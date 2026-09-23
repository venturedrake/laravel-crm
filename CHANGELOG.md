# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Documents
- Calendar
- Payments

## 2.4.5 - 2026-09-23

### Changed
- **Version bumped to `2.4.5`.** `config/package.php` still read `2.4.4`, which `SystemCheckService::normalisedVersion()`, the `db_version` marker written by `laravelcrm:update`, the update banner in `UpdateController` and the Settings middleware's `crm.settings-seeded.<version>` flag all key off — so the update banner and the "database is behind the code" check would both have compared this release against the previous one's number, and the seeding pass would never have been re-armed on deploy
- **Existing installs that use the Xero integration have to require `dcblogdev/laravel-xero` themselves.** It moved out of this package's `require`, so nothing pulls it in for you any more: upgrading to this release removes it unless your own `composer.json` asks for it, and a production `composer install --no-dev` will not bring it back from this package's `require-dev` either. The integration then goes quiet rather than erroring — connected or not, `XeroIntegration::installed()` is false, so the `XeroToken` observer is never registered, `XeroTenant` drops out of the web stack, `Product`/`Invoice`/`PurchaseOrder` sync no-ops and `laravelcrm:xero` exits with the install instruction. **There is no banner and no failed job**; the sign on screen is the settings page replacing "Connect to Xero" with an alert naming the missing package. Stored tokens and `crm_xero_*` rows are untouched, so `composer require dcblogdev/laravel-xero` restores the connection with nothing to re-authorise. See [docs/upgrading.md](docs/upgrading.md#245) for the deploy-order detail

### Fixed
- **The package can be installed again on Guzzle 8.** `composer require venturedrake/laravel-crm` failed outright in any app that had resolved `guzzlehttp/guzzle` 8 — which includes a stock Laravel 13.32+ app, since `laravel/framework` moved to `^7.8.2 || ^8.0` at that version. Composer reported the clash against this package's own `guzzlehttp/guzzle ^6.0|^7.0` first, so it read as a one-line bump, but widening that constraint alone did not fix it: the binding cap came in transitively from `dcblogdev/laravel-xero`, which pins `guzzlehttp/guzzle` to `^7.9.3` at most in **every version it has ever published**. There is no upstream release of that package compatible with Guzzle 8
  - **`dcblogdev/laravel-xero` is now optional** — `suggest` plus `require-dev` rather than `require`. Apps that use the Xero integration install it explicitly with `composer require dcblogdev/laravel-xero` and keep the behaviour they have today (at the cost of staying on Guzzle 7); everyone else is no longer held back by an integration they never enabled
  - New `src/Support/XeroIntegration.php` gates every entry point into the integration. `Dcblogdev\*` classes may now be absent at runtime, and while a `use` statement never autoloads, any *reference* to a missing class is fatal — so the `XeroToken` observer registration, the `XeroTenant` middleware, the `connect`/`disconnect` routes, `Product`/`Invoice`/`PurchaseOrder` service sync, the `laravelcrm:xero` command and the v1 invoice/purchase-order field partials all check `XeroIntegration::installed()` / `::connected()` first
  - The Xero routes stay registered when the package is absent, because the integrations tab strip and the app layout build links to `laravel-crm.integrations.xero` by name and would otherwise throw `RouteNotFoundException` on every page carrying the settings menu. They redirect back to the settings screen, which now explains what to install instead of offering a dead "Connect to Xero" button. `laravelcrm:xero` exits with the same instruction rather than a fatal error
  - Covered by `tests/Feature/Support/XeroIntegrationTest.php`, which resolves each file's `use Dcblogdev\...;` imports — plain, aliased or grouped — to their local aliases and fails the build on any file under `src/` or `resources/` that reaches one of those classes without gating on `XeroIntegration`. Matching the literal string `Dcblogdev` is not enough, since every PHP caller writes `Xero::isConnected()`, which carries none of it; neither is per-line comment handling, since a gate named only in a docblock is not a gate. Comments are stripped once with PHP's tokenizer and both halves of the check run against that, and the scanner is itself pinned against fixtures for every property it claims. Plus `tests/Feature/Livewire/XeroConnectNotInstalledTest.php`
- **`guzzlehttp/guzzle` now allows `^8.0`** alongside `^6.0|^7.0`

## 2.4.4 - 2026-09-11

### Changed
- **Version bumped to `2.4.4`.** `config/package.php` still read `2.4.3`, which `SystemCheckService::normalisedVersion()`, the `db_version` marker and the Settings middleware's `crm.settings-seeded.<version>` flag all key off — so the update banner and the "database is behind the code" check would both have compared this release against the previous one's number, and the seeding pass would never have been re-armed on deploy
- **The Owner filter searches server-side instead of rendering every user.** It handed `User::orderBy('name')->get()` straight to `<x-mary-choices>`, which json_encodes every option into an Alpine `x-data` block and renders a `<div>` per option — and Livewire then serialises and checksums that payload on every round trip. On an install with ~11,500 users a 25-row people page weighed **31.7 MB** and spent six seconds in render, and every keystroke, filter toggle and page change paid it again. Rendering `PersonIndex` against 3,000 users goes from **7,813,797 bytes to 94,868**. New `src/Livewire/Traits/HasUserOwnerFilter.php` replaces the 15 copy-pasted `users()` methods with one `#[Computed]` source capped at 20 rows and searched server-side; selected owners are merged back into that collection because MaryUI resolves a chip's label out of the same options it was handed, so an owner that falls out of the matches would otherwise render as a blank badge. Covered by `tests/Feature/Livewire/OwnerFilterSearchTest.php`
  - **`allow-all` ("Select all") is gone from the Owner filter** on all 15 screens. MaryUI throws when `allow-all` is combined with `searchable`, and selecting all would only ever have covered the rendered page of options anyway. `clearable` takes its place. **This is a visible change** to the filter drawer on the 12 index pages and the 3 boards
  - `Label`, `LeadSource` and `Role` filter options are narrowed to the `id` and `name` columns the list items actually render

### Fixed
- **Selecting an owner on nine screens threw an ambiguous-column `QueryException`.** `DealIndex`, `DealBoard`, `LeadIndex`, `LeadBoard`, `QuoteIndex`, `QuoteBoard`, `OrderIndex`, `InvoiceIndex` and `PurchaseOrderIndex` all left-join `crm_people` / `crm_organizations`, both of which carry their own `user_owner_id`, so the filter's unqualified column was ambiguous — a hard 500 on applying the filter, not merely a slow render. The column is now table-qualified

## 2.4.3 - 2026-09-11

### Changed
- **Version bumped to `2.4.3`.** `config/package.php` still read `2.4.2`, which both `SystemCheckService::normalisedVersion()` and the `db_version` marker key off — so the update banner and the "database is behind the code" check would both have compared this release against the previous one's number. A third consumer arrives with this release: the Settings middleware's `crm.settings-seeded.<version>` flag is stamped with the same value, and that stamp is what makes a deploy re-run the seeding pass exactly once without anyone remembering to clear a cache
- **The CRM index pages issue a bounded number of queries.** Every index table is a MaryUI `<x-mary-table>`, which resolves a dotted header key with `data_get($row, 'ownerUser.name')` — a lazy relation load *per row* unless the list query already loaded it. Add the relations each blade touches in its `@scope` cells and a 25-row page cost anywhere from 75 to nearly 500 queries: measured against the demo database, `/people` was 171 and `/orders` 486, and both are now 15 and 22. New `Traits/HasPrimaryContactDetails` gives `Person`, `Organization` and `Lead` eager-loadable `primaryEmail` / `primaryPhone` / `primaryAddress` morphOnes; `getPrimaryEmail()` and its siblings keep their signatures and read the loaded relation when there is one, so **every existing caller gets the saving with no code change**. Deal counts come from three `withCount` aggregates rather than hydrating the whole collection and filtering it in PHP three times, and the filter-drawer option queries are wrapped in `#[Computed]` so a debounced search keystroke reuses them. Covered by three new suites — `PersonIndexQueryCountTest` (a bounded query count for the people index), `IndexEagerLoadingTest` (every index column resolves without a further query) and `SettingsSeedingCacheTest` (the cold/warm middleware guard below)
  - Six read-paths that ran once or more *per row* now read the loaded collection instead of rebuilding a query: `CheckAmount\getItems()`, `Quote::orderComplete()`, `Order::invoiceComplete()`, `Order::deliveryComplete()`, `Product::getDefaultPrice()` and `Delivery::getShippingAddress()`. The `*_prefix` and `organization_name` settings move onto the memoised settings map in the four `get*IdAttribute()` accessors and the six send-document mail templates, which the per-row `<livewire:crm-*-send>` components render
- **The Settings middleware's seeding pass is gated behind a version-stamped, team-partitioned cache flag.** It is idempotent work that only matters after an install, an upgrade or a cache clear, and it cost 36 `crm_settings` queries plus an `information_schema` probe on *every* CRM page. The flag carries a 24h TTL, so a hand-deleted settings row still heals itself and the version check keeps its own cadence. It is partitioned by team because most of what the pass seeds is team-scoped — `organization_name`, `currency`, the document prefixes — and a single shared flag would mean whichever team made the first request after a deploy got its rows and every other team got none
- **The `next_activity` column is gone from the people and organizations index tables.** No column, accessor or relation of that name exists on either model, so the cell always rendered blank. **This is a visible change to those two tables**
- **`LogUsage` no longer hashes the visitor IP with `crypt()`** — see Fixed for why that call was also emitting a deprecation. The replacement is a sha256 over the application key, so **`visitor` values written before this release will not match ones written after it for the same visitor**: any unique-visitor count over `crm_usage_requests` that spans the upgrade is split across the boundary. There is no migration and no backfill — the column is a pseudonym, not a key

### Fixed
- **The version phone-home could block every page load indefinitely.** Guzzle defaults both `connect_timeout` and `timeout` to "wait forever", and `install_id` is only written from a *successful* response — so against an unreachable API the `! $installIdSetting` guard stayed true and the CRM put a blocking outbound POST in front of every single request, with no timeout to end it. The call now carries a 2s connect / 3s total timeout and an hour-long backoff that is marked *before* the request goes out, so a failure costs at most one attempt an hour rather than one per page view
- **The version badge lied once the minor version reached double digits.** The popover compared version strings with `<`, and `'2.2.0' < '2.10.0'` is false lexicographically — so an install would show *"Latest Version"* against a release that was in fact newer. It uses `version_compare()` now, matching `SystemCheckService`. The popover's two settings are also read off the cached map rather than two raw queries per page
- **`LogUsage` emitted a PHP deprecation on every page view.** It called `crypt($ip, config('hashing.encryption_key'))`, which is not a stock Laravel config key, so the salt was null — a deprecation today and a hard error in a future PHP release

## 2.4.2 - 2026-09-07

### Changed
- **Version bumped to `2.4.2`.** `config/package.php` still read `2.4.1`, which both `SystemCheckService::normalisedVersion()` and the `db_version` marker key off — so the update banner and the "database is behind the code" check would both have compared this release against the previous one's number

### Removed
- **`LARAVEL_CRM_PORTAL_TEAM_ID` / `config('laravel-crm.portal.team_id')`.** It sat ahead of every other signal as a hard single-tenant lock, which was harmless while the portal only served roadmaps and wrong the moment `PortalTeam::forDocument()` started answering "whose branding does this invoice carry?" — a document states its own owner, and one env var was silently overruling it for every team on the install, re-opening the leak below wherever it had been set. Every portal team signal is now derived from the request or the record. Boards remain reachable per team at `/p/features/team/{id}`; see [docs/upgrading.md](docs/upgrading.md)

### Fixed
- **Every PDF preview failed with *"setting up fake worker failed"* on nginx and older Apache**, on a correctly built and correctly published install. Vite emitted the pdf.js worker as `pdf.worker.min-<hash>.mjs`, and `.mjs` is in neither server's default MIME map, so the file was served as `application/octet-stream`; the browser's strict module-script MIME check then rejected both `new Worker(url, {type:'module'})` and pdf.js's own `import()` fallback, which is why there was no working path left and the drawer failed on every document. `vite.config.js` rewrites the emitted extension through `assetFileNames` rather than depending on each host's server config, so the worker inherits the `.js` mapping every server already has. The bytes are identical either way — module-ness comes from `{type:'module'}` and the `Content-Type`, not from the extension. **A host must republish its assets**, since `manifest.json` now names a different file; `laravelcrm:upgrade` does that and prunes the stale one, and it runs from the composer hook. See [docs/upgrading.md](docs/upgrading.md)
- **`BelongsToTeamsScope` now stands down on the three signed document routes.** Those links are authorised by their signature and the recipient has no account at all, so a staff member who happened to be signed in to team A was getting a 404 on team B's valid link — the route-model binding filtered the record out. Who is logged in should not decide whether a signed link opens

### Security
- **The public portal printed another tenant's branding on a document.** The portal is anonymous by design — a signed link, no login — so `BelongsToTeamsScope` never engaged there and every settings read on a quote, invoice or purchase-order page was unscoped: `pluck()` keys by name, so whichever team's row the database listed last supplied the organisation name, the ABN, the contact block and the logo for every tenant's documents alike, on the page and in the PDF behind the download button. The portal controllers now pin `SettingService` to the document's own team via `forTeam()` before rendering anything, which corrects every downstream reader of the shared scoped instance — `PdfContactDetails`, `PdfLogo` and the view composer included. A document that predates teams renders a blank From block rather than borrowing whoever sorted last
  - The cached settings map is partitioned on the same answer, so warming the page with one team's invoice cannot serve its branding to the next team's for the rest of the TTL. The pinned key deliberately collides with the one an authenticated member of that team produces, because the pinned query restates the scope's condition exactly

## 2.4.1 - 2026-09-06

### Added
- **A PDF preview drawer on every document.** A **Preview** action now sits beside every download button on quotes, orders, deliveries, invoices and purchase orders — on the show pages and on the index rows — rendering the real generated PDF in a slide-over rather than sending the user out through the browser's download tray and the OS file browser to check a document before sending it. The drawer renders with pdf.js, so what is on screen is the same bytes the download produces, not an HTML approximation of it
  - Five new `*/preview` routes (`laravel-crm.quotes.preview` and its four siblings), each carrying the same `can:view` guard as the download twin it mirrors, so preview grants nothing download did not
  - New `Http\Controllers\Concerns\ServesPdfDocuments` with `buildPdf()` / `pdfFilename()`: one generated PDF served two ways, so the preview and the download cannot drift apart as the templates change
  - `pdfjs-dist` is imported lazily, so the viewer chunk and its worker are only fetched the first time a preview is opened — a user who never previews downloads none of it
- **A "Get link" button** beside the preview button on the invoice, quote and purchase-order show pages and index rows, handing over the same 14-day signed portal URL that gets emailed to the customer — previously the only way to obtain that link was to send the document. New `Support\PortalLink` replaces six duplicated `generateUrl()` bodies, so all six surfaces sign the same URL with the same expiry. The modal is mounted once in the layout rather than once per row, and `confirm()` re-resolves the record through a model whitelist and the policy rather than trusting the component's own state, so a tampered payload cannot mint a link to a record the caller may not view
- **The portal document pages render the record's selected PDF template**, so the page a customer opens from an emailed link and the PDF they download from it are the same document — they had been separate hand-maintained layouts that drifted apart the moment the 2.4.0 template picker landed. New `Support\PortalDocument` renders the chosen template and embeds it in a self-sizing sandboxed iframe. The sandbox deliberately withholds `allow-scripts`: the templates emit record content unescaped, which is safe in a PDF renderer and is not safe in a browser document
- **The settings logo can be deleted.** `deleteLogo()` removes whichever logo is on screen, and a pending upload discards back to the saved logo rather than destroying artwork that is still printing on every PDF — there had been no way to remove a logo at all, only to replace it. Guarded with `authorize('update', Setting::class)`, matching the rest of the settings form
- **`laravelcrm:upgrade` warns about drifted published views.** It md5-compares every published blade under `resources/views/vendor/laravel-crm` against the copy the package ships, reporting both the views that have drifted and the ones the package no longer ships at all, skipping `PdfTemplateRegistry::LEGACY_VIEWS` (whose whole purpose is to be a preserved override). This is how `demo.laravelcrm.com` started 500ing on `/crm/users` with `Undefined variable $ownerUsers` — a published view frozen at an older component contract, with nothing anywhere in the upgrade path saying so. The check degrades to a single `warn()` and never fails a composer run, since it fires from a `post-autoload-dump` hook on production boxes
- **A shared `pdf_contact_details` setting** — **Settings → General → Document contact details** — filling the "From" block on quote, order, delivery and invoice PDFs. Only invoices had such a field before, which is why the other document types had no way to populate the block the 2.4.0 templates render. A document type's own `{type}_contact_details` setting still takes precedence, so existing invoice output is unchanged. Two gaps are deliberate, since closing either would change the visible output of a document that never rendered the block: **purchase orders** show no contact block on any of the 5 templates — their layouts pair a **Supplier** column with a **Delivery details** one rather than From/To, so `purchase_order_contact_details` resolves through the same chain but prints nowhere — and the **`classic`** template, which reproduces the pre-2.4.0 layout unchanged, renders the block on invoices only. Filling this field therefore affects quote, order and delivery PDFs on the `modern`, `bold`, `compact` and `professional` templates, and invoice PDFs on all 5. All 15 render call sites resolve through one `PdfContactDetails` helper
  - **Both fields in the chain can now be cleared.** They save on `!== null` / `has()` rather than the truthy check the neighbouring prefix and terms fields use, which makes a field write-once — clearing the textarea binds `''`, the `set()` is skipped, the old row survives, and `mount()` restores the stale text on the next visit while the PDFs keep printing it. That would have been a dead end on the shared key, and a sharper one on `invoice_contact_details`, which *shadows* the shared value: an admin who had ever filled the invoice field could never drop back to the shared block, which is exactly what that field's hint offers. An empty row is a meaningful value for both — `PdfContactDetails::for()` reads with `filled()`, so a cleared row resolves to null and falls through the chain. A field never filled on an install still writes no row at all

### Changed
- **Version bumped to `2.4.1`.** `config/package.php` still read `2.4.0`, which both `SystemCheckService::normalisedVersion()` and the `db_version` marker key off — so the update banner and the "database is behind the code" check would both have compared this release against the previous one's number
- **Settings → General is now split across tabs** rather than one flat column of ~27 unrelated controls. The page grouped organisation identity, localisation, tax defaults, seven ID prefixes and five blocks of document terms into a single card, so finding "invoice terms" meant scrolling past everything else — and it only grew as document settings accumulated. It now uses the same DaisyUI tab strip as **Settings → Templates**, with one tab per entity in the order records flow through the CRM: **General**, **Leads**, **Deals**, **Quotes**, **Orders**, **Invoices**, **Deliveries** and **Purchase orders** — each entity tab holding that entity's ID prefix beside its own terms. **General** keeps everything that belongs to no single entity: identity, localisation, tax defaults, the shared **Contact details** block (`pdf_contact_details`, which feeds quote, order, delivery and invoice PDFs alike), the two account-wide toggles, and the phone / email / address panels. Tabs are query-string-synced, so `/crm/settings?tab=invoices` deep-links; an unrecognised or module-disabled tab falls back to General. Each tab is hidden when its module is off, and a validation failure jumps to the tab holding the offending field rather than leaving Save looking inert
  - **There is no longer a Documents tab.** It had briefly held the shared contact-details block on its own; that block sits on **General** alongside the rest of the account-wide settings, and `?tab=documents` falls back to General rather than 404ing a bookmark
  - **Nothing about how the page saves changed.** It is still one form and one atomic save covering every panel, whichever tab is showing; no public property was renamed or removed, no public method was removed, and `save()` does not branch on the visible tab
  - **A host that published `resources/views/vendor/laravel-crm/livewire/settings/setting-edit.blade.php` keeps the flat single-column page** until it re-publishes the view — the view finder prefers the on-disk copy. The published copy keeps working because the component's contract is unchanged
  - New `VentureDrake\LaravelCrm\Support\Modules` helper exposes the `laravel-crm.modules` rule to PHP callers, and the 13 `@has*enabled` Blade directives now delegate to it instead of each carrying its own copy. Behaviour is identical, including the non-obvious case that an **empty** `modules` array means every module is enabled, not none — the 12 copies of the same rule in `src/Policies/` are deliberately left alone
- **The integration settings pages match the rest of the settings area.** Xero and ClickSend rendered under MaryUI underline tabs that navigated with `window.location.href`, which was the only place in the settings area not using the DaisyUI lifted-tab panel the rebuilt UI settled on. They now share an `integration-tabs` slot component over that panel, so the tab strip, the active state and the card edge match Settings → General and Settings → Templates
- **Emailed quote, invoice and purchase-order portal pages drop the navbar.** The brand logo, dark-mode toggle and **Login** button were rendered above a document a customer had opened from an emailed link, offering an account they do not have; the pages also inherited whatever theme the visitor's browser preferred, so a document meant to mirror a printed PDF could arrive dark. Those pages now render chrome-free and pin `data-theme="light"`. The portal's auth, feature board and invite pages keep their navbar — they are pages you navigate, not documents you read
- **The settings logo preview is framed like the fields around it** and bounded on both axes, so a tall or very wide upload no longer pushes the rest of the form down the page, and the remove button is laid out as a flex sibling rather than floating over the artwork it removes

### Removed
- The `crm-portal-quote-line-items`, `crm-portal-invoice-line-items` and `crm-portal-purchase-order-line-items` Livewire components, dead once the portal pages render the PDF template itself. A host that references one of them in a published portal view needs the reference removed
- `resources/views/layouts/partials/nav-integrations.blade.php`, replaced by the `integration-tabs` component
- The lang keys `record_ids`, `record_ids_hint` and `documents`, dead after the settings tab split

### Fixed
- **Settings → Templates rendered five broken images instead of the template artwork**, on every 2.4.0 install. The thumbnails lived in `public/vendor/laravel-crm/img/pdf-templates` — which is the Vite build output directory, and `vite.config.js` sets `emptyOutDir: true`, so the `npm run build` that preceded the 2.4.0 tag deleted all five SVGs and the tag shipped with no thumbnail artwork at all. This also made 2.4.0's *"thumbnails are served from the package, falling back to the copy inside the package"* fallback inert on arrival: `PdfTemplateRegistry::thumbnailFile()` checks the host's published copy and then the packaged one, and at 2.4.0 neither existed, so it returned null and the route 404'd for every slug. The artwork now lives in `resources/assets/img/pdf-templates` alongside the other static assets — a publish source mapped to the same destination, and one the build cannot reach — so `THUMBNAIL_DIR` and the published lookup are unchanged and a build can no longer delete it
- **Downloading a quote, order or delivery PDF returned a 500 on every template except `classic`.** The themed templates added in 2.4.0 print a "From" contact block guarded on a `$contactDetails` variable, but only the invoice and purchase-order call sites ever passed one — the commit that repointed all 14 download / send / portal call sites at `PdfTemplateRegistry::viewForModel()` changed the view name and left the view-data array alone. Quotes, orders and deliveries therefore rendered a blade reading a variable nobody supplied, and `modern` is the default template, so a stock 2.4.0 install failed on `Undefined variable $contactDetails` at the first quote download. All six affected call sites now pass the variable, and all 16 themed blades guard the read with `?? null` so a host that published its views at 2.4.0 — and whose on-disk copy the view finder prefers over the package's — is fixed by `composer update` alone, without republishing
- **Portal quote and invoice downloads never showed the organisation's address.** Both call sites guarded on `$quote->organisation` / `$invoice->organisation`, but the relation is spelled `organization` on both models, so Eloquent resolved the British spelling to `null`, the branch never ran, and `organization_address` was always passed as null. Portal-download PDFs now render the organisation address block, matching the authenticated download and the portal purchase-order controller. **This changes the visible output of portal PDFs** for records whose organisation has a primary address
- **Template previews showed the invoice contact block for every document type.** `TemplatePreviewController::sampleData()` hardcoded the `invoice_contact_details` setting, so previewing a quote, order, delivery or purchase-order template displayed whatever the invoice was configured to say. It now resolves per document type
- **A single null `due_date` took down the entire invoices index** with `Call to a member function diffinDays() on null`. `crm_invoices.due_date` is nullable and the V2 API accepts a null value for it, so one such record 500'd the page for every user of the install, not only for whoever created it. The date is guarded now on the invoices index and show blades, in the classic PDF and in all four themed templates; the send-invoice mail body splits into due-date and no-due-date variants rather than interpolating a formatted null; and four `$model->date->format(...) ?? null` reads become nullsafe — the `??` there never fired, because the method call on null fatalled before the coalesce was ever reached
- **Kanban drag-and-drop 500'd on non-record sibling ids.** Each card is followed in the DOM by its own delete-confirm `<dialog>`, so `onEnd` collected arrays like `['3', 'modalDeleteLead3', '7']` and the server ran `Lead::find('modalDeleteLead3')->update()`, which fatalled; the interleaved dialogs also inflated `pipeline_stage_order` to 1, 3, 5 rather than 1, 2, 3. Cards now carry `data-record-id` and the server side resolves, filters and renumbers the batch in a transaction. **This also closed an authorization gap** — `onStageSorted` authorized only the first resolvable record in the batch and then updated every id it had been handed, so a record the user had no right to edit was reordered as long as an editable one led the list
- **Deleting a product made every document that referenced it permanently unviewable.** `Product` soft-deletes, but the line-item `belongsTo` relations did not `withTrashed()` and the show views guarded on the foreign-key column rather than on the relation, so the column was still populated, the relation resolved to null, and `->product->name` fatalled on a quote, order or invoice that had been fine for months. Fixed across `QuoteProduct`, `OrderProduct`, `InvoiceLine`, `PurchaseOrderLine`, `DealProduct` and `DeliveryProduct::orderProduct()`, restoring the historic line description on the show views, in all 14 PDF templates and in the Xero sync. The product pickers still query `Product::` directly, so a deleted product stays out of selection lists — it is readable on the documents that already reference it, not choosable on new ones
- **The portal page's line pitch did not match the PDF's.** DomPDF multiplies the font's own height by a unitless `line-height` rather than treating it as the line box height, so Modern's declared `1.2` renders at roughly 1.79em there and 1.2em in a browser — the same template producing visibly different line spacing on the portal page and in the downloaded file. The correction is applied on the way out by `PortalDocument` rather than in the template CSS, since a template-side fix would inflate the PDF by the same factor it removes from the page. Covers `modern`, `bold`, `compact` and `professional`; `classic` declares no unitless line-height and is excluded
- **The Bold header band lost its inset in a browser.** The band is a `<table>` carrying its inset as padding, and CSS ignores a table's own padding under `border-collapse: collapse` — DomPDF applies it anyway, browsers drop it, so the portal page rendered the title flush against the left edge of the band while the PDF looked right. The inset is declared per cell now, which both honour. The band's logo plate is also `vertical-align: middle`: as an `inline-block` it sat on the text baseline and rode about 5px above centre. Both changes were verified byte-identical in the PDF output
- **The portal footer** now spans the full viewport width, is pinned to the bottom of the page rather than floating under short documents, and no longer breaks its credit line across two lines
- **The contact-details hint rendered as literal HTML entities.** The field passed `hint="{{ ... }}"`, so Blade escaped the string and MaryUI escaped the result again — a hint containing a quotation mark arrived on screen as `&amp;quot;`. Binding with `:hint` hands the raw string over once; `invoice_contact_details_hint` carried the identical latent bug and is fixed with it
- **The preview drawer distorted pages when zoomed.** Tailwind's `max-w-full` capped the canvas width while the inline height stayed at the zoomed value, so at 200% an invoice rendered around two-thirds width at full height. The opening scale is also picked by `fitScale()` from the measured panel width rather than a fixed guess, so a document opens fitted rather than arbitrarily cropped
- **The preview drawer failed every render with `Cannot read from private field`.** Alpine's deep reactivity wraps component state in a `Proxy`, and pdf.js brand-checks its `#private` fields against the real instance, so every proxied call was rejected. The document, the viewer module, the abort controller and the render token now live in the `Alpine.data()` closure instead of on reactive state. This is why the drawer header rendered correctly and only `getPage()` died — `numPages` reads a plain property and needs no brand check
- The PDF preview button no longer renders a second `o-eye` beside the show button on index rows; it uses `o-document-magnifying-glass`

### Security
- **The settings cache was global in front of a team-scoped query.** On a teams install, whichever team warmed the cache served its organisation name, ABN, address and logo to every other team until the next settings write — on the settings screen itself and on every invoice, quote and PDF those settings render into. `SettingService::cacheKey()` partitions by the current team, with a generation counter so `forgetCache()` still reaches every team's entry on cache drivers that cannot tag or scan. Alongside it: `SettingsComposer` drops its own second, never-invalidated cache and its static; the three team-switch sites flush the cache and unset the now-stale `currentTeam` relation; and the settings services bind as `scoped` rather than `singleton`, so memoised state cannot outlive a queued job or survive between requests under Octane
  - **`CurrentTeamController`'s non-Jetstream fallback switched a user into any team id it was handed.** It verifies membership of the target team before switching now

## 2.4.0 - 2026-08-09

### Added
- **User invitations.** A CRM admin can now invite someone by email instead of creating the account themselves and handing over a password out of band. the **Invite** button on `/crm/users` sends a `UserInvitationNotification` carrying an accept link; the invitee follows it and either signs in as an existing host user or sets a password and gets a new one, landing with the CRM role and team the inviter chose. The old `laravel-crm.users.sendinvite` route did none of this and is gone — see Removed
  - New `crm_user_invitations` table and `UserInvitation` model, route-keyed on a 64-character `code`, with `isPending()` / `isExpired()` / `isAccepted()` / `isValid()` state predicates and an observer stamping `external_id` and `code` on `creating`
  - `UserInvite` Livewire form on the users index, offering only the roles the caller may hand out (`Role::assignableBy()` — see Security)
  - Public accept routes `users/invitations/{code}/accept` (`GET` and `POST`), deliberately outside both `auth.laravel-crm` and the CRM-access check — a logged-out invitee has to reach them, and an invited user carries `crm_access = 0` until they accept
  - A **Pending Invitations** tab on the users index alongside **Registered Users**, with resend (stamping `last_sent_at`) and revoke (soft delete) row actions
- **Five PDF templates, pickable per document type and per record.** Invoices, orders, quotes, purchase orders and deliveries render through one of `modern` (the new default), `classic`, `bold`, `compact` or `professional` (`PdfTemplateRegistry::SLUGS`) rather than the single hardcoded layout each had before
  - **Settings → Templates** sets the default per document type, with a tab per type, packaged thumbnails and a live full-page preview rendered from sample data through `TemplatePreviewController`
  - A **PDF template** select on each of the five document create / edit forms pins a template to that record; the blank option means "follow the Settings default". Every later download, email send and portal render of that record resolves through `PdfTemplateRegistry::viewForModel()`, so all 12 render call sites agree on one answer
  - A host that customised its PDFs the pre-picker way — by publishing and editing `resources/views/vendor/laravel-crm/invoices/pdf.blade.php` and its siblings — keeps that view; see Fixed
- **Every team gets its own CRM lookup data and pipelines.** On a teams install the pipelines, stages, labels, tax rates, industries and the three type lookups were global, so `/leads/create` rendered against an empty per-team pipeline and no team could tailor its own stages. The one-time `db_update_1201` backfill — run by `laravelcrm:update`, `laravelcrm:install` and `laravelcrm:v2` — copies the lot to every pre-existing team, then re-points existing records at that team's stages, rewriting `pipeline_stage_id` on `crm_leads`, `crm_deals`, `crm_quotes`, `crm_orders`, `crm_invoices`, `crm_deliveries` and `crm_purchase_orders`. Matching is by stage name within the same pipeline model, only the backfilled team's rows are touched, a stage with no per-team counterpart is left alone, and there is no `down()`. **Take a database backup before upgrading a teams install** — see [docs/upgrading.md](docs/upgrading.md)
- **A host-team switcher in the CRM header.** Under `config('laravel-crm.teams')` the app layout gained a dropdown listing the teams the signed-in user belongs to, with a per-team `POST` switch form and a check mark on the current one, so an operator no longer has to leave the CRM to change team. It works on hosts without Jetstream — team detection falls back to the user's `ownedTeams()` relation. Tenant teams are labelled **Enterprise**
  - **`+ New team` creates the team from inside the CRM.** New `HostTeamController` and a quick-create form write the row through the host application's own team model and switch the user onto it, so a host whose `teams.create` route sits behind its own middleware (Jetstream's `hasNoTeam`, for instance) no longer blocks the link. New `host_team_model` config key (`LARAVEL_CRM_HOST_TEAM_MODEL`) overrides the auto-detection
- **Tasks take an optional start date and time.** `start_at` on `crm_tasks`, surfaced on the create / edit form, the inline task item, the related-task list and the show page, so a task can describe a window rather than only a deadline
- **Role and CRM-access filters on the users index**, replacing the owner and label filters the page had inherited from the CRM entity indexes — neither matched anything a user row actually carries. See Changed for the query-string break
- **A shared tabs bar across the integration settings pages.** Xero and ClickSend each rendered inside the general settings sidebar shell, so moving between them meant navigating back out to Settings. They now share `layouts/partials/nav-integrations.blade.php`, and the two items are dropped from the settings sidebar
- **Persian (`fa`) translations** — a full `resources/lang/fa` set alongside the existing `en`, `en_au` and `en_gb`
- **Performance and recovery alerts stop re-firing on every monitor check.** `perf_notified_at` and `recovered_notified_at` on `crm_monitors` rate-limit the slow-response and recovered notifications the way `notified_at` already did for downtime — a monitor sitting just over its performance threshold sent one email per check, so a 5-minute monitor mailed its owner 288 times a day. Both windows are configurable (`monitoring.perf_alert_rate_limit_minutes` and `monitoring.recovered_alert_rate_limit_minutes`, 60 minutes each by default), and a recovery alert is only sent when a down or slow alert actually preceded it
  - **`monitoring.max_response_bytes`** (`LARAVEL_CRM_MONITORING_MAX_RESPONSE_BYTES`, 5 MB) caps the response body `MonitorCheckService` will read, so a monitored endpoint streaming an unbounded response cannot exhaust the queue worker's memory
- **Every foreign key on an API write is now validated against the caller's team.** New `ScopedExists` rule builds an `exists` rule constrained to the current team when `laravel-crm.teams` is on. The bare Laravel `exists` rule queries the database directly and so bypasses `BelongsToTeamsScope`, which let an authenticated caller reference another tenant's `external_id` and have it accepted. Applied across every `Store*` / `Update*` request for leads, deals, quotes, orders, invoices, people and products; a table with no `team_id` (labels, lead sources) falls back to a bare `exists` rather than producing a SQL error, and a caller holding no current team is failed rather than passed. `OwnerInCurrentTeam` was rewritten onto the same footing. Cross-team ids now present as a `422`
- **`POST /api/crm/v2/auth/token` is throttled per account as well as per IP** — `api.token_attempts_per_account` (5) failed attempts per `api.token_attempts_decay_seconds` (600), so credential stuffing spread across many IPs against one email address no longer gets unlimited attempts. Exhausting it returns `429`. See [docs/api.md](docs/api.md)
- **`php artisan laravelcrm:upgrade`** — the safe, database-free half of an upgrade: republishes built assets, prunes stale content-hashed build output, publishes Flasher assets and clears cached config/routes/views. It never opens a database connection and never prompts, so it is safe to run unattended from a composer hook on a production box mid-build. `laravelcrm:install` now adds `@php artisan laravelcrm:upgrade --ansi` to the host application's `post-autoload-dump` scripts, so every later `composer install` / `composer update` republishes assets on its own — the same hook Filament uses, and for the same reason (it fires on `install` as well as `update`, so a production `composer install --no-dev` is covered). Existing installs add the line once by hand; see [docs/upgrading.md](docs/upgrading.md)
  - Pruning matters because Vite output is content-hashed and `vendor:publish --force` adds but never removes, so a host accumulated one `app-<hash>.js` per release it had ever installed while `manifest.json` named exactly one of them. Only top-level files in `public/vendor/laravel-crm/assets` with no counterpart in the package are removed — `img/`, `fonts/`, `libs/` and `css/` are untouched
- **New migrations no longer need publishing.** They ship as real `.php` files in `database/updates`, loaded with `loadMigrationsFrom`, so they reach every host through a plain `php artisan migrate` with no publish step, no hand-picked order number and no entry in the service provider. The existing 134-entry `.stub` publish array is frozen and still published, so existing hosts are entirely unaffected and keep the filenames they already have. `loadMigrationsFrom` previously pointed at `database/migrations`, which holds only `.stub` files and is therefore invisible to the migrator's `*_*.php` glob — the call was inert
  - **All seven migrations added in this release now take that route**, where they had been left behind as `.stub` files with publish-array entries 135–141 — the exact arrangement this change exists to retire. A host following the documented `composer update && php artisan migrate` would have received none of them and then hit `Column not found` on the first task save (`start_at`) or PDF template choice. `SystemCheckService` already scanned `database/updates` for unrun migrations; it simply had nothing to find
- **A `db_version` marker**, stamped by `laravelcrm:install` and by `laravelcrm:update` on success, so the app can tell that the code is ahead of the database. The existing `version` setting cannot carry this: `Http/Middleware/Settings` overwrites it with `config('laravel-crm.version')` on the first web request after a deploy, so it always reads as current no matter what the database has had applied
- **`upgrade_guide_url` config key** (`LARAVEL_CRM_UPGRADE_GUIDE_URL`), defaulting to <https://laravelcrm.com/docs/2.x/upgrading>. Every "Upgrade guide" link in the CRM — both on the updates page and the one in the system check's upgrade-required banner — now points there instead of at the GitHub repository. `docs_url` is unchanged and still carries the "View version X details" link, which is a release-notes link rather than an install one
- **Both update commands are now printed in the UI.** The updates page gained a "How to update" card showing `composer update venturedrake/laravel-crm` and `php artisan laravelcrm:update`, and flags a database that is behind the code; the system check banner now shows the command to run rather than only linking to a page about it
- **`laravelcrm:sample-data` now covers this release's new surfaces**, so a demo or dev install shows them rather than leaving them empty: a start date and time on ~40% of tasks, a `pdf_template` across all five document types (the majority left null, so the "follow the Settings default" path is represented too), fractional quantities on ~30% of line items, and `crm_user_invitations` rows in each of the pending, expired and accepted states. The seeder also gained its first execution coverage — `tests/Feature/Seeders/SampleDataSeederTest.php` runs it end to end at 2% scale and asserts money stored as integer cents, quantities surviving the copy from quote to order to invoice and delivery, and every document sitting in a stage of its own pipeline

### Changed
- **Version bumped to `2.4.0`.** `config/package.php` still read `2.3.0`, which both `SystemCheckService::normalisedVersion()` and the `db_version` marker key off — so the update banner and the "database is behind the code" check would both have compared this release against the previous one's number
- **Breaking (API): `subtotal` and `total` are rejected on quote / order / invoice writes.** Both are computed from `line_items`, `discount`, `tax` and `adjustments`. They had been dropped from the validation rules, so a client still sending its own authoritative totals was silently ignored and got recomputed numbers back with no error — the kind of break that surfaces weeks later when figures stop reconciling. They are now `prohibited` with a message pointing at `line_items`, so it presents as a `422` naming the cause. `prohibited` passes for an absent or `null` value, so a payload that never sent them is unaffected. See [docs/api.md](docs/api.md)
- **`FeatureComment` goes back to `$guarded = ['id']`.** It had swapped to an explicit `$fillable`, making it the only one of the five `Feature*` models not using `$guarded`. It protected nothing — `FeatureService::comment()` builds its array from explicit typed parameters, so no user-controlled array reaches `create()` — while silently dropping `external_id` for host code that mass-assigns its own
- **`laravelcrm:update` no longer swallows failures.** `migrate` and the base seeder were wrapped in try/catch, downgraded to warnings, and the command still printed `Laravel CRM is now updated.` and exited 0 — so a broken upgrade and a clean one produced the same deploy log and the same exit code, and a deploy script's `&&` chain carried on over a half-applied schema. Both are now fatal: the command prints an error and returns a failure exit code, and `db_version` is stamped only on the success path. **A deploy script that relied on this command always succeeding will now stop where it previously carried on.** It also gained `--force` for non-interactive use, calls `laravelcrm:upgrade` first so one command by hand still does everything, and had its description corrected from `Install Laravel CRM package`
- **`laravelcrm:update` runs the lookup-data seeders operators used to run by hand** — `laravelcrm:lead-sources`, and on teams installs `laravelcrm:permissions`, `laravelcrm:labels`, `laravelcrm:addresstypes`, `laravelcrm:contacttypes` and `laravelcrm:organizationtypes`. All are idempotent (`updateOrInsert` / `firstOrCreate` / existence-checked inserts), so re-running revokes nothing and duplicates nothing
- **`SystemCheckService` no longer depends on a hand-maintained list to notice that an update is due.** `DB_UPDATE_REQUIRED` now fires on any of three signals: a `db_update_*` flag still at `0` (as before), a missing or stale `db_version`, or an unrun migration belonging to this package (guarded on `repositoryExists()`, so a host that has never migrated still reports `UPGRADE_REQUIRED` instead). The pending-migration check is scoped to the package's own files — those loaded from `database/updates`, plus published stubs matched back to the `.stub` set by filename — so an unrun migration belonging to the host application or another package cannot raise a CRM banner telling the operator to run `laravelcrm:update` over it. The `DB_UPDATES` list stays as the *worklist* of data backfills, but is no longer load-bearing for detection — it was frozen at `db_update_1201` and nothing complained
- Published migration stubs are stamped from a fixed `2024-01-01` epoch rather than the moment of publishing. `date('Y_m_d_His', strtotime("+$order sec"))` meant a stub published today sorted **after** a package-loaded migration authored earlier in the same year, so on a fresh install the migrator would try to alter tables that did not exist yet. Hosts that have already published keep their existing filenames — the glob-reuse branch returns the published path unchanged
- **Line item quantities accept up to 3 decimal places** — a product sold by weight or volume (3.5 Kg, 0.25 L) can now be quoted, ordered, invoiced, delivered and purchase-ordered at its real quantity. Previously `quantity` was an `integer` column behind a bare `<input type="number">`, so a fractional quantity could not be entered at all.
  - `quantity` widens from `integer` to `decimal(15,3)` on `crm_quote_products`, `crm_order_products`, `crm_deal_products`, `crm_invoice_lines`, `crm_purchase_order_lines` and `crm_delivery_products`. The new range strictly contains the old `INT` range, so every existing row widens losslessly and NULLs stay NULL — see [docs/upgrading.md](docs/upgrading.md) for the write-lock note and the fact that rolling back truncates
  - New `Support\Quantity` helper and `HasDecimalQuantity` model trait: quantities round to 3dp on write and read back as floats on every driver, so a whole quantity still renders as `2` rather than `2.000`
  - **The Order → Invoice and Order → Delivery quantity dropdown is now a bounded number input.** The dropdown was built by an integer `for` loop over the remaining quantity, so an order line of 2.5 could only ever be invoiced as 2, leaving 0.5 outstanding forever. The cap on the outstanding quantity is now enforced **server-side** as well — previously it lived only in the browser, so an over-invoice was reachable by posting the form directly. The submitted quantity is checked against a remainder recomputed from the order line and the invoices or deliveries already raised against it, not against the row's own `quantity_max` (a public Livewire property, and so whatever the caller sends back). The delivery form, which ran no validation at all, now validates its quantities
  - **Breaking (API response type):** `quantity` in the quote / order / invoice API responses was cast to an integer and is now a JSON number that may come back fractional. Clients decoding it into an `int` will truncate. The request side is a widening — `integer|min:1` becomes `numeric|min:0.001|max:999999999` with at most 3 decimal places, so every previously valid payload still passes
  - Xero invoice and purchase order sync now sends `Quantity` as a JSON number (Xero's `LineItem.Quantity` takes 4dp, so 3dp fits). Xero recomputes `LineAmount` itself and may round a fractional line differently from the amount stored here
- **Breaking: the users index swapped its query-string filters.** The `#[Url]` properties `user_id` and `label_id` are replaced by `role_id` (array) and `crm_access` (nullable string), matching the filters the page now offers. A bookmarked or generated `?user_id=` / `?label_id=` link is ignored rather than erroring. See [docs/upgrading.md](docs/upgrading.md)
- **Breaking (API): `discount` and `tax` gained `min:0`** on quote / order / invoice writes, alongside the `subtotal` / `total` change above. A negative value in either previously passed validation and inflated the computed total past the sum of the line items
- The users index and the Templates settings page moved from MaryUI's tabs component to DaisyUI `tabs-lift` radio tabs, matching the rest of the rebuilt UI. The active tab's radio is checked on first render, so the default tab is highlighted before any interaction rather than after the first click
- The **General Settings** sidebar item is matched exactly now, in both the live sidebar and the legacy v1 settings side-card. MaryUI's activate-by-route falls back to a URL prefix match, so `/crm/settings` stayed highlighted while sitting on `/crm/settings/templates` or `/crm/settings/feature-statuses`

### Removed
- `create_audits_table.php.stub` — a leftover from when this package depended on `owen-it/laravel-auditing`. It has no entry in the publish array, so it could never reach a host, and the dependency itself is long gone
- `LaravelCrmUpdate::checkQuantityColumns()` — a release-specific check that existed only to compensate for `migrate` failures being swallowed. Now that a failed migration exits non-zero, the general mechanism covers it; the migration itself stays pinned by `tests/Feature/QuantityMigrationTest.php`
- **Breaking: the `laravel-crm.users.sendinvite` route.** The only named route dropped since 2.3.0 — invitations run through the `crm_user_invitations` table and its Livewire surface now. `route('laravel-crm.users.sendinvite')` throws `RouteNotFoundException`, so host code referencing it needs updating. The package's own last caller, `resources/v1/views/users/partials/card-invite.blade.php`, is unreachable dead code (`resources/v1` is registered as a view namespace nowhere) and is left as-is
- **Breaking: the `SystemCheck` middleware.** It was pushed onto the `crm` middleware group and reported through flash messages; the banner is the `crm-system-check` Livewire component backed by `SystemCheckService` now. A host referencing `VentureDrake\LaravelCrm\Http\Middleware\SystemCheck` in its own stack needs the reference removed — the class is gone

### Fixed
- **`laravelcrm:sample-data --fresh` did not clear `crm_user_invitations`.** The table was absent from the truncate list, so a re-run doubled the invitations and left the earlier ones pointing at a sample user the same run had just hard-deleted — the delete could not be blocked, since the truncate step disables foreign key constraints around it, so the dangling `invited_by` simply persisted
- **Sample deals landed with no pipeline stage, and sample document totals could read a cent short.** Two long-standing seeder faults that only surfaced once the seeder had test coverage:
  - `LaravelCrmPipelineTablesSeeder` writes the Deal stages with `firstOrCreate(['id' => n], ...)`, and `id` is guarded on the model, so the rows it means to create at 35/36/37 are created at 10/11/12 instead — the ids Pending, Closed Won and Closed Lost are keyed on, which then match and are skipped. The Deal pipeline came out with four stages rather than seven and roughly two thirds of the sample deals had nowhere to sit. `ensureDealPipelineStages()` now ensures all six non-Draft stages by name rather than only the three intermediate ones
  - Line amounts and per-line tax were rounded in dollars, which put fractional-quantity lines up to a cent away from the figure `CheckAmount` recomputes from `quantity x price` and flagged the document as broken on the index. Both are now rounded to whole cents, the way the columns store them
- **`TeamObserver::seedCrmDataForTeam()` gave every existing team a second copy of six lookup tables.** Labels, organization types, address types, contact types, industries and tax rates were copied with an unconditional `INSERT`, while only the pipelines/stages block below them used `updateOrInsert` — so the `db_update_1201` backfill, which calls the helper for *every* team, duplicated the lot on any teams install upgrading from 2.3.0, where `TeamObserver::created()` had already seeded them. The result was two of every label, tax rate, industry and type in every dropdown, with fresh UUIDs and no clean de-dup key afterwards. `laravelcrm:v2` was worse: it calls the same helper with no `db_update_*` marker guard at all, so each run added another set. All six now upsert on `team_id` + the row's `name`; `external_id` and `created_at` are written on first insert only, so a re-run neither re-keys a row the host is already linking to nor resurrects one the team deleted. The pipelines and stages block was moved onto the same helper for the same reason — it was idempotent on row count but re-minted `external_id` on every run. Fresh installs were never affected: `laravelcrm:install` runs the backfill before any team holds data. Pinned by `tests/Feature/Observers/TeamObserverSeedTest.php`
  - The two comments asserting the helper was already idempotent — in `LaravelCrmUpdate` and `LaravelCrmV2` — described only the pipelines block and are corrected
- **Every team now gets its own public portal, and `LARAVEL_CRM_PORTAL_TEAM_ID` is optional.** Under `laravel-crm.teams` the portal was hard-wired to one team: unset, the feature board and every public feature 404'd outright; set, only that team had a portal at all. A public roadmap is read by a team's customers, who are anonymous and carry no `currentTeam`, so the team cannot be inferred from the session — new `Support\PortalTeam` resolves it from a team-addressable board URL (`/p/features/team/{id}`), the board remembered in the visitor's session, the signed-in user's current team, or the only team that has a board, in that order. A single-team install needs no configuration; `portal.team_id`, when set, still behaves as the single-tenant lock it always was. The admin features index gained a **Public board** button carrying the team-scoped link
  - A public feature is now reachable by its own link whichever team owns it, and opening one moves the visitor onto that board for the rest of the session, so "back to the board", voting and commenting all follow. An install with `portal.team_id` set keeps 404ing everything outside that team
  - **Portal submissions no longer 403 the people the portal is for.** The submit path required the submitter's `currentTeam` to equal the board's team — but a visitor who registered through `/p/register` holds no host-app team, so every genuine public submission was rejected. Features are stamped with the board's team instead
- **PDF documents fall back to a published view the host has customised.** Rendering moved to the new template registry with `modern` as the default, which silently retired any PDF restyled the pre-picker way — by publishing and editing `resources/views/vendor/laravel-crm/invoices/pdf.blade.php` and its siblings. `PdfTemplateRegistry::viewForModel()` now prefers that file when it exists *and differs from the packaged copy*; an explicit choice on the record or in Settings → Templates still wins. The content comparison matters: `vendor:publish --tag=views` copies the whole directory, so presence alone would have pinned a host that published last week to the old layout forever. The Templates page warns on any tab where an override is in effect, since saving it writes a choice for all five doc types at once
- **Emailed PDFs ignored the template picker.** `SendQuote`, `SendInvoice` and `SendPurchaseOrder` loaded `laravel-crm::quotes.pdf` and friends directly, so the attachment a customer received did not match the document the sender had just downloaded. All three resolve through `PdfTemplateRegistry::viewForModel()` now
- **`orderComplete()` / `invoiceComplete()` / `deliveryComplete()` compared floats with `> 0`** — a 1.1 order fulfilled as 0.7 + 0.4 leaves `1.11e-16` behind, so the document read as "not fully invoiced" forever. Latent while quantities were integers; user-visible the moment decimals exist. Now compared within half the smallest storable unit
- **`CheckAmount` compared line and document totals with an exact `==`** — price is integer cents, so 3.5 × $9.99 computes 3496.5 against a stored 3497 and **every** fractional line would have shown a red mismatch icon on the show page and a "broken document" badge on the index. Each line is now rounded to the cent the same way the stored one was, and `subTotal()` / `total()` sum those rounded lines rather than rounding once at the end — otherwise two lines of 0.5 × $9.99 store 500 + 500 = 1000 but compute 999, and a perfectly consistent document reads as broken. `subTotal()` / `tax()` / `total()` also now return a real `bool` — they previously returned `true` or fell off the end returning `null`
- **The line item quantity was cast with `(int)` before pricing** — a quantity of 3.5 at $1.99 stored 597 cents instead of 697, and the document header followed the lines, so nothing looked wrong
- The deal form's `(int) $product['quantity'] ?? 1` fallback was dead code — `(int)` binds tighter than `??`, so the default never applied
- The Order line-item loop leaked `$quantityRemaining` across iterations, so a line that did not draw down inherited the previous line's remainder
- **`laravelcrm:v2` was broken on the v1 → v2+ upgrade path.** `fix_line1_nullable_on_laravel_crm_addresses_table` hardcoded the `crm_addresses` table name instead of reading `db_table_prefix`, so a host on any other (or an empty) prefix halted the migrate run — which then cascaded into missing `feature_statuses` and `pipeline_stage_probabilities` on the seeders that followed. The command also passed an unsupported `--force` to `flasher:install`, which already wipes and rebuilds `public/vendor/flasher/` on its own. Alongside those: renames now run before migrations, migration stubs are force-published so previously broken published copies are refreshed, nine dangling stub references were pruned from the service provider, the customer / organization stubs gained idempotency guards so a re-run against an already-renamed database no longer fails, and the corrupted `add_customer_to_laravel_crm_deals` stub — which was writing `url` columns across many unrelated tables — is repaired
- **Option-backed custom fields could not be saved.** `select`, `radio` and `checkbox_multiple` values are stored as the `FieldOption` id, but the sample data seeder wrote the option's *value* string, so editing any record carrying one failed `Rule::in([option ids])` — and for `checkbox_multiple` the errors landed on `fields.N.*` keys that no input rendered, so the form silently did nothing on save. Legacy option-value strings are mapped to ids on hydration, the wildcard keys gained messages and attributes, `checkbox_multiple` renders its errors, display falls back to matching by value, and the seeder writes ids
- **Masked money values were stored unnormalised.** Money inputs are masked in the UI, so a value such as `"$1,234.56"` reached the model mutators and services as a formatted string; multiplying it by 100 raised `A non-numeric value encountered`, or a `TypeError` on an empty value, when saving quotes, orders, invoices, purchase orders and deals. New `Support\Money` normalises a user-supplied value to a float or to integer cents, and every money mutator and the services' tax and amount-due arithmetic route through it
- **The line item totals footer zeroed out and stayed there.** `updatedProducts()` reset `sub_total`, `tax` and `total` before returning early for any update that replaced the products array wholesale rather than editing one `{index}.{attribute}` — and the sum itself cast the masked input values, so `"14,000"` counted as `14`. Totals are now always summed from the lines through `Money::toFloat`, derived from the lines on mount so a stale stored total cannot mask them, and dispatched when a row is added
- **Cancelling an inline activity edit did not restore the fields.** `edit()` snapshotted the form values into a *private* `$revert` array, which Livewire does not dehydrate — and `edit()` and `cancel()` are separate requests, so the snapshot was always empty by the time `cancel()` ran. On calls, meetings, lunches and tasks that made Cancel a silent no-op, with the edits left on screen as though they had been kept; on notes it was worse, `cancel()` indexed `$this->revert['content']` directly and threw `Undefined array key`. `mount()` and `cancel()` now share a private `hydrateFromRecord()`, so cancel means "discard the edits and re-read the record" — no state has to survive the round trip, and the fields are right even if someone else edited the record meanwhile
- **`LeadCreate::mount()` threw on a null pipeline stage.** The lookup is null-safe now, so a lead pipeline with no stages renders the form rather than a 500
- **The brand logo did not render in generated PDFs.** DomPDF will not fetch an `http(s)` URL unless the host enables `dompdf.enable_remote`, which is off by default — so the raw storage path the templates wrapped in `asset('storage/...')` produced a broken-image box on every render. New `Support\PdfLogo` reads the file off the `public` disk and inlines it as a base64 `data:` URI, needing no host configuration; it is applied at all 14 PDF render sites. A missing logo file now resolves to `null` so the templates fall back to the organisation name as text rather than a broken image. The Settings → Templates preview was unaffected because it already inlined the file, which is why this went unnoticed. The portal's on-screen views and campaign emails keep the `asset()` URL, which browsers and mail clients fetch fine
- **PDF template thumbnails are served from the package.** The Templates picker linked them through `asset()` behind a `file_exists(public_path(...))` check, so any host whose published assets predated the artwork silently degraded to text-only placeholders. They now resolve through a settings-gated route that prefers the host's published copy and falls back to the copy inside the package
- **`db_update_*` flags are read and written install-wide.** They describe the schema, which is install-wide, but they went through `BelongsToTeamsScope` — and a console command has no authenticated user and so stamps no `team_id`. `laravelcrm:install` and `laravelcrm:update` therefore wrote rows a teams host could never see: every team got its own copy seeded at `0`, and a freshly installed or freshly updated install reported database updates it had already applied. New `SettingService::setInstallWide()` drops the team scope and rewrites every row of the same name, so per-team duplicates left by older versions clear too; a flag counts as pending when *any* of its rows holds `0`, so a stale duplicate cannot mask a genuinely outstanding update
- **The updates page compared versions as raw strings.** `'2.2.0' >= '2.10.0'` is `true` in PHP — it compares character by character — so the page claimed the install was up to date and stopped offering the update the moment the minor version reached double digits. Both comparisons use `version_compare()` now and are derived from one computed value so they cannot drift out of being exact inverses. The page also reads through `app('laravel-crm.settings')` rather than two inline `Setting::where(...)` lookups, and `laravel-crm.updates.index` gained a `can:view crm updates` gate, matching the sidebar link that has always carried one
- **A feature status could be deleted while features still pointed at it**, orphaning them on the board; the delete is refused with a message now. Clearing the "default status" flag is also scoped to the current team, so marking a default on one team no longer unsets every other team's
- **The invitation email resolved the team name badly on hosts without Jetstream.** It goes through the configured host team model with sensible fallbacks now
- **The Pending Invitations tab is gated on `create crm users`**, matching the Invite button beside it, and the `team_user` insert on accept is deduplicated so accepting cannot add a second pivot row
- **The teams dropdown is gated on `config('laravel-crm.teams')`** rather than on the modules Blade directive, so a host running the CRM single-tenant no longer sees a switcher for teams it does not have
- `product-attributes` routes were 403 for every user including Owner: the route parameter was named `{productCategory}` while the `can:` guard read `productAttribute`, so the gate was handed `null` and resolved no policy
- **The deal / quote / order `products` sub-resource groups asked for a permission they were designed not to need.** Each route carried a per-route `can:view,{param}` / `can:update,{param}` guard underneath the group's `can:manageProducts,<Model>`. `can:update` resolved to the same `edit crm <entity>` check as `manageProducts` and was pure duplication; `can:view` resolved to `view crm <entity>` — and `manageProducts` exists precisely so that line items key off the parent's **edit** permission and nothing else. A custom role built under Settings → Roles holding edit-but-not-view could open a deal's form and then `403` on the line items embedded in it. The group gate is now the single rule, which is what the shipped roles and the tests always described
- Every method on `DealProductController` / `QuoteProductController` / `OrderProductController` now type-hints its parent, so a nonexistent parent 404s at the binding, and `$id` lands on the parameter it names — with two route parameters and neither bound, arguments were passed positionally and `show($id)` was handed the parent's key rather than the product's

### Security
- **Authorization is now enforced on every mutating Livewire action and CRM route** — every action now checks the same Spatie permission the UI already advertised via `@can`. Previously the Blade layer hid buttons a user could not use, but the Livewire components behind them did not re-check on the server, so any user who could reach a CRM page could invoke its actions directly over the Livewire endpoint regardless of role.
  - `$this->authorize(...)` added to 165 mutating actions across 117 Livewire components covering leads, deals, quotes, orders, invoices, deliveries, purchase orders, products, tasks, people, organizations, notes / calls / meetings / lunches / files, settings lookups, chat, imports, templates, and campaigns
  - `can:` middleware added to the previously ungated `activities/*` route group and the deal / quote / order `products` sub-resource groups
  - Cross-team user deletion and non-Owner `Owner` role assignment now blocked; the users listing is scoped to the current team so the visible set matches the actionable set
  - 38 `@can` / `@canany` gates added across 20 Blade views, and kanban cards are no longer draggable without the matching `edit` permission (they previously dragged, then 403'd on drop)
  - New `ActivityPolicy`; the orphaned `ProductAttributePolicy` is now registered (it existed but was never wired up, so every `ProductAttribute` authorization check silently fell through to deny)
  - `ActionAuthorizationCoverageTest` added as a regression guard — a mutating Livewire action without a guard or a documented exemption fails the suite by name
  - **No new permission name is introduced.** Stock Owner and Admin receive `Permission::all()`, and Manager and Employee hold create / view / edit / delete on the core CRM entities, so no seeded role loses access it previously exercised (see the upgrade guide for the exact per-role breakdown)
  - **Upgrading — re-run the permission seeding first.** Installs that have upgraded over time without re-seeding may be missing later-release permissions (`crm monitors`, `crm features`, `crm email-campaigns`, `crm sms-campaigns`, `crm chat`, `crm activities`) and will start returning `403`. Run `php artisan laravelcrm:update` (or `db:seed --class=...\LaravelCrmTablesSeeder`) to create any missing permission rows — the seeder is `firstOrCreate` + `givePermissionTo` throughout, so it is idempotent and revokes nothing. On multi-tenant (`teams`) installs, follow it with `php artisan laravelcrm:permissions`, which fans the global CRM roles out to each team; that command creates no permissions of its own and exits with an error when teams are disabled. Custom view-only roles built under Settings → Roles will correctly lose actions they could previously perform, and hosts with a trimmed `config('laravel-crm.modules')` will `403` on the disabled module. See [docs/upgrading.md](docs/upgrading.md) for the full guide.
  - **Ships as a minor release, not a patch** — it changes observable behaviour for existing users
- **Owner-escalation vetting is centralised in `Role::assignableBy()`.** Roles arriving from user creation, user editing, invitation *and* CSV import are all vetted through one predicate now, so a non-Owner cannot grant `Owner` by any route. Previously each site made its own decision and they disagreed
  - `Role::assignableBy()` layers the Owner check onto `assignable()`, so the role dropdowns, the `AssignableRole` validation rule and `UserInvite` share one predicate and cannot offer a role the caller may not hand out. A null caller (console, queue, unauthenticated) is treated as not an Owner
  - **`UserImport` resolved roles with a bare `Role::where('name', $csvColumn)` and `Role::find($this->defaultRole)`.** Both inputs are caller-controlled, so anyone holding `create crm users` could import `role=Owner`, a host-application role such as `super-admin`, or a role belonging to another tenant. Both go through `assignableBy()` now, and an unassignable role is dropped rather than failing the row. Its role dropdown also filtered on `where('team_id', currentTeam->id)`, which rendered empty under teams (the seeded roles carry `team_id => null`) and 500'd for a user with no current team — the options offered and the values accepted can no longer diverge
  - `UserController::store/update` and `UserCreate::save` resolve and vet the role **before** the user row is written. A blocked escalation previously aborted after `forceCreate()`, leaving an orphaned role-less user and burning the email address against the unique index
  - `UserEdit` prefilled its role from the unfiltered Spatie `roles()` relation, so a host-application role or an Owner being edited by a non-Owner was silently offered back
  - `UserIndex::users()` team scoping no longer 500s on a host that enables `laravel-crm.teams` without shipping Jetstream's `team_user` pivot (Spark Classic names it `team_users`), and stops hiding a team owner from their own user list — Jetstream keeps owners out of the pivot and merges them back in via `Team::allUsers()`

## 2.3.0 - 2026-05-31
### Added
- **Feature voting & feedback portal** — public roadmap board where customers and team members can submit feature requests, vote, comment, and follow status changes
  - New entities: `Feature`, `FeatureStatus`, `FeatureComment`, `FeatureVote`, `FeatureView`
  - Admin surface under `/crm/features`: list, kanban board grouped by status, show, create, edit, voters list
  - Public portal under `/p/features`: board view, individual feature page, submit form, voting and commenting (gated by sign-in)
  - **Charts on the show page** — votes-over-time and views-over-time displayed side by side, with selectable period (7 / 30 / 90 / 365 days)
  - **View tracking** with per-visitor de-duplication (configurable via `LARAVEL_CRM_FEATURES_VIEW_DEDUP_MINUTES`)
  - Notifications: `FeatureSubmittedNotification`, `FeatureStatusChangedNotification`, `FeatureCommentPostedNotification`
  - Default statuses seeded: Under Review, Planned, In Progress, Completed, Declined
  - Module toggle `features` and Blade directive `@hasfeaturesenabled`
  - `FeaturePolicy` and permission seeding for view / create / update / delete / manage-statuses
- **Uptime & SSL monitoring module** — track external HTTP/HTTPS endpoints from inside the CRM
  - New entities: `Monitor`, `MonitorCheck`
  - Admin surface under `/crm/monitors`: list with 7-day performance sparkline, show page with response-time bar chart (24h / 7d / 30d / 90d / 365d), CRUD forms
  - Configurable per-monitor: URL, method, expected status code, check interval, timeout, performance threshold, custom request headers/body, SSL expiry alert window
  - SSRF guard rejects loopback/private/reserved targets by default (override via `LARAVEL_CRM_MONITORING_ALLOW_PRIVATE_TARGETS`)
  - Response time measured via Guzzle `on_stats` transfer time (more accurate than wall-clock)
  - Queued `RunMonitorCheck` job + `laravelcrm:monitor-check` console command (scheduled via the package scheduler)
  - Mail notifications for downtime and SSL expiry alerts
  - Module toggle `monitoring` and `MonitorPolicy` / `MonitorCheckPolicy`
- **Public portal redesign** — rebuilt the entire portal layout on Vite + Tailwind v4 + DaisyUI v5 + MaryUI
  - New top navbar with logo, theme toggle, and auth-aware actions
  - Centred main container, toast notifications, and a footer
  - Public quote, invoice, and purchase-order show pages refactored to the new stack
  - Portal auth scaffold (login / register) gated by `LARAVEL_CRM_PORTAL_ALLOW_REGISTRATION`
- **File upload improvements**
  - Drag-and-drop dropzone affordance on the file-upload component, with inline selected-file preview and remove control
  - Upload progress bar
  - Per-component `size` and `allowed-types` properties enforced via Livewire validation rules
  - Translation keys for max-size and allowed-types hints
  - Upload now deferred until the upload button is clicked (no auto-submit on file pick)
  - Captures uploaded-file metadata before `store()` to avoid loss after the temporary file is moved
- **Installer module selection prompt** — `laravelcrm:install` now asks which optional modules to enable, with `--modules=all` and `--modules=leads,deals,...` flags for non-interactive installs
- **`@hasfeaturesenabled` Blade directive** for gating UI by the `features` module
### Changed
- **Sample data seeder** expanded to include 10 sample features with hundreds of votes spread across the last 90 days, and realistic comments
- Sample monitors added to the seeder
- Currency support added to float helpers
- `checkbox_multiple` custom fields now render as checkboxes (was rendering incorrectly as a select)
- Tightened spacing between `checkbox_multiple` options to match `mary-radio` styling
- People index `name` sort key now correctly maps to `last_name`
- Feature title in show header no longer double-encodes HTML entities
- `FeatureStatus` colors normalised so badges render consistently
- Monitor list drops the URL column and falls back to host when name is empty
- Monitor performance threshold rendered as a dotted line on response-time charts
- Monitor form expanded with hints, suffixes, and a threshold field; required fields and labels tightened
- Quote / order / invoice / purchase-order product line UI repaired:
  - `wire:model.live` restored on product select for auto-populating price
  - Tax and amount recalculate when editing price or quantity
  - Model-products keyed correctly on order, invoice, and purchase-order forms
- `flasher:install` now runs silently inside the CRM installer
- Hardened API V2, monitors, and feature-status colour input against SSRF / CSS injection / cross-team access
### Fixed
- Label filter SQL error on index pages
- Monitor name column now nullable; underlying error surfaced when monitor save fails
- `CarbonImmutable` accepted in monitor chart helpers
- Sample data seeder now uses synthetic voter IDs for feature votes (avoids FK/uniqueness conflicts and supports portal-visitor votes)

## 2.2.1 - 2026-05-23
### Fixed
- PDF save directory not created before writing invoice/quote/purchase-order PDF attachments (`file_put_contents: failed to open stream: No such file or directory`)
- Portal routes (quotes, invoices) returning 401 — moved out of authenticated CRM middleware group into own `web`-only route group so signed links are publicly accessible
- Missing portal route for purchase orders (`laravel-crm.portal.purchase-orders.show` not defined) — added `PurchaseOrderController`, portal view, and routes under `/p/purchase-orders`
- PHPFlasher v2 API compatibility — replaced `flash($msg)->success()->important()` chained calls with `flash()->success($msg)` across all controllers (v2 no longer supports chaining type/importance methods on the returned `Envelope`)
- Flash notifications appearing behind the fixed-top navbar on portal pages — bumped `.fl-wrapper` z-index above Bootstrap's navbar in the portal layout
- Missing `invoice_sent` and `purchase_order_sent` lang keys

## 2.2.0 - 2026-05-22
### Added
- **REST API (v2)** — Sanctum-authenticated JSON API mounted at `/crm/api/v2`
  - Full CRUD for 8 entities: leads, products, organizations, people, deals, quotes, orders, invoices (with nested line items on quotes/orders/invoices)
  - Auth endpoints: `POST auth/token`, `GET auth/me`, `DELETE auth/token`
  - `laravel-crm-api` rate limiter (60 req/min/user authenticated, 30 req/min/IP unauthenticated)
  - Multi-tenancy support via optional `X-Team-ID` header
  - Ops artisan command `laravel-crm:api-token` for issuing tokens from the CLI
- **Page titles** across the entire UI — every CRM page now sets a descriptive `<title>` (dashboard, core CRM pipeline entities, sales, marketing, communication, operational, user/team/profile/updates, settings, chat-widgets)
- Filament plugin planning doc (`plan-filamentPlugin.prompt.md`)
### Changed
- **PHP minimum is now 8.2** (was 8.1)
- **Laravel minimum is now 11** (was 10)
- Hardened `auth/token` endpoint to return 422 on validation failures
- Improved PDF document styling
- Chat conversations are now only created when a visitor initiates the conversation (not on widget load)
- Updated sample data seeder
### Fixed
- Undefined `$title` variable when Livewire components call `->layout('laravel-crm::layouts.app')` directly (Xero/ClickSend integration pages)
- Deal pipeline bug
- Purchase order edit bug
- Settings address missing bug
- Product update bug when removing a price
- Download PDF buttons not working
- API: preserve Lead person/organization relations on partial PUT updates
- API: preserve Deal person/organization relations on partial PUT updates
- API: null-safe contact-update helpers in `PersonService` and `OrganizationService`
- API: null-safe `OrderService::updateOrderAddresses`

## 2.1.1 - 2026-05-17
### Added
- Optional CSV import fields for users: `email_verified_at`, `created_at`, `updated_at`, `last_online_at`, `mailing_list`
- Dark mode support for autocomplete dropdown backgrounds
### Changed
- Dispatch email campaign jobs to dedicated `email` queue
- Dispatch SMS campaign jobs to dedicated `sms` queue
- Make sample chat conversations recent (last 30 days) for a live demo feel
- Removed v1 docs from repo
- Updated AGENTS docs
### Fixed
- `taxName` undefined variable in invoice/purchase-order PDFs
- Refactored PDF render calls to use `app('laravel-crm.settings')->get(...)` pattern
- Missing `dateFormat` variable in PDF templates
- Added missing PDF blade templates (invoices, orders, purchase-orders, deliveries)
- Campaign job trait composition error (`$queue` property conflict with `Queueable`)
- `laravelcrm:update` command failing in test environments when tables pre-exist
- Address line 1 missing in some forms
- Sorting quotes by labels
- Null crash on updates page when version settings not seeded
- Default `email_verified_at` to null in user CSV import
- Sanitise NULL/blank date values in user CSV import to prevent Carbon parse exceptions
- Attribute type cast
- Double-namespaced `Carbon::Carbon::` introduced by bulk sed replace
- `CarbonImmutable` compatibility in sample data seeder
- Widened encryptable columns migration to fit encrypted payloads
- Sample data seeding date

## 2.1.0- 2026-06-14
### Added
- People & Organizations CSV import
### Fixed
- Missing migrations for chat conversation
- System check

## 2.0.0 - 2026-05-13
### Added
- Support for Laravel 12 & 13
- Web / In-app Chat
- Email Marketing
- SMS Marketing
### Changed
- Complete rewrite of the application using the TALL stack
- TALL stack = Tailwind, Alpine, Laravel & Livewire

## 1.4.1- 2025-08-05
### Fixed
- Bug with default quote item ordering

## 1.4.0 - 2025-06-14
### Added
- More details on autocomplete dropdowns

## 1.3.4 - 2025-0609
### Fixed
- Fixed bug when uploading files
- Fixed bug when deleting activity and the component not refreshing

## 1.3.3 - 2025-05-24
### Fixed
- Fixed bug when adding new emails, phone numbers and addresses and setting as primary

## 1.3.2 - 2025-05-06
### Added
- Better support for Jetstream teams
### Fixed
- Typo in task reminder with organization link

## 1.3.1 - 2025-04-07
### Fixed
- sortBy bug when creating new quotes, orders, invoices, deliveries & purchase orders

## 1.3.0 - 2025-04-04
### Added
- Quote, Order, Invoice, Delivery & Purchase Order Item order drag & drop
### Fixed
- Fixed issue with Lead, Deal & Quote board drag & drop ordering

## 1.2.3 - 2025-02-25
### Added
- Cache settings in view composer
- Create & add another PO from orders
### Changed
- Updated invoice payment instructions
- Unallocated option for leads, orders, etc
- Don't paginate boards
### Fixed
- Missing pipeline on lead to deal conversion
- Archive command added to provider
- Handle invalid payload on decrypt
- Issue with searching on related fields
- Missing client relation on person model

## 1.2.2 - 2024-08-25
### Fixed
- Kanban board search
- Search when not using encrypted fields
- Bug when custom field has been deleted

## 1.2.1 - 24-08-24
### Fixed
- Force seed pipeline settings

## 1.2.0 - 2024-08-24
### Added
- Kanban boards
- Custom Fields
- Disabled double click on form submits
- Lead prefix
- Deal prefix
### Changed
- Improved logo sizing on pdfs
- Vertical navigation for settings
- Imporved updating process
### Fixed
- Unreject quotes functionality
- But when added organization
- Version check

## 1.1.0 - 2024-06-17
### Added
- Support for Laravel 11
- Product barcode
- Create multiple purchase orders from an order
- Show purchase orders tab on orders
- Custom fields to various models
- Download, show and send Xero invoices
- Search invoices, deliveries & purchase orders
- Email purchase orders
- Delivery types
### Changed
- Product code renamed as SKU
- Updates to custom fields
- Updated purchase order PDF
- Added setting for checking for global field scope
- Updated totals, tax totals checks
### Fixed
- Edit/delete purchase orders
### Removed

## 1.0.0 - 2024-03-09
### Added
- Comments on invoice lines
- Purchase orders
- Purchase order Xero integration
- Some statistics totals widgets on dashboard
- Set default tax rate when creating products
- Setting to disable adding products dynamically when creating quotes, orders, invoices
- Added VAT / Sales Tax number
- Added some extra fields to organisations
- Email validate helper
- Tax type on tax rates
### Changed
- Updated form request validation on settings
- Moved global view settings to view composer
- Updated some setting variable names to avoid conflicts
- Don't apply the teams scope when using Laravel Nova
- Order search results on leads, orders & quotes by latest
### Fixed
- Issue when query string too long when many filters added
- Allow null tax name and rate on settings
- But in multi-tenant mode when adding orders
- Bug when adding invoice line items
### Removed
- Duplicated notes tab removed
- Auth checker package removed

## 0.19.10 - 2023-09-25
### Added
- Phone, emails & address to settings and users
- VAT/ABN to settings
- Added setting to disable update notifications
- Address multiple lines helper
### Changed
- No longer adds zero quantity items to deliveries
### Fixed
- Adding new organisation or person bug when creating invoice
- Produce code required to post products to Xero api

## 0.19.9 - 2023-09-04
### Fixed
- Database update for tax amounts

## 0.19.8 - 2023-09-04
### Added
- Tax rates and tax amount added to quote products, order products & invoice lines
- Indicate on products whether exist in Xero items
### Fixed
- Bug with create new products setting when creating quotes, orders, invoices
- Tax rate show view
### Removed
- Save/Cancel buttons on product category show view

## 0.19.7 - 2023-09-01
### Changed
- Switched cs from laravel to prs12
### Fixed
- Related contacts bug when deleting peron or organisation
- Bug when allowing null value on custom field
- Fixed bug by disabling create new labels when adding leads, contacts, etc

## 0.19.6 - 2023-08-29
### Fixed
- Error when sending quotes and missing organization name setting
- Error when deleting custom fields and attached models
- Error when showing deliveries when order has been deleted
- Ensure address isset on pdf before displaying
- Error on PDF when person not set

## 0.19.5 - 2023-08-29
### Fixed
- Consider soft deleted models when incrementing numbers

## 0.19.4 - 2023-08-28
### Added
- Delivery number
### Fixed
- Copying billing & shipping address from quote to order
- Copying shipping address from order to delivery

## 0.19.3 - 2023-08-28
### Added
- Show quote orders
- Show order invoices
- Show order deliveries
### Changed
- Update command for order related deliveries

## 0.19.2 - 2023-08-28
### Fixed
- Version 0.19.1 database update check

## 0.19.1 - 2023-08-28
### Changed
- Now using Laravel Pint and Laravel preset for code style
- Create multiple invoices from an order
### Fixed
- Typo on quote show view
### Removed
- Travis config
- cs fixer config

## 0.19.0 - 2023-08-25
### Added
- Update command for updating database
- Make some of the models optional with config setting
- Show related contact activity setting
- Client search
- Using Pint and Laravel preset for code style
- Tax rates setting
- Invoice contact details setting
- Check app is running on correct subdomain setting
- Added setting default config
- Show users with update permissions update alerts
- Send task, call, meeting and lunch reminder emails
### Changed
- Add related contacts to person and use contacts relation
- Update some dependencies for Laravel 10 support
- PDF download filenames updated
- Default invoice, order & quote number set to 1000
- Allowing products to be added during order, quote, invoice create
### Fixed
- Disabled settings no longer throws error
- Bug when no activity
- Support for removed Jetstream personal team
- Invoice title fixed
- Missing product on invoice
- Invoice due badge
- Timezone global view share
- Tax amount on invoice lines
- Validation on phone number & email type
### Removed

## 0.18.1 - 2023-06-04
### Added
- Laravel 10 support

## 0.18.0 - 2023-06-02
### Added
- Show product code on quote, order & invoice lines
- Default sales account for xero integration
- Purchase & sales account codes on products
- Quote prefix setting
- Order prefix setting
- Indicate related invoice order
- Indicate related quote on order
- Split quote into multiple orders
- Split order into multiple deliveries
- Added checks on totals and indicted when errors
### Changed
- Activate select2 when adding quote, order & invoice items
- Improved PDF formatting
### Fixed
- Copy reference to invoice created from order
- Bug with deleting notes & related activity
- Bug with issue & due dates on xero invoices
- Fixed error when creating order without a quote
- Don't show unordered list when zero notes, removes extra padding above tabs

## 0.17.1 - 2023-04-23
### Added
- Date & time format setting
- Option to show specific addresses on orders
### Fixed
- Missing invoice number, issue and due date on PDF
- Missing delivery date on delivery PDF
- Bug with non numeric values in price & quantity on quote, order, invoice items
- Bug when missing address and creating or editing orders
- Bug when settings have no value

## 0.17.0 - 2023-04-12
### Added
- Number formatting on quotes, orders & invoice items
- Add products to xero when adding to crm
- Add reference to xero invoice
- Row delete on quote, order & invoice items
- Added received by on deliveries
- Added delivery contact to delivery pdf
- Added pdf attachment to send quotes email
- Added pdf attachment to send invoices email
- Indicate if a contact is in xero
- Indicate required fields
- Customer on orders
- Added expected and actual delivery dates
- Added customer to leads, deals, quotes & orders
- Create leads, deals, quotes & orders from customers, organisations & people
### Changed
- Load select2 options from data array
- Quote, Orders & Invoice PDF formatting
- Improved title generation on leads & orders
- Client now called customer
- Invoice number not required when using xero integration
### Fixed
- Fixed quote to order error
- Fixed error on pdf when contact person not set
- Fixed organisation name on invoice pdf
- Fixed support for db seeder when using teams
- Fixed bug showing delivery when order is deleted
- Fixed bug on lead form fields

## 0.16.0 - 2023-03-12
### Added
- Menu icons
- User model relations trait
- Client model
- Invoice generation in xero integration
- Billing & shipping addresses on orders
- Shipping address on Deliveries
### Changed
- No text wrapping on responsive tables
- Quote items now using Select2
- Order items now using Select2
- Invoice lines now using Select2
- Improved layout for quote items, order items & invoice lines
### Fixed
- Typo in delivery products migration
- Bug when retrieving related contacts by type
- Bug with decimal missing from product pricing in xero integration
- Layout issues fixed on smaller screens

## 0.15.0 - 2023-02-24
### Added
- Usage request logging
- Custom fields and field groups
- Disable UI setting
- Deliveries
- Quote, Order, Invoice & Delivery PDF downloads
- Default Quote & Invoice Terms setting
### Changed
- Use mail template for outgoing emails
### Fixed
- Validate signed urls for quotes and invoices
- Only run xero schedule commands with integration enabled
- Fixed multi-tenant xero connection
- Increase url fields size on usage requests table

## 0.14.1 - 2023-01-20
### Added
- Laravel Breeze profile section support
### Changed
- Completed the CLI installer

## 0.14.0 - 2023-01-17
### Added
- Invoicing

## 0.13.0 - 2023-01-06
### Added
- Orders
- Calls, meetings and lunches
- Logo and favicon
### Changed
- Merged notes and tasks into activities
### Fixed
- Button background colors
- Zero tasks, notes and orders

## 0.12.2 - 2022-12-10
### Added
- Lead source observer
- Invoices permissions
- Retain filters when searching
- Return to search results
### Changed
- Only run xero scheduled tasks when relevant setting is true
- Moved model filters to a modal for better UX
- Set multi-select max height
### Fixed
- Xero middleware check if auth user before setting tenant id
- Sorting working with encrypted table fields
- Default owner user filter
- Support for browser back button with search

## 0.12.1 - 2022-12-03
### Added
- Aggregated notes
- Support for xero integration multi-tenancy
### Changed
- Quote items in a table
- Disable autofill on noted_at field
### Fixed
- Xero integration when using teams
- Deleting of activity when notes, tasks or files deleted

## 0.12.0 - 2022-11-19
### Added
- Quote builder
- Send quotes
- Accept/Reject quotes
- Tasks
- Files upload
- Xero integration
- Noted at field on notes
- Pin notes
- Toast notifications
- Timezone setting
- Logo setting
### Fixed
 - Support for country domains when using subdomain
 - Issue with spatie permissions when conflicting tables exist
 - Various minor bugs and typos

## 0.11.0 - 2022-09-03
### Added
- Laravel 9 support
- Default settings in config
- Better subdomain support so not to conflict with other routes
- Noted at datetime on notes
### Changed
- Replaced countries package for Laravel 9 support
### Fixed
- Laravel 6 support
- PHP 7 support
- Team settings
- Bug when not using teams support causing issue with permissions and seeder

## 0.10.1 - 2022-03-22
### Fixed
- Issue with middleware affecting access to non-crm API

## 0.10.0 - 2022-03-11
### Added
- Link to owner profile on contacts
- Clear filters button
- Sort functionality on filters where available
- Auto build lead title
### Fixed
- Remove organisation from a person
- Problem when query has joins and using teams 
- https://github.com/venturedrake/laravel-crm/issues/33
- https://github.com/venturedrake/laravel-crm/issues/34

## 0.9.9 - 2021-12-15
### Added
- Show related notes from related contacts
### Fixed
- Notes when using teams

## 0.9.8 - 2021-12-08
### Fixed
- Issue with loading team roles, settings when not using teams mode

## 0.9.7 - 2021-11-27
### Fixed
- Issue with adding owner role when creating new team

## 0.9.6 - 2021-11-24
### Added
- Related organisations and people
- AU & GP language variables

## 0.9.5 - 2021-11-16
### Fixed
- Missing command from service provider

## 0.9.4 - 2021-11-15
### Added
- Ability to add notes to leads, deals & contacts
- Auth logging
- Organization types
- Search option on multiselect search filters
### Fixed
- Incorrectly names morph fields on notes table
- Typo in lang file
- Formatting of delete button on phone, email and addresses

## 0.9.3 - 2021-11-05
### Changed
- Filters now use post request and stored in session

## 0.9.2 - 2021-11-03
### Fixed
- Address types migration command

## 0.9.1 - 2021-11-03
### Fixed
- Missing command in service provider

## 0.9.0 - 2021-11-02
### Added
- Model audit logging
- Config for spatie permissions
### Fixed
- Address types teams support

## 0.8.1 - 2021-10-28
### Fixed
- Editing roles in teams mode

## 0.8.0 - 2021-10-27
### Added
- Spatie permissions team support
- allTeams scope
### Fixed
- Search filter scope
- Problem with team permissions cache

## 0.7.2 - 2021-10-20
### Added
- Added owner and label browsing filters
### Changed
- Use owner rather than assigned to field
### Fixed
- Issue with address and contact types migrations
- Search leads and deals
- Settings menu active main menu issue
### Removed

## 0.7.1 - 2021-10-08
### Added
- Name field on address
### Fixed
- Issue with copying labels to teams
### Removed
- Don't set a team if not using teams

## 0.7.0 - 2021-09-24
### Added
- Labels admin
- Labels description
- Multiple contact addresses
- Mutliple contact phone numbers
- Mutliple contact emails
- Fax number type
- Select2 for labels

## 0.6.8 - 2021-09-12
### Fixed
- Roles & Permissions team owner role missing

## 0.6.7 - 2021-09-12
### Added
- Roles & Permissions team support

## 0.6.6 - 2021-09-03
### Changed
- Default field level encryption security setting to false
### Fixed
- Issue with table field size when using field level encryption

## 0.6.5 - 2021-08-29
### Fixed
- Issue with user policy when user in models directory

## 0.6.4 - 2021-08-13
### Changed
- Dual listbox for managing crm team users
### Fixed
- Show crm team users only

## 0.6.3 - 2021-07-29
### Fixed
- Issue with publishing migrations

## 0.6.2 - 2021-07-28
### Added
- blade directives to show/hide CRUD buttons
### Fixed
- Missing lang keys
- Bug where converted leads were still showing
- Hide on team users from latest online users dashboard widget

## 0.6.1 - 2021-07-15
### Fixed
- Users in teams

## 0.6.0 - 2021-07-14
### Added
- Support for Jetstream/Spark teams
### Fixed
- Model observers - https://github.com/venturedrake/laravel-crm/issues/29
- Assign role instead of sync - https://github.com/venturedrake/laravel-crm/pull/28

## 0.5.1 - 2021-05-31
### Fixed
- Missing key in lang file

## 0.5.0 - 2021-05-31
### Added
- Language support
### Fixed
- Bug when converting lead to deal

## 0.4.0 - 2021-05-21
### Added
- Products & product categories
- Product & product category permissions
### Fixed
- Issue with editing a role name
- Issue with dashboard chart

 ## 0.3.1 - 2021-05-12
### Added
- Version check on updates route

## 0.3.0 - 2021-05-11
### Added
- Dashboard
- team_id to models
### Removed
- User model class

## 0.2.7 - 2021-04-04
### Fixed
- Conflict with Laravel 8 Jetstream teams route

## 0.2.6 - 2021-04-26
### Fixed
- Conflict with Laravel 8 default routes

## 0.2.5 - 2021-04-25
### Added
- Support for Laravel 8 App\Models\User
- Config file comments and updated readme
### Fixed
- Conflict with default Laravel auth routes

## 0.2.4 - 2021-04-23
### Fixed
- Issue with timestamp on published migrations

## 0.2.3 - 2021-04-23
### Fixed
- Issue with migrations being before spatie permissions

## 0.2.2 - 2021-04-23
### Fixed
 - Typo in readme
 - Issue with order of published migrations

## 0.2.1 - 2021-04-22
### Changed
 - Moved lead, deal, person, organisation, users & team views to partials & components
### Fixed
 - Bug with LeadPolicy
 - Bug with checking user on team

## 0.2.0 - 2021-04-15
### Added
- Roles / Permissions
- Traits HasCrmAccess & HasCrmTeams for App\User model

### Changed
- Contacts created when adding leads
- Use the App\User model by default

### Fixed
- New contact badge
- Role not required when editing user
- Check if settings table exists and create if not
- Version check bug
- btn hover style on table rows
- form group for crm access toggle

### Removed
- VentureDrake\LaravelCrm\Models\User model

## 0.1.6 - 2021-04-07
### Changed

- Updated crm middleware group

## 0.1.5 - 2021-04-01
### Fixed

- Bug with seeder not working after assets published

## 0.1.4 - 2021-04-01
### Changed

- Support for Laravel 7/8
- Livewire support for Laravel 7/8

## 0.1.3 - 2021-04-01
### Removed

- Livewire dependency

## 0.1.2 - 2021-03-19
### Added

- Version checking

## 0.1.1 - 2021-03-19
### Removed

- Disabled seeding sample data

## 0.1.0 - 2021-03-18
### Added

- Leads
- Deals
- People
- Organizations
- Users
- Teams