<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The demo's two accounts: Jo, who shops, and an admin for the Control
 * Panel. Both use the password "password", so this only runs locally.
 */
Artisan::command('demo:users', function () {
    if (! app()->environment('local')) {
        $this->error('demo:users only runs with APP_ENV=local: its passwords are public.');

        return 1;
    }

    $accounts = [
        ['jo@example.com', 'Jo Shopper', false],
        ['admin@example.com', 'Admin', true],
    ];

    foreach ($accounts as [$email, $name, $super]) {
        $user = \Statamic\Facades\User::findByEmail($email) ?? \Statamic\Facades\User::make()->email($email);
        $user->set('name', $name)->password('password');

        // makeSuper() takes no argument: it always makes a super user.
        $super ? $user->makeSuper() : $user->remove('super');

        $user->save();
        $this->info("{$email} / password".($super ? ' (Control Panel)' : ''));
    }
})->purpose('Create the demo shopper and admin accounts');

/*
 * Change what the feed lab's supplier catalogue says, to see Ingest sync
 * the change: v2 changes prices and a title and swaps a book, v3 changes a
 * cover; empty, broken (a sign-in page), slow and error show the safety
 * checks and failure alerts. Local only.
 */
Artisan::command('demo:feed-lab {feed=books} {state?}', function () {
    if (! app()->environment('local')) {
        $this->error('demo:feed-lab only runs with APP_ENV=local.');

        return 1;
    }

    $states = App\Http\Controllers\FeedLabController::STATES;
    $state = $this->argument('state');

    if ($state === null) {
        $this->info($this->argument('feed').' is '.App\Http\Controllers\FeedLabController::state($this->argument('feed')).'. States: '.implode(', ', $states));

        return 0;
    }

    if (! in_array($state, $states, true)) {
        $this->error('Choose one of: '.implode(', ', $states));

        return 1;
    }

    Illuminate\Support\Facades\Cache::forever('feed-lab:'.$this->argument('feed'), $state);
    $this->info($this->argument('feed')." now serves {$state}.");
})->purpose('Change what the demo feed lab serves');

/*
 * Back to the start: remove everything the feeds imported (entries,
 * terms, downloaded images), forget the run history, and put the feed lab
 * back to v1. Then run the feeds again to see them arrive. Local only.
 */
Artisan::command('demo:reset', function () {
    if (! app()->environment('local')) {
        $this->error('demo:reset only runs with APP_ENV=local.');

        return 1;
    }

    $map = app(NineteenNinetyFour\Ingest\Contracts\ItemMap::class);
    $removed = 0;

    foreach ($map->all() as $record) {
        $content = match ($record->contentType) {
            'entry' => Statamic\Facades\Entry::find($record->contentId),
            'term' => Statamic\Facades\Term::find($record->contentId),
            'asset' => Statamic\Facades\Asset::find($record->contentId),
            default => null,
        };

        if ($content) {
            $content->delete();
            $removed++;
        }
    }

    // Authors and subjects made for relationship fields.
    foreach (['authors'] as $collection) {
        Statamic\Facades\Entry::query()->where('collection', $collection)->get()->each->delete();
    }
    Statamic\Facades\Term::query()->where('taxonomy', 'subjects')->get()->each->delete();
    Statamic\Facades\Entry::query()->where('collection', 'staff_picks')->get()->each->delete();

    Illuminate\Support\Facades\File::deleteDirectory(storage_path('ingest'));
    Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('ingest'));
    file_put_contents(storage_path('ingest/.gitignore'), "*\n!.gitignore\n");
    foreach (['covers', 'news'] as $folder) {
        Illuminate\Support\Facades\File::deleteDirectory(public_path("assets/{$folder}"));
    }

    Illuminate\Support\Facades\Cache::forget('feed-lab:books');
    Statamic\Facades\Stache::clear();

    $this->info("Removed {$removed} imported items. Run the feeds again: php artisan ingest:run supplier_catalogue author_events publisher_news jobs opening_hours");
})->purpose('Remove everything the demo feeds imported');
