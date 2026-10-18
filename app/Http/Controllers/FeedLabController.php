<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The demo's "feed lab": made-up feeds served from resources/feed-lab (a
 * copy of 1994limited/ingest-test-data), plus a few endpoints
 * that behave like real suppliers and APIs:
 *
 *   /feed-lab/supplier/books?page=2   the supplier catalogue, 10 a page, in
 *                                     its current state (php artisan demo:feed-lab books v2)
 *   /feed-lab/api/events?cursor=…     needs "Authorization: Bearer" with FEED_LAB_TOKEN
 *   /feed-lab/{file}                  any file in the test data
 *
 * Local only.
 */
class FeedLabController extends Controller
{
    public const STATES = ['v1', 'v2', 'v3', 'empty', 'broken', 'slow', 'error'];

    public static function state(string $feed): string
    {
        return Cache::get("feed-lab:{$feed}", 'v1');
    }

    public function books(Request $request)
    {
        $state = self::state('books');

        if ($state === 'error') {
            return response('Supplier system unavailable', 503);
        }
        if ($state === 'broken') {
            return response('<!doctype html><title>Sign in</title><p>Your session has expired. Please sign in.</p>', 200, ['Content-Type' => 'text/html']);
        }
        if ($state === 'slow') {
            sleep(3);
            $state = 'v1';
        }

        $data = json_decode(File::get(resource_path("feed-lab/states/books/{$state}.json")), true)['data'];
        $pages = array_chunk($data, 10) ?: [[]];
        $page = max(1, (int) $request->query('page', 1));

        return response()->json([
            'data' => $pages[$page - 1] ?? [],
            'meta' => ['current_page' => $page, 'last_page' => count($pages), 'total' => count($data)],
        ]);
    }

    /**
     * Stock and prices by SKU for the books in the current catalogue state:
     * a little stock sells every hour, so each run has something to update.
     */
    public function stock()
    {
        $data = json_decode(File::get(resource_path('feed-lab/states/books/'.(in_array(self::state('books'), ['v2', 'v3'], true) ? self::state('books') : 'v1').'.json')), true)['data'];
        $sold = (int) now()->format('G') % 4;
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['sku', 'price', 'stock'], ',', '"', '');

        foreach ($data as $book) {
            foreach ($book['formats'] as $format) {
                if ($format['stock'] !== null) {
                    fputcsv($out, [$format['sku'], $format['price'], max(0, $format['stock'] - $sold)], ',', '"', '');
                }
            }
        }

        rewind($out);

        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv']);
    }

    public function events(Request $request)
    {
        if ($request->bearerToken() !== config('services.feed_lab.token')) {
            return response()->json(['error' => 'unauthorised'], 401);
        }

        $events = json_decode(File::get(resource_path('feed-lab/events/index.json')), true)['events'];

        // Move the made-up dates into the coming weeks, so they're upcoming.
        $start = now()->startOfDay()->addDays(2);
        foreach ($events as $i => &$event) {
            $at = $start->copy()->addDays($i * 2)->setTime(18, 30);
            $event['starts_at'] = $at->toIso8601String();
            $event['ends_at'] = $at->copy()->addMinutes(90)->toIso8601String();
        }

        $offset = (int) ($request->query('cursor') ? base64_decode($request->query('cursor')) : 0);
        $slice = array_slice($events, $offset, 10);
        $next = $offset + 10 < count($events) ? base64_encode((string) ($offset + 10)) : null;

        return response()->json(['events' => $slice, 'next_cursor' => $next]);
    }

    public function file(string $path)
    {
        $path = str_replace('..', '', $path);
        $file = resource_path('feed-lab/'.$path);

        abort_unless(is_file($file), 404);

        $type = match (pathinfo($file, PATHINFO_EXTENSION)) {
            'json' => 'application/json',
            'xml' => Str::contains($path, ['rss', 'merchant', 'podcast']) ? 'application/rss+xml' : 'application/xml',
            'csv' => 'text/csv',
            'png' => 'image/png',
            default => 'text/plain',
        };

        return response()->file($file, ['Content-Type' => $type]);
    }
}
