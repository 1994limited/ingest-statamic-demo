# Dog-ear Books: a demo of Ingest for Statamic

A small bookshop, built on Statamic and Cargo, whose catalogue, stock, author events, publisher news, jobs, staff picks and opening hours all arrive from feeds through [Ingest](https://github.com/1994limited/ingest-statamic). It has a **feed lab**: made-up feeds served by the site itself, with switches to make them change, break or slow down, so every part of Ingest can be tried without a real supplier.

Everything here is made up, from the books to the publishers.

## Running it

You need PHP 8.3+, Composer, and Herd or Valet (the feeds point at `https://ingest-statamic-demo.test`).

```bash
git clone https://github.com/1994limited/ingest-statamic-demo.git
cd ingest-statamic-demo
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan demo:users
```

In `.env`, point `INGEST_HTTP_VERIFY` at your local certificate authority so the site can read its own feed lab over HTTPS. For Herd:

```env
INGEST_HTTP_VERIFY="/Users/you/Library/Application Support/Herd/config/valet/CA/LaravelValetCASelfSigned.pem"
```

Then bring everything in:

```bash
php artisan ingest:run supplier_catalogue author_events publisher_news jobs opening_hours
```

The catalogue then runs the stock feed and the staff picks. Open the shop at `https://ingest-statamic-demo.test`, and the Control Panel at `/cp` as `admin@example.com` with the password `password` (local only: `demo:users` refuses to run anywhere else).

## The feeds

They're in `resources/ingest/feeds`, as YAML.

| Feed | From | Into |
|---|---|---|
| Supplier catalogue | A paginated JSON API | Cargo products with editions, covers, authors, subjects |
| Stock and prices | A CSV by SKU | Cargo stock and prices |
| Author events | A JSON API with a bearer token and cursor pages | Events |
| Publisher news | RSS | News |
| Jobs board | XML | Jobs |
| Staff picks | A Google-Sheets-style CSV | Staff picks |
| Opening hours | A JSON object | The Shop globals |

## Making things happen

```bash
php artisan demo:feed-lab books v2       # prices change, a title changes, one book goes, one arrives
php artisan demo:feed-lab books v3       # a cover changes
php artisan demo:feed-lab books empty    # an empty catalogue: the safety check stops it
php artisan demo:feed-lab books broken   # a sign-in page instead of the feed
php artisan demo:feed-lab books error    # a 503, and an email to shop@example.com
php artisan demo:feed-lab books slow     # a three-second response
php artisan demo:feed-lab books v1       # back to the start
php artisan demo:reset                   # remove everything the feeds imported
```

After each change, try a **Dry run** in the Control Panel, apply it, then undo it.

## Screenshots

`scripts/screenshots.mjs` takes the screenshots in Ingest's docs (`cd scripts && npm install && npm run screenshots`).

## Licence

The demo's own code is MIT. Ingest itself is commercial: see its [licence](https://github.com/1994limited/ingest-statamic/blob/main/LICENSE.md).
