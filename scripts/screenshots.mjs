// Screenshots of Ingest in Dog-ear Books, as used in Ingest's docs and
// Marketplace listing. Run against the demo after:
//
//   php artisan demo:reset && php artisan ingest:run supplier_catalogue author_events publisher_news jobs opening_hours
//   php artisan demo:feed-lab books v2
//   php artisan demo:users
//
// then: cd scripts && npm install && npm run screenshots
//
// DEMO_URL     the demo's address (default https://ingest-statamic-demo.test)
// SCREENSHOTS  where to write them (default ./screenshots)
// CHROME       a Chrome or Chromium to drive (default: Google Chrome on macOS)
import { chromium } from 'playwright-core';
import { mkdirSync } from 'node:fs';

const base = (process.env.DEMO_URL || 'https://ingest-statamic-demo.test').replace(/\/$/, '');
const out = process.env.SCREENSHOTS || new URL('./screenshots', import.meta.url).pathname;
const chrome = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
mkdirSync(out, { recursive: true });

const browser = await chromium.launch({ args: ['--ignore-certificate-errors'], executablePath: chrome });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2, colorScheme: 'light' });
const page = await context.newPage();

async function settle(ms = 800) {
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(ms);
}

async function shot(name) {
    await settle();
    await page.screenshot({ path: `${out}/${name}.png` });
    console.log(`${name}.png`);
}

// Sign in to the Control Panel (the demo's local admin).
await page.goto(`${base}/cp/auth/login`);
await page.fill('input[type="text"]', 'admin@example.com');
await page.fill('input[type="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForURL(/\/cp\/dashboard/);

// Snooze the trial-mode licensing alert if it shows.
async function dismissLicensing() {
    const snooze = page.getByRole('button', { name: 'Snooze' });
    if (await snooze.isVisible().catch(() => false)) await snooze.click();
}

await page.goto(`${base}/cp/ingest`);
await dismissLicensing();
await shot('cp-feeds');

// The feed form: the mapping tab with the live preview.
await page.goto(`${base}/cp/ingest/feeds/supplier_catalogue/edit`);
await dismissLicensing();
await page.getByRole('tab', { name: /Mapping/ }).click();
await page.getByText('Feed preview').waitFor();
await settle(2500);
await page.getByRole('button', { name: /Editions/ }).first().click();
await shot('cp-mapping');

await page.getByRole('tab', { name: /^Source/ }).click();
await shot('cp-source');

await page.getByRole('tab', { name: /Sync rules/ }).click();
await shot('cp-sync-rules');

// A dry run of the stock feed: prices changed in v2.
await page.goto(`${base}/cp/ingest/feeds/stock_levels`);
await dismissLicensing();
await page.getByRole('button', { name: 'Dry run' }).first().click();
await page.waitForURL(/\/runs\//);
await page.getByText('Nothing was saved').waitFor();
await settle(1500);
await page.locator('button[aria-expanded]').filter({ hasText: 'Would update' }).first().click();
await shot('cp-dry-run');

// The catalogue's page, with its runs.
await page.goto(`${base}/cp/ingest/feeds/supplier_catalogue`);
await shot('cp-feed');

// The dashboard widget.
await page.goto(`${base}/cp/dashboard`);
await dismissLicensing();
await shot('cp-dashboard');

// The shop.
const shop = await browser.newContext({ viewport: { width: 1280, height: 860 }, deviceScaleFactor: 2, colorScheme: 'light' });
const front = await shop.newPage();
for (const [path, name] of [['/', 'shop-home'], ['/products/an-orchard-for-pip', 'shop-product'], ['/events', 'shop-events']]) {
    await front.goto(`${base}${path}`);
    await front.evaluate(() => document.fonts.ready);
    await front.waitForTimeout(600);
    await front.screenshot({ path: `${out}/${name}.png` });
    console.log(`${name}.png`);
}

await browser.close();
