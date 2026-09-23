# Upgrade guide

Published at <https://laravelcrm.com/docs/2.x/upgrading>, which is where the CRM's own
"Upgrade guide" links point.

Notes for operators upgrading an existing `venturedrake/laravel-crm` install.

Two things to read: **How to update** below, which is the same every release, and the
**version-specific notes** at the bottom for the release you are moving to. Read the latter
**before** you deploy — some releases change who is allowed to do what, and the failure mode is a
`403` for a user who could previously click the button.

---

## How to update (local)

```bash
composer update venturedrake/laravel-crm
php artisan laravelcrm:update
```

That is the whole procedure. The first command republishes assets and clears caches via the
composer hook; the second applies database changes.

> `composer update && php artisan migrate` is **not** enough. Migrations published before 2.4.0
> ship as `.stub` files that have to be published into your `database/migrations` before the
> migrator can see them, and a stale `manifest.json` will point at asset filenames that no longer
> exist on disk. `laravelcrm:update` does both.

---

## How to update (production deploy)

```bash
composer install --no-dev --optimize-autoloader   # post-autoload-dump fires laravelcrm:upgrade
php artisan laravelcrm:update --force             # migrations + backfills, no prompts
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`--force` skips the production confirmation prompt. `laravelcrm:update` exits non-zero if
migrations or seeding fail, so `&&` chains and CI steps stop where they should.

### Zero-downtime deploys (Envoyer, Deployer, Vapor)

The two commands belong in different hooks:

| Command | Hook | Why |
| --- | --- | --- |
| `laravelcrm:upgrade` | **Build / install** (it fires automatically from `composer install`) | It writes into *that release's* `public/`, so it has to run per release directory, before the symlink flips |
| `laravelcrm:update` | **Activate / after-deploy**, once | It touches the shared database. Running it per server would run the same migrations concurrently |

If your platform builds each release in a fresh directory, nothing extra is needed for the first
one — the composer hook handles it. Just make sure the second is not in a per-server hook.

---

## What each command does

| | `laravelcrm:upgrade` | `laravelcrm:update` |
| --- | --- | --- |
| Republishes built assets (JS/CSS/images) | ✅ | ✅ (calls `upgrade` first) |
| Prunes stale content-hashed build files | ✅ | ✅ |
| Clears cached config, routes, views | ✅ | ✅ |
| Publishes Flasher assets | ✅ | ✅ |
| Publishes migration stubs | ❌ | ✅ |
| Runs `migrate` | ❌ | ✅ |
| Runs seeders and data backfills | ❌ | ✅ |
| Stamps the `db_version` marker | ❌ | ✅ |
| Prompts | never | only on an interactive production console, unless `--force` |
| Exits non-zero on failure | only if asset publishing itself errors | on any failure |

**`laravelcrm:upgrade` never opens a database connection.** That is deliberate: it runs from a
composer hook, which can fire during a build when the database is unreachable, mid-migration, or
belongs to a different release. All database work is in `laravelcrm:update`, which you run
explicitly.

`laravelcrm:update` also runs the lookup-data seeders that this guide used to tell you to run by
hand — `laravelcrm:lead-sources`, and on teams installs `laravelcrm:permissions`,
`laravelcrm:labels`, `laravelcrm:addresstypes`, `laravelcrm:contacttypes` and
`laravelcrm:organizationtypes`. All are idempotent.

---

## One-time step for installs from before 2.4.0

New installs get this from `laravelcrm:install`. Existing hosts add it once, to the host
application's `composer.json`:

```json
"scripts": {
    "post-autoload-dump": [
        "@php artisan package:discover --ansi",
        "@php artisan laravelcrm:upgrade --ansi"
    ]
}
```

Then run `composer dump-autoload` to confirm it fires. From that point on, every `composer install`
and `composer update` republishes CRM assets and clears caches on its own.

The line must come **after** `package:discover` — that is what makes the package's artisan
commands resolvable.

---

## Caveats

**Published views are frozen.** If you ran `vendor:publish --tag=views`, your host's copies in
`resources/views/vendor/laravel-crm` shadow the package's and will *not* pick up template changes
from a release. Re-publish with `--force` and re-apply your edits, or diff the package's
`resources/views` against your copies before upgrading. The same applies to published lang files.

**New config keys arrive automatically, but caches hide them.** Keys added to
`config/package.php` and `config/laravel-crm.php` reach your app through `mergeConfigFrom`, so you
do not need to re-publish the config. A stale `php artisan config:cache` from the previous release
will hide them — the composer hook runs `config:clear` for exactly this reason. Keys you have
overridden in your published `config/laravel-crm.php` stay as you set them.

2.4.0 adds one: `upgrade_guide_url` (`LARAVEL_CRM_UPGRADE_GUIDE_URL`), which every "Upgrade guide"
link in the CRM now points at, defaulting to <https://laravelcrm.com/docs/2.x/upgrading>. It is
separate from `docs_url`, which keeps carrying the "View version X details" link. Override it if
you host your own documentation.

**The database can be behind the code without anything looking wrong.** The CRM stamps a
`db_version` setting when `laravelcrm:update` completes and reports a banner when the installed
code is ahead of it, or when one of *this package's* migrations has not run. Migrations belonging
to your own application or to other packages are not counted — an unrun migration of yours will
never raise a CRM banner. If you see *"Your Laravel CRM version requires some database updates"*,
run `php artisan laravelcrm:update`.

**Rolling back the package does not roll back the database.** Migrations are not reversed by
downgrading the composer constraint. Restore from a backup if you need to go back.

**Remove the composer hook before you remove the package.** `post-autoload-dump` fires on
`composer remove venturedrake/laravel-crm` too, at which point `laravelcrm:upgrade` no longer
exists and composer reports the script returned a non-zero exit code. Delete the
`@php artisan laravelcrm:upgrade --ansi` line from your `composer.json` first.

---

## Version-specific notes

## 2.4.5

### No migrations, no new config keys

This release changes only which packages composer installs and how the Xero integration behaves when
one of them is absent. It adds no tables, columns or configuration keys, so

```bash
composer update venturedrake/laravel-crm
php artisan laravelcrm:update
```

is the whole upgrade. `laravelcrm:update` still advances the `db_version` marker, so run it even
though there is nothing to migrate — otherwise the system check reports the database as behind the
code.

### If you use the Xero integration, require it yourself *before* you deploy

`dcblogdev/laravel-xero` was in this package's `require`. It is now a `suggest` plus a `require-dev`,
which means nothing pulls it into your application any more. Add it to your own `composer.json`:

```bash
composer require dcblogdev/laravel-xero:1.1.3
```

Do that **in the same change as the upgrade**, not after it. Upgrade first and the package is gone
between the two deploys, and the integration stops syncing in that window without saying so.

**Nothing errors when it is missing — it goes quiet.** Every entry point now gates on
`XeroIntegration::installed()`, so with the package absent the `XeroToken` observer is never
registered, `XeroTenant` is never pushed onto the `web` or `crm-api` middleware groups,
`Product` / `Invoice` / `PurchaseOrder` sync becomes a no-op, and `php artisan laravelcrm:xero`
exits non-zero with the install command instead of a fatal error. There is no banner and no failed
job. The one visible sign is **Settings → Integrations → Xero**, where the "Connect to Xero" button
is replaced by an alert naming the missing package.

Your data is not touched. Stored tokens and the `crm_xero_*` mirror rows survive, so installing the
package restores the connection with nothing to re-authorise.

### Why: the package would not install on Guzzle 8

`composer require venturedrake/laravel-crm` failed outright in any app that had resolved
`guzzlehttp/guzzle` 8 — which includes a stock Laravel 13.32+ app, since `laravel/framework` moved
to `^7.8.2 || ^8.0` at that version. Composer named this package's own `^6.0|^7.0` constraint first,
so it read as a one-line bump, but the binding cap arrived transitively from `dcblogdev/laravel-xero`,
which pins guzzle to `^7.9.3` at most in **every version it has ever published**. There is no
upstream release of it compatible with Guzzle 8.

So the choice is yours rather than ours, and it is a real one:

| You want | Do this | Consequence |
| --- | --- | --- |
| Xero sync | `composer require dcblogdev/laravel-xero:1.1.3` | Your app stays on Guzzle 7 until that package ships a Guzzle 8 release |
| Guzzle 8 / Laravel 13.32+ | Nothing | The Xero integration is inert, as described above |

You cannot have both today.

### Views to re-publish

If you have published views into `resources/views/vendor/laravel-crm`, the view finder prefers your
frozen copy, and this release changes one of them:

| View | What you miss if you keep the old copy |
|---|---|
| `livewire/settings/integrations/xero/xero-connect.blade.php` | The "not installed" alert. A frozen copy still renders a **Connect to Xero** button with the package absent. It is not fatal — the route redirects back to this same screen — but it loops silently instead of naming what to install, which removes the only signal this release gives you |

`php artisan laravelcrm:upgrade` names your drifted published views on every deploy (added in 2.4.1),
so this table is a cross-check rather than the only signal you will get.

### If you copied blades out of `resources/v1/` during a v1 → v2 migration

`resources/v1/views/invoices/partials/fields.blade.php` and its purchase-order twin were gated in
this release too, but nothing in the package loads or publishes that tree — it is there for v1-era
apps that lifted the blades into their own `resources/views`. If yours is one of them, a copy taken
before this release calls `\Dcblogdev\Xero\Facades\Xero::isConnected()` directly, and with the
package absent that is a hard `Class "Dcblogdev\Xero\Facades\Xero" not found` on the invoice and
purchase-order forms. Swap the call for
`\VentureDrake\LaravelCrm\Support\XeroIntegration::connected()`, which is a drop-in replacement, or
keep `dcblogdev/laravel-xero` installed.

## 2.4.4

### No migrations, no new config keys

This is a performance patch release. It adds no tables or columns and no configuration keys, so

```bash
composer update venturedrake/laravel-crm
php artisan laravelcrm:update
```

is the whole upgrade. `laravelcrm:update` still advances the `db_version` marker, so run it even
though there is nothing to migrate — otherwise the system check reports the database as behind the
code.

### The Owner filter no longer has a "Select all"

The Owner filter used to hand the whole users table to the filter drawer, which is why a page on a
large install could weigh tens of megabytes and take seconds to render. It now searches server-side:
type a name and the matches come back from the database, capped at 20 at a time, instead of scrolling
a list that was pre-rendered in full on every request.

The trade is **Select all**, which is gone from that filter. It could only ever have selected the
options that happened to be rendered, so on any install big enough for this to matter it was already
selecting a subset rather than "all owners". **Clear** takes its place for emptying the filter in one
click, and owners you have already selected stay visible as chips even when they fall outside the
current search.

No action is required. Tell your support and sales people, since it is the one change in this release
they will notice.

### Views to re-publish

If you have published views into `resources/views/vendor/laravel-crm`, the view finder prefers your
frozen copy, and this release changes the Owner `<x-mary-choices>` on 15 of them:

| View | What you miss if you keep the old copy |
|---|---|
| The 12 index blades (`livewire/people/person-index.blade.php`, `livewire/organizations/organization-index.blade.php`, `livewire/leads/lead-index.blade.php`, and the same file under `.../deals`, `.../quotes`, `.../orders`, `.../invoices`, `.../deliveries`, `.../purchase-orders`, `.../products`, `.../tasks`, `.../teams`) | The `searchable` / `search-function="searchUsers"` / `clearable` Owner filter. **This one does not error — it goes quietly wrong.** A frozen copy keeps `allow-all` and has no search box, while the component behind it now returns at most 20 users, so the filter lists only the first 20 owners alphabetically with no way to reach the rest, and **Select all** selects those 20 rather than everyone. On an install with more than 20 users, re-publishing these is not optional |
| The 3 board blades (`livewire/leads/lead-board.blade.php`, `livewire/deals/deal-board.blade.php`, `livewire/quotes/quote-board.blade.php`) | The same change, and the same silent failure |

`php artisan laravelcrm:upgrade` names your drifted published views on every deploy (added in 2.4.1),
so this table is a cross-check rather than the only signal you will get.

(The reverse combination *does* throw: MaryUI rejects `allow-all` together with `searchable`, which is
why **Select all** could not simply be kept alongside the new search.)

## 2.4.3

### No migrations, no new config keys

This is a performance patch release. It adds no tables or columns and no configuration keys, so

```bash
composer update venturedrake/laravel-crm
php artisan laravelcrm:update
```

is the whole upgrade. `laravelcrm:update` still advances the `db_version` marker, so run it even
though there is nothing to migrate — otherwise the system check reports the database as behind the
code.

### Clear your application cache after deploying

This release gates the settings-seeding pass that used to run on every CRM page behind a cache flag,
which is the one genuinely new operator-facing behaviour here. The flag is stamped with the package
version (`crm.settings-seeded.2.4.3`), so **the version bump re-arms it on its own** — the first CRM
request after the upgrade runs the seeding pass once, and every request after that skips it. You do
not have to clear anything by hand for that to happen, and `laravelcrm:upgrade` (which fires from
the composer hook, and which `laravelcrm:update` calls first) clears your caches anyway. This is a
caveat only for anyone who bypasses both.

The flag also carries a 24-hour TTL, so a settings row deleted by hand heals itself on the next day's
first request rather than staying missing until the next release.

**On a teams install the flag is partitioned by team.** Most of what the pass seeds is team-scoped —
`organization_name`, `currency`, the document prefixes — so each team's first request after a deploy
does its own seeding pass, once. A single shared flag would have meant whichever team requested first
got its rows and no other team did.

### The version phone-home now has timeouts

The CRM's version check posts to `api.laravelcrm.com`. Guzzle defaults both `connect_timeout` and
`timeout` to "wait forever", and the install id is only recorded from a successful response, so **an
install with blocked egress to that host was putting a blocking outbound POST with no timeout in
front of every CRM page load.** The call is now capped at 2s connect / 3s total, with an hour-long
backoff marked before the request goes out, so a failure costs one attempt an hour rather than one
per page view.

No action is needed. It is worth knowing about if your page load times drop noticeably after this
upgrade, or if you had previously worked around the stall by blocking the CRM's own checks.

### `crm_usage_requests.visitor` values change format

The visitor pseudonym was hashed with `crypt($ip, config('hashing.encryption_key'))` — not a stock
Laravel config key, so the salt was null and PHP emitted a deprecation on every page view. It is now
a sha256 over your application key.

**Rows written before the upgrade will not match rows written after it for the same visitor.** There
is no migration and no backfill, and none is offered: the column is a pseudonym, not a key, and
nothing joins on it. The practical consequence is that any report counting *distinct* visitors over a
window spanning the upgrade double-counts anyone who appears on both sides of it. Pick a window that
starts after the deploy if the number has to be exact.

### Views to re-publish

If you have published views into `resources/views/vendor/laravel-crm`, the view finder prefers your
frozen copy, and this release changes:

| View | What you miss if you keep the old copy |
|---|---|
| `livewire/people/person-index.blade.php`, `livewire/organizations/organization-index.blade.php` | The removal of the always-blank `next_activity` column. The eager loading is in the components, not the views, so the pages are fast either way |
| `livewire/quotes/quote-index.blade.php`, `livewire/users/user-index.blade.php` | Column changes paired with the new eager loads |
| `layouts/app.blade.php` | The `version_compare()` badge fix and the cached settings read — a frozen copy keeps two raw queries on every page and keeps showing the wrong badge once the minor version passes 2.10 |
| `mail/templates/send-quote/*`, `send-invoice/*`, `send-purchase-order/*` (both `message` and `subject`) | The memoised settings read. The rendered output is identical; the query count is not, and these render once per row behind the index send buttons |

`php artisan laravelcrm:upgrade` names your drifted published views on every deploy (added in 2.4.1),
so this table is a cross-check rather than the only signal you will get.

## 2.4.2

### Republish assets — the pdf.js worker filename changed

The pdf.js worker is now emitted as `pdf.worker.min-<hash>.js` where it was
`pdf.worker.min-<hash>.mjs`. `.mjs` is in the default MIME map of neither nginx nor older Apache, so
those servers handed it over as `application/octet-stream` and the browser's strict module-script
MIME check refused to run it — every PDF preview failed with *"setting up fake worker failed"*, on a
build and a publish that were both correct.

`manifest.json` names the new filename, so **a host that upgrades without republishing assets has a
manifest pointing at a file that is no longer on disk, and every preview fails.** Nothing manual is
needed — the composer hook fires `laravelcrm:upgrade`, which republishes the assets and prunes the
stale content-hashed file — but this is the release where `composer update` on its own is not
enough. If you have not added the hook, see **One-time step for installs from before 2.4.0** above,
or run `php artisan laravelcrm:upgrade` by hand.

### No migrations; one config key removed

No tables and no columns. Unlike 2.4.1 this is *not* a "no new config keys" release: `portal.team_id`
is **removed** — see below. Run

```bash
composer update venturedrake/laravel-crm
php artisan laravelcrm:update
```

as usual. `laravelcrm:update` still advances the `db_version` marker, so run it even though there is
nothing to migrate — otherwise the system check reports the database as behind the code.

### Multi-tenant installs should upgrade promptly

Before this release the public portal rendered a document's page and PDF from **unscoped** settings.
The portal is anonymous — a signed link, no login — so `BelongsToTeamsScope` never engaged there and
the settings query returned every team's rows; `pluck()` keys by name, so whichever team the database
listed last supplied the organisation name, ABN, contact block and logo on every tenant's quotes,
invoices and purchase orders alike. On a `laravel-crm.teams` install, a customer opening one team's
emailed invoice link could be shown another team's branding. See the **Security** entry in
[CHANGELOG.md](../CHANGELOG.md) for the full description.

**No data migration is needed.** The fix is in the scope and the cache key — the portal controllers
pin the settings service to the document's own team before rendering — so upgrading and clearing the
application cache is sufficient. Single-tenant installs were never affected.

### `LARAVEL_CRM_PORTAL_TEAM_ID` is gone

`LARAVEL_CRM_PORTAL_TEAM_ID` and `config('laravel-crm.portal.team_id')` are **removed**. The variable
sat ahead of every other portal team signal as a hard single-tenant lock, which was harmless while
the portal served only roadmaps and wrong once the portal started answering "whose branding does this
invoice carry?" — a document states its own owner, and one env var was silently overruling it for
every team on the install. Every portal team signal is now derived from the request or the record.

**If you had that variable set,** delete it from your `.env` and drop the `team_id` line from
`config/laravel-crm.php` if you have published the config. Nothing reads it any more, so leaving it
in place is inert rather than harmful — but the portal will stop behaving as a single-tenant lock,
which is the point. A team whose board you do *not* want public should have its features marked
non-public rather than relying on the other teams being locked out.

Boards stay reachable per team at `/p/features/team/{id}`, and bare `/p/features` resolves as it has
since 2.4.0: the team in the URL, the board remembered in the visitor's session, the signed-in user's
current team, and finally — when exactly one team has a public board — that team.

### Views to re-publish

**None.** Neither change in this release touches `resources/views`, so a published view cannot hide
either fix. If you are coming from 2.4.0, the 2.4.1 table below still applies to you.

## 2.4.1

### No migrations, no new config keys

This is a patch release. It adds no tables or columns and no configuration keys, so

```bash
composer update venturedrake/laravel-crm
php artisan laravelcrm:update
```

is the whole upgrade. `laravelcrm:update` still advances the `db_version` marker, so run it even
though there is nothing to migrate — otherwise the system check reports the database as behind the
code.

### Multi-tenant installs should upgrade promptly

Before this release the settings cache was global while the query behind it was team-scoped, so on
a `laravel-crm.teams` install whichever team warmed the cache served its organisation name, ABN,
address and logo to every other team until the next settings write — on the settings screen and on
the documents those settings are rendered into. See the **Security** entry in
[CHANGELOG.md](../CHANGELOG.md) for the full description.

**No data migration is needed.** The fix is in the cache key, so upgrading and clearing the
application cache is sufficient. Single-tenant installs were never affected.

### Views to re-publish

If you have published views into `resources/views/vendor/laravel-crm`, the view finder prefers your
frozen copy, and this release changes:

| View | What you miss if you keep the old copy |
|---|---|
| `livewire/settings/setting-edit.blade.php` | The flat single-column settings page persists — harmless, and it keeps saving correctly |
| `livewire/kanban-board/record.blade.php`, `livewire/kanban-board/sortable.blade.php` | The client-side half of the drag-and-drop fix — the `data-record-id` marking. The server side resolves, filters and renumbers regardless, so the 500 and the authorization gap are closed either way |
| The quote / order / delivery / invoice / purchase-order `*-index`, `*-related-index`, `*-show` and `*-form` views | The Preview and **Get link** buttons — the routes exist, but nothing renders a link to them |
| `portal/quotes/show`, `portal/invoices/show`, `portal/purchase-orders/show` | The portal page keeps its own hand-built layout instead of rendering the record's PDF template |
| `layouts/app.blade.php`, `layouts/portal.blade.php` | The Get-link modal mount, the chrome-free portal document pages and the footer fix. `layouts/partials/nav-integrations.blade.php` no longer exists in the package at all |
| `pdfs/{modern,bold,compact,professional}/*` and the five classic `*/pdf.blade.php` | The null-date guards, the Bold header inset and its logo alignment |
| `mail/templates/send-invoice/message.blade.php` | The no-due-date variant of the emailed invoice body |

`php artisan laravelcrm:upgrade` now names your drifted published views on every deploy, so you no
longer have to work this out by hand — it md5-compares each published blade against the packaged
one and warns on both drifted views and views the package no longer ships. It only warns; it never
fails the run.

**The Settings → Templates thumbnails come back on their own.** 2.4.0 shipped without the five
template SVGs, so the picker rendered broken images; the artwork has moved out of the build output
directory and is republished by `laravelcrm:upgrade`, which `laravelcrm:update` calls first. Nothing
manual is needed.

**If you published the portal views**, note the three `crm-portal-*-line-items` Livewire components
are gone. A published portal view still referencing one will throw — remove the reference, or
re-publish the view.

### New routes

Five preview routes are added: `laravel-crm.quotes.preview` and its order, delivery, invoice and
purchase-order siblings. Each carries the same `can:view` guard as the download route it mirrors,
so they grant nothing your existing roles did not already allow.

## 2.4.0

### Migrations no longer need publishing

Migrations added from this release ship as real `.php` files inside the package, in
`database/updates`, and are loaded with `loadMigrationsFrom`, so a plain `php artisan migrate` runs
them. The existing `.stub` set is frozen and still published into your `database/migrations` —
**existing hosts are unaffected and keep the filenames they already have.**

The seven migrations this release adds all arrive this way, so there is nothing to publish for any
of them:

| Migration | What it does |
|---|---|
| `add_perf_notified_at_to_laravel_crm_monitors_table` | Performance-alert dedup timestamp |
| `add_recovered_notified_at_to_laravel_crm_monitors_table` | Recovery-alert dedup timestamp |
| `create_crm_user_invitations_table` | The user invitation lifecycle |
| `add_soft_deletes_and_last_sent_at_to_crm_user_invitations_table` | Resend + revoke support |
| `add_pdf_template_to_laravel_crm_tables` | Per-document PDF template choice |
| `add_start_at_to_laravel_crm_tasks_table` | Task start time |
| `change_quantity_to_decimal_on_laravel_crm_tables` | Decimal line item quantities (see below) |

One related change: newly published stubs are now stamped from a fixed `2024_01_01` epoch rather
than the moment of publishing, so a fresh install orders them correctly against the package-loaded
migrations. This only affects stubs that have never been published on a given host; anything
already in your `database/migrations` keeps its name.

### `laravelcrm:update` now fails loudly

It used to catch migration and seeder exceptions, print a warning, and still report
`Laravel CRM is now updated.` with exit code 0 — so a broken upgrade and a clean one looked
identical in a deploy log. It now prints an error and exits non-zero. **If your deploy script was
relying on it always succeeding, it will now stop where it previously carried on.** That is the
point, but check your pipeline for it.

### Line item quantities become decimal — six `ALTER TABLE`s

Line item `quantity` widens from `integer` to `decimal(15,3)` so a product can be sold by
weight or volume (3.5 Kg, 0.25 L). `php artisan laravelcrm:update` publishes and runs
`change_quantity_to_decimal_on_laravel_crm_tables`, which alters six tables:

| Table |
| --- |
| `crm_quote_products` |
| `crm_order_products` |
| `crm_deal_products` |
| `crm_invoice_lines` |
| `crm_purchase_order_lines` |
| `crm_delivery_products` |

**No data is lost.** `decimal(15,3)` strictly contains the old `INT` range
(2,147,483,647), so every existing row widens exactly. NULL quantities stay NULL. Nothing
needs backfilling.

**Plan for a brief write lock.** On MySQL each `ALTER` rewrites the table. On a small CRM
that is a fraction of a second; on a large install with millions of invoice lines, budget
for it or run the migration in a maintenance window. Postgres is likewise a table rewrite.

**Rolling back truncates.** `down()` puts the column back to `integer`, which discards the
decimal part of any quantity entered since. There is no lossless inverse — if you need to
roll back after users have entered fractional quantities, export those rows first.

**Verify afterwards.** `laravelcrm:update` now exits non-zero when a migration fails, so its
output can be trusted — but if you ran an older build of it, which swallowed failures and printed
success anyway, confirm the change actually landed:

```sql
SHOW COLUMNS FROM crm_quote_products LIKE 'quantity';   -- decimal(15,3), Null: YES
```

Three behaviour changes ride along with it:

- **The Order → Invoice and Order → Delivery quantity dropdown is now a number input.** A
  dropdown cannot express 3.5. The cap on the outstanding quantity is unchanged but has
  moved server-side — previously it existed only in the browser, so an over-invoice was
  reachable by anyone posting the form directly. On submit the remainder is recomputed
  from the order line and the invoices or deliveries already raised against it, so a
  request is checked against the database rather than against anything it sent.
- **The API `quantity` field is now a JSON number rather than an integer.** See
  [docs/api.md](api.md#quantity-accepts-decimals-up-to-3-places).
- **`$lineItem->quantity` now reads back as a PHP `float`, not an `int`.** The new
  `HasDecimalQuantity` trait casts it on `QuoteProduct`, `OrderProduct`, `DealProduct`,
  `InvoiceLine`, `PurchaseOrderLine` and `DeliveryProduct`. Host code doing
  `is_int($line->quantity)` or `$line->quantity === 2` breaks — a whole quantity of 2 now
  compares as `2.0`, so `===` against an integer is `false` and `is_int()` is `false`. Loose
  `==` and arithmetic are unaffected. Search your app for strict comparisons and `is_int` /
  `gettype` checks against a line item quantity before you deploy; `(int)` casts of your own
  still work but will silently truncate a fractional quantity, which is the thing this
  release exists to allow.

---

### The per-team backfill rewrites `pipeline_stage_id` on seven tables

**Teams installs only** (`laravel-crm.teams = true`) — single-tenant installs skip this
entirely. Take a database backup before running `laravelcrm:update`, as you should for any
release, and read this first if you have customised your pipelines.

`laravelcrm:update` runs a one-time backfill (`db_update_1201`) that gives every pre-existing
team its own copy of the CRM lookup data and pipelines, then **re-points existing records at
the per-team pipeline stages**. Without it, `/leads/create` on a teams install renders against
an empty per-team pipeline. The rewrite touches `pipeline_stage_id` on seven tables:

| Table |
| --- |
| `crm_leads` |
| `crm_deals` |
| `crm_quotes` |
| `crm_orders` |
| `crm_invoices` |
| `crm_deliveries` |
| `crm_purchase_orders` |

What it does, precisely:

- **Matching is by stage name**, within the same pipeline model — a global stage on the Lead
  pipeline maps only to the per-team Lead stage of the same name, never to a Deal stage that
  happens to share it.
- **Only rows belonging to the team being backfilled are touched.** Other teams' rows are left
  where they are.
- **A stage name with no per-team counterpart is left alone.** Nothing is nulled out and
  nothing errors — the record keeps pointing at the global stage. If you renamed stages per
  team, add or rename the missing stage first and re-run, otherwise those records stay on the
  global pipeline.
- **There is no `down()`.** This is a data migration, not a schema one, so rolling the package
  back does not put the ids back. Restore from a backup if you need to reverse it.
- **It is safe to re-run.** The lookup copy upserts on the team plus the row's own name, and
  the re-point matches on `pipeline_stage_id = <global id>`, which the first run has already
  replaced. Running `laravelcrm:update` (or `laravelcrm:v2`) twice adds no duplicate labels,
  tax rates, industries, types or pipelines, and re-migrates nothing.

> Builds before this one seeded the six lookup tables with a plain `INSERT`, so a team that
> already held them — which is every team created since 2.3.0 — got a **second full copy** the
> first time the backfill ran: duplicate labels, tax rates, industries and type lookups in
> every dropdown. If you ran a pre-release build of `laravelcrm:update` or `laravelcrm:v2`,
> check for duplicates by name before upgrading:
>
> ```sql
> SELECT team_id, name, COUNT(*) FROM crm_labels GROUP BY team_id, name HAVING COUNT(*) > 1;
> ```
>
> Repeat for `crm_tax_rates`, `crm_industries`, `crm_organization_types`, `crm_address_types`
> and `crm_contact_types`. Released 2.4.0 cannot produce them.

---

### PDF documents render through a template, and your published view still wins

Quotes, orders, invoices, deliveries and purchase orders now render through one of five shipped
templates, picked per document type under **Settings → Templates** and overridable per record on
the document form. The shipped default is **Modern**.

**If you customised a PDF by publishing and editing it** — `resources/views/vendor/laravel-crm/
invoices/pdf.blade.php` and its siblings, which was the only way to restyle a PDF before this
release — **nothing changes.** Those documents keep rendering through your file until you pick a
template on the Templates page. The check compares your published copy against the packaged one,
so an untouched `vendor:publish --tag=views` does not count as a customisation and gets the new
default like everyone else.

Two things to know:

- **Saving the Templates page retires the override**, for every document type at once — the form
  writes a choice for all five. The page warns you on any tab where an override is currently in
  effect. To keep your view *and* opt into the picker, choose **Classic**: it is a thin
  `@include` of the original views, so a published override of those files still applies.
- **Emailed PDFs now match downloaded ones.** `SendQuote`, `SendInvoice` and `SendPurchaseOrder`
  loaded the original views directly and so ignored the picker. They resolve the same way as the
  download routes now, including the published-view fallback.

---

### Every team gets its own public portal

The public feature board is team-aware on a `laravel-crm.teams` install. Each team's board lives
at `/p/features/team/{team_id}` — a shareable URL that works for an anonymous visitor, which is
the whole point of a public roadmap.

`LARAVEL_CRM_PORTAL_TEAM_ID` is **no longer required**. Bare `/p/features` resolves the board
from, in order: the team in the URL, the board remembered in the visitor's session, the signed-in
user's current team, and finally — when exactly one team has a public board — that team. So a
single-team install needs no configuration at all. Admins can copy the right link from the
**Public board** button on `/crm/features`.

If you *have* set `portal.team_id`, it still behaves exactly as before: a hard single-tenant lock
that 404s every feature outside that team. Unset it to give the other teams a portal.

> The variable is **removed** in 2.4.2. If you are upgrading past this release, see the
> **`LARAVEL_CRM_PORTAL_TEAM_ID` is gone** note in the 2.4.2 section above.

One behaviour fix comes with this: submitting a feature through the portal used to require the
submitter's `currentTeam` to match the board's team, which `403`'d every visitor who registered
through `/p/register` — they hold no host-app team. Submissions are now stamped with the board's
team regardless of the submitter's own.

---

### Authorization is now enforced on every mutating action

This release closes a long-standing gap: the UI has always advertised permissions via Blade
`@can` directives, but the Livewire components behind those buttons did not re-check them on the
server. Anyone who could reach a CRM page could invoke its actions directly over the Livewire
endpoint, regardless of role.

Every mutating Livewire action, every route group that previously had none, and the Blade
controls that trigger them now enforce the **same** Spatie permission the UI already advertised.
**No new permission name is introduced anywhere in this change** — the guards use permissions
that already ship in `LaravelCrmTablesSeeder`.

Concretely, the following now return `403` instead of silently succeeding:

- Livewire actions that create, update, delete, send, pay, complete, accept/reject, or reorder a
  record (leads, deals, quotes, orders, invoices, deliveries, purchase orders, products, tasks,
  people, organizations, notes/calls/meetings/lunches/files, settings lookups, chat, imports,
  templates, campaigns).
- Route groups that shipped ungated: `activities/*` and the deal/quote/order `products`
  sub-resources.
- Deleting a user who is not in your current team, and assigning the `Owner` role if you are not
  an Owner yourself.

Kanban cards are no longer draggable for users without the matching `edit` permission (previously
they were draggable and the drop 403'd), and mutating buttons/menu items are hidden rather than
shown-then-denied.

---

### 1. Re-run the permission seeder first — before deploying the new code

**This is the most likely cause of unexpected 403s after upgrade, and it is entirely
preventable.**

An install that has been upgraded over time without re-seeding may be missing permissions added
in later releases. Those permissions did not matter before, because the actions they gate were
not enforced. They matter now. The six families most commonly missing on long-lived installs:

| Permission family | Added for |
| --- | --- |
| `crm monitors` | Uptime / SSL monitoring |
| `crm features` | Feature voting & feedback portal |
| `crm email-campaigns` | Email marketing |
| `crm sms-campaigns` | SMS marketing |
| `crm chat` | Live chat |
| `crm activities` | Activity timeline |

If the permission row does not exist, no role holds it, and every action it gates will `403` —
**including for Owner and Admin**, because those roles are granted `Permission::all()` *as it
existed at the moment they were seeded*.

**Run this before you deploy:**

```bash
# 1. Creates any missing permission rows and re-grants them to the stock roles.
php artisan laravelcrm:update

# …or, to re-run only the seeder without also migrating:
php artisan db:seed --class="VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder" --force

# 2. Multi-tenant (laravel-crm.teams = true) installs ONLY — run after step 1.
#    laravelcrm:update now runs this for you; it is listed here so you can run it
#    on its own, and so the ordering is explicit.
php artisan laravelcrm:permissions
```

**Step 1 is the step that fixes missing permissions, and it is safe to re-run.** Every permission
is created with `Permission::firstOrCreate(...)` and every role with
`Role::firstOrCreate(...)->givePermissionTo(...)`, so existing rows are matched rather than
duplicated and existing grants are additive. Re-seeding revokes nothing and does not touch custom
roles.

**`php artisan laravelcrm:permissions` is not a substitute for step 1.** Despite the name it
creates no permissions — it copies the global CRM roles and their *existing* grants down to each
team, so it only does anything when `laravel-crm.teams = true`. Run on a single-tenant install it
prints `Teams config for multi-tenant support is not enabled.` and exits without changing
anything. If that message is all you saw, the missing permissions are still missing: go back and
run step 1.

Verify before you deploy:

```bash
php artisan tinker
>>> Spatie\Permission\Models\Permission::where('name', 'like', '%crm monitors%')->count();  // expect 4
>>> Spatie\Permission\Models\Role::where('name', 'Owner')->first()->permissions->count();   // expect all
```

---

### 2. Custom roles with view-only permissions lose actions they could previously perform

This is **correct behaviour**, not a bug — but it will generate support traffic, so audit it
before you upgrade rather than after.

If your team built custom roles under **Settings → Roles** (for example a "Read only" or
"Support" role granted `view crm leads` but not `edit crm leads`), those users could previously
still edit, delete, and reorder records by using the on-screen controls. The UI hid some of the
buttons; the server did not check. After this upgrade the server checks, so those actions return
`403` and the buttons no longer render.

Audit your custom roles before deploying:

```bash
php artisan tinker
>>> Spatie\Permission\Models\Role::where('crm_role', 1)
...     ->whereNotIn('name', ['Owner', 'Admin', 'Manager', 'Employee'])
...     ->get()
...     ->mapWithKeys(fn ($r) => [$r->name => $r->permissions->pluck('name')]);
```

For each custom role, ask whether the people holding it are *expected* to perform the actions
they have been performing. If yes, grant the matching `create` / `edit` / `delete` permission.
If no, nothing to do — the upgrade is the fix. Either way, tell those users first.

---

### 3. Installs with a trimmed `config('laravel-crm.modules')` will 403 on the disabled module

Every policy gates its methods on an `isEnabled()` helper:

```php
protected function isEnabled()
{
    if (is_array(config('laravel-crm.modules')) && in_array('deals', config('laravel-crm.modules'))) {
        return true;
    } elseif (! config('laravel-crm.modules')) {
        return true;
    }
}
```

Note the missing `else`: when `modules` **is** an array but does **not** contain the module,
`isEnabled()` returns `null`. Every policy method reads `if ($this->isEnabled() && $user->hasPermissionTo(...))`,
so a disabled module denies the action no matter which permissions the user holds — Owner
included.

Before this release that only affected surfaces which already called `authorize()`. Now it
affects every mutating action in the module. If you have trimmed `modules` in
`config/laravel-crm.php` but left the module's UI reachable, users will hit `403`.

Check what is enabled:

```bash
php artisan tinker
>>> config('laravel-crm.modules');
```

If a module is listed in your config it behaves normally. If it is absent and you still expect
people to use it, add it back to the array. If it is absent deliberately, confirm the module's
navigation is also hidden — the module toggles and the `@has{module}enabled` Blade directives
already handle this for the shipped views.

---

### What does not change

Verified against `database/seeders/LaravelCrmTablesSeeder.php`:

- **Owner and Admin lose nothing.** Both are granted `Permission::all()`
  ([`LaravelCrmTablesSeeder.php:569-570`](../database/seeders/LaravelCrmTablesSeeder.php#L569-L570)
  and [`:578-579`](../database/seeders/LaravelCrmTablesSeeder.php#L578-L579)), so they satisfy
  every new guard — provided the permission rows exist, which is exactly what step 1 above
  guarantees.
- **Manager and Employee lose nothing on the core entities.** Both hold
  `create` / `view` / `edit` / `delete` on leads, deals, quotes, orders, invoices, deliveries,
  purchase orders, people, organizations, contacts, activities, tasks, notes, calls, meetings,
  lunches, files, pipelines, and features.
- **No new permission name is introduced anywhere in this change.** Every guard reuses a
  permission that already ships in the seeder.

Three pre-existing gaps in the stock roles, unchanged by this release but worth knowing about
because they are now enforced rather than merely advertised:

- Neither Manager nor Employee holds any `crm monitors` permission — monitoring is Owner/Admin
  only by default.
- Employee holds `view crm chat` and `reply crm chat` only, and holds no `crm email-campaigns` or
  `crm sms-campaigns` permissions.
- Manager holds no `crm customers` permission (Employee does).

If your Managers or Employees are expected to run campaigns, manage monitors, or edit customers,
grant those permissions explicitly under **Settings → Roles** before you upgrade.

---

### Smaller breaking changes for host code

None of these affect a stock install. Each one only matters if you have extended, published or
integrated against the thing named.

**The `laravel-crm.users.sendinvite` route is gone.** It is the only named route dropped since
2.3.0 — user invitations now run through the `crm_user_invitations` table and its Livewire
surface. `route('laravel-crm.users.sendinvite')` throws `RouteNotFoundException`, so grep your
app for it. The package's own last caller,
`resources/v1/views/users/partials/card-invite.blade.php`, is unreachable dead code —
`resources/v1` is not registered as a view namespace anywhere — and is left as it is.

**`SystemCheck` middleware is replaced by the `crm-system-check` Livewire component.** It was
pushed onto the `crm` middleware group and did its work through flash messages; the banner is a
Livewire component now, backed by `SystemCheckService`. If you referenced
`VentureDrake\LaravelCrm\Http\Middleware\SystemCheck` in your own middleware stack, remove the
reference — the class no longer exists.

**`ModelProducts` renamed the `quantities` row key to `quantity_max`.** The per-row array the
line-item component builds carried a `quantities` array of `<select>` options; it now carries a
single `quantity_max` number, because the control is a bounded number input rather than a
dropdown. A **published** copy of `resources/views/vendor/laravel-crm/livewire/model-products.blade.php`
still iterates `$products[$index]['quantities']` and will render an empty control. Re-publish
that view with `--force` and re-apply your edits.

**`UserIndex` swapped its query-string filters.** The `#[Url]` properties `user_id` and
`label_id` are replaced by `role_id` (array) and `crm_access` (nullable string), matching the
filters the page actually offers. Bookmarked or generated links carrying `?user_id=` /
`?label_id=` are ignored rather than erroring.

**`CheckAmount`'s `subTotal()` / `tax()` / `total()` return a real `bool`.** They previously
returned `true` on a match and fell off the end returning `null` on a mismatch. Code doing
`=== false` against them never matched and now does; `=== null` no longer matches. Loose
falsy checks are unaffected.

**The REST API rejects `subtotal` and `total` on quote / order / invoice writes.** They are
computed from `line_items`. Sending them is now a `422` naming the cause, where a pre-release
build silently ignored them and returned recomputed numbers. See
[docs/api.md](api.md#subtotal-and-total-are-computed-and-rejected-on-input) for this and the
other API changes in this release — cross-team ids now `422`, `discount` / `tax` gained
`min:0`, and `POST /auth/token` can now return `429`.

---

### Release type

**This ships as a minor release, not a patch.** It changes observable behaviour for existing
users: actions that previously succeeded can now return `403`. Treat it as a minor version bump
with a prominent security note in your own release communications, and give your admins the
heads-up in step 2 before you deploy.
