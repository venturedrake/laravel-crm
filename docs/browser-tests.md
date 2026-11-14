# Product form submission regression tests

Save on quote, order, invoice, purchase-order and delivery create/edit forms captures the current shared product editor immediately. It prepares the captured rows and passes the result to `saveWithProducts()` before the parent validates and persists. Debounced preview events still update the page, but do not supply the rows used by this save action. Deal forms own their rows directly and retain their existing submission path.

The form shows a saving indicator and prevents edits and duplicate submissions during preparation and persistence. Validation errors return control to the form. A failed request displays a retry message and retains the captured entries; it never falls back to the parent's cached rows. Existing authorization, quantity limits, discounted pricing, invoice settlement rules and delivery-line identities still apply.

Hosts that publish package views or assets must update both together using their normal package update/publish procedure. Old published views retain the old submission behavior; new views require the rebuilt JavaScript bundle.

## Run the browser suite

Requirements: the package's Composer dependencies, Node/npm and a PHP version supported by those dependencies.

```bash
npm install
npx playwright install chromium
npm run build
npm run test:browser
```

Playwright starts a disposable Laravel host at `127.0.0.1:8765`. Its SQLite database, sessions, compiled views and results live under `.context/`. Setup recreates only `.context/browser-host/database/database.sqlite`. The host loads this worktree's package classes and compiled assets; it does not change the existing Herd host, its Composer symlink or its database. Fixture routes and permissive test users are registered only by this test host, never by the package service provider. The fixture uses `TestSchema` and real form components/services, with Xero disconnected.

The tests cover the 2.5 order / 1.25 first delivery / immediate 0.5 second delivery sequence (0.75 remainder), pending debounce and in-flight requests, create/edit forms across all five families, immediate price/comment changes, reload persistence, duplicate submission, validation failure, network failure/retry at each save stage, Enter in the create-product drawer (it creates the product rather than submitting the surrounding document form), and the separate deal editor. Request payloads for the two ordering regressions are attached to Playwright results; failure traces are retained in `.context/browser-results`.

## Livewire / Laravel matrix

Install a separate dependency set without changing `composer.json`, `composer.lock` or `vendor/` in the worktree:

```bash
php tests/browser/install-matrix.php 3 12
CRM_BROWSER_VENDOR="$PWD/.context/browser-dependencies/lw3-laravel12/vendor" npm run test:browser

php tests/browser/install-matrix.php 4 13
CRM_BROWSER_VENDOR="$PWD/.context/browser-dependencies/lw4-laravel13/vendor" npm run test:browser
```

The installer takes Livewire major (3 or 4) and Laravel major (11, 12 or 13). Use a PHP version that satisfies the requested Laravel version. `CRM_BROWSER_VENDOR` must be an absolute path to the isolated dependencies. The bootstrap rejects class maps that resolve `ModelProducts` to another checkout.

Run suites sequentially: they share the disposable database and port. `npm run build` is required after changing frontend code; the tests exercise the built bundle that hosts receive.
