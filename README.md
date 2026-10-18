# Dog-ear Books with Cargo: a Back in Stock demo

A small bookshop built with [Statamic](https://statamic.com) and [Cargo](https://builtwithcargo.dev), running [Back in Stock for Statamic](https://github.com/1994limited/back-in-stock-statamic). Some books, and some editions, are sold out: leave your email on one, confirm it, put the stock back in the Control Panel, and get the email. Copy whatever helps.

It shows:

- **A "Tell me when it's back" form** on sold-out books, made with `{{ back_in_stock }}`. It works without JavaScript, has a consent tick box and a hidden honeypot field, and fills in your email when you're signed in.
- **Editions.** Coral Arithmetic's hardback is sold out and its paperback isn't. Choose the hardback in the basket form and the button says "Sold out", and the form below asks about the hardback (`follow="variant"`). Without JavaScript it asks which edition instead.
- **Confirming.** Every request is confirmed from a link in an email, so nobody can sign someone else up.
- **Restocks.** Put a book back in stock in the Control Panel (or cancel an order for it) and everyone waiting is emailed once, with a one-click unsubscribe.
- **Your alerts** (`/alerts`), for a signed-in reader: what they're waiting for, and a button to cancel each.
- **The Control Panel**: Store → Back in Stock lists the most wanted sold-out books, who's waiting, what happened at each restock, and who bought after the email. The dashboard has a "Wanted while sold out" widget.

The book covers are drawn in CSS: there are no image files, and every title and author is invented.

## Running it

You need PHP 8.3+, Composer, and a local web server such as [Herd](https://herd.laravel.com) or `php artisan serve`.

```bash
git clone https://github.com/1994limited/back-in-stock-statamic-demo.git
cd back-in-stock-statamic-demo
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan demo:users
php artisan demo:requests
php artisan serve
```

Then open http://localhost:8000.

`demo:users` makes two accounts, both with the password `password`:

| Account | For |
| --- | --- |
| `jo@example.com` | Shopping: the sign-in page is filled in with it |
| `admin@example.com` | The Control Panel, at `/cp`, where the books' stock is |

`demo:requests` adds people already waiting for the sold-out books, so the Control Panel has something to show. It sends no email. Both commands only run with `APP_ENV=local`: don't put this demo on a public server as it is, as the sign-in page gives the password away.

Emails go to the log (`storage/logs/laravel.log`) unless you set `MAIL_MAILER` in `.env`. A local mail catcher such as [HELO](https://usehelo.com) or Mailpit shows them as they'd look.

Back in Stock and Cargo are commercial addons. Running them locally like this needs no licence. The demo turns on Statamic Pro (`STATAMIC_PRO_ENABLED=true`), which Cargo needs, and which runs in Statamic's trial mode locally.

## Things to try

1. Open **The Lantern Ledger**, which is sold out. Leave your email, tick the box, and send. Open the email and confirm.
2. Sign in to the Control Panel as `admin@example.com`, open The Lantern Ledger, and set its stock to 3. Everyone waiting is emailed. Store → Back in Stock shows the restock.
3. Open **Coral Arithmetic** and choose the hardback. Ask to hear about it.
4. Sign in as `jo@example.com` and ask about a book: your email is filled in. Your alerts are at **Alerts**.
5. In Tools → Addons → Back in Stock → Settings, choose "As many as came back", then put one book back in stock: only the person who asked first is emailed.

## Where to look

| File | What it shows |
| --- | --- |
| `resources/views/products/show.antlers.html` | `{{ back_in_stock }}` with `follow="variant"`, and the basket form's edition choice |
| `resources/views/home.antlers.html` | `{{ back_in_stock:sold_out }}` on the shelves |
| `resources/views/alerts.antlers.html` | `{{ back_in_stock:alerts }}` and `{{ back_in_stock:cancel }}` |
| `resources/views/layout.antlers.html` | `{{ back_in_stock:success }}` after confirming or cancelling |
| `resources/blueprints/collections/products/` | Two book blueprints: one edition (price and stock), or several (Cargo's `product_variants`, with stock per edition) |
| `config/statamic/cp.php` | The dashboard widgets |
| `routes/console.php` | `demo:users` and `demo:requests` |

Back in Stock's [docs](https://github.com/1994limited/back-in-stock-statamic/blob/main/docs/README.md) explain each of these.

## Licence

The demo's own code (templates, styles, content) is MIT licensed: copy whatever helps. Back in Stock and Cargo are commercial software: see [Back in Stock's licence](https://github.com/1994limited/back-in-stock-statamic/blob/main/LICENSE.md) and [Cargo's](https://builtwithcargo.dev).
