---
name: verify-in-browser
description: Use whenever asked to test, verify, simulate, "try", or check that a UI/feature change works in this app (Pharmacy.vue, Inertia pages, any resources/js changes). Always drive a real headless browser and produce a screenshot the user can actually see — never just describe in text what "should" happen or reason from reading the code alone.
---

# Verify in browser (screenshot, not just text)

This app is Laravel + Inertia + Vue. Reading the component code is not proof
it renders or behaves correctly. When the user asks to test/verify/simulate
a change (or when you're about to claim "this works"), actually load the
page in a headless browser, interact with it, and **show a screenshot**
(via the Read tool, so it renders inline for the user) instead of only
describing expected behavior in prose.

## 1. Start the dev server

Preferred (per project setup — starts Laravel, queue, logs, and Vite together):

```bash
composer dev &
timeout 30 bash -c 'until curl -sf http://127.0.0.1:8000 >/dev/null; do sleep 1; done'
```

If that's too heavy for a quick check, `php artisan serve &` alone is enough
for pages that don't need the queue worker; Vite dev assets still need
`npm run dev &` alongside it (or run `npm run build` once for a static build).

Stop when done — find and kill the listener on 8000 (and Vite's 5173) rather
than `pkill -f`, which can match unrelated processes:

```bash
lsof -ti:8000 -sTCP:LISTEN | xargs -r kill
lsof -ti:5173 -sTCP:LISTEN | xargs -r kill
```

## 2. Log in

There's a seeded admin (`database/seeders/SuperAdminSeeder.php`) — get the
current credentials from that file locally (do not hardcode the password in
any committed file). Note: that account has `mfa_enabled = true`, which
will block a scripted login — for a quick UI check, prefer a test/dev user
without MFA if one exists in your local DB, or temporarily flip
`mfa_enabled` off for a seeded test user in your local database only.

## 3. Drive the browser

No `chromium-cli` and no local `playwright` npm package in this repo, but
Playwright *has* been used here before via `npx`, so both the package and
its browser binaries are already cached (no network needed). Two things you
must resolve dynamically — cache hashes/versions can differ by machine —
rather than hardcoding a path:

```bash
# 1. a node_modules dir that has playwright installed (from a prior npx run)
PW_MODULES=$(dirname "$(find ~/.npm/_npx -maxdepth 3 -type d -name playwright | head -1)")

# 2. a real (non-headless-shell) chrome binary — playwright's launch() default
#    looks for a specific headless-shell build that may not be cached; the
#    full chrome-linux64 build usually is, so pick the newest one explicitly.
CHROME_BIN=$(find ~/.cache/ms-playwright -maxdepth 3 -iname chrome -type f | grep -v headless | sort -V | tail -1)

echo "$PW_MODULES / $CHROME_BIN"
```

Write a **CommonJS** (`.cjs`) script, not ESM — `NODE_PATH` (needed to find
the cached `playwright` package without a local `node_modules`) is ignored
by Node's ESM resolver but honored by `require()`:

```bash
cat > /path/to/scratchpad/verify.cjs <<'EOF'
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch({
    executablePath: process.env.PW_CHROME,
    args: ['--no-sandbox'],
  });
  const page = await browser.newPage();
  page.on('pageerror', e => console.error('PAGE ERROR:', e));
  page.on('console', m => { if (m.type() === 'error') console.error('CONSOLE:', m.text()); });

  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('input[type=email]', process.env.PW_EMAIL);
  await page.fill('input[type=password]', process.env.PW_PASSWORD);
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle');

  await page.goto('http://127.0.0.1:8000/pharmacy'); // whatever route changed
  await page.waitForSelector('text=Pharmacy');        // wait for something you expect

  await page.screenshot({ path: '/path/to/scratchpad/verify.png', fullPage: true });
  await browser.close();
})();
EOF

NODE_PATH="$PW_MODULES" PW_CHROME="$CHROME_BIN" PW_EMAIL=... PW_PASSWORD=... \
  node /path/to/scratchpad/verify.cjs
```

Passing credentials via env vars (not inline in the script file) keeps them
out of any file that might get read back or committed later.

For multi-step interactions (fill a form, submit, check a computed price,
etc.) chain more `page.fill` / `page.click` / `page.waitForSelector` calls
before the final screenshot — mirror the exact user flow being tested, not
just the landing state.

## 4. Show it

After the script runs, **Read the screenshot file** so it renders inline in
the conversation. Do not just say "the screenshot was saved" or describe
what it probably looks like — the whole point is the user sees the actual
rendered browser state.

## Gotchas

- Vue's `v-model` inputs: use `page.fill()`/`page.type()`, not
  `evaluate(el => el.value = ...)` — the latter skips the `input` event
  Vue's reactivity depends on.
- Inertia pages are SPA-like after first load — `waitForLoadState('networkidle')`
  after navigation clicks, since URL changes don't trigger a full page load.
- Check the browser console for JS errors before declaring success —
  `page.on('console', msg => ...)` or `page.on('pageerror', ...)` registered
  before `goto()`.
- If Tailwind/Vite assets are stale, screenshots will look unstyled — make
  sure `npm run dev` or `npm run build` actually ran first.
