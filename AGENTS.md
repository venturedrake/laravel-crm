# AGENTS.md — Laravel CRM

A Laravel **package** (`venturedrake/laravel-crm`) that installs a full CRM into any Laravel application. All source lives under `src/` with namespace `VentureDrake\LaravelCrm\`. Supports Laravel 11–13 (`illuminate/support ^11.0|^12.0|^13.0`), PHP 8.2+.

## Workspace Overview

This package is developed across a **three-project workspace**:

| Project | Path | Purpose |
|---|---|---|
| **Package** (this repo) | `/Users/andrewdrake/Packages/laravel-crm` | CRM package source — all CRM code lives here |
| **Laravel 13 Host** (primary) | `/Users/andrewdrake/Sites/laravel-13-crm-v2` | Laravel 13 host that **consumes** the package via Composer path repo — primary dev/test host |
| **Docs** | `/Users/andrewdrake/Sites/laravel-crm-docs` | Markdown docs site (per-feature `.md` files: leads, deals, quotes, etc.) — update when adding/changing user-facing features |

The Laravel 13 host requires this package via a Composer **path repository** with the symlink `vendor/venturedrake/laravel-crm -> ../../../../Packages/laravel-crm/`, so changes to package source are reflected immediately without `composer update`.

**Key workflow**: Edit package code here in `/Users/andrewdrake/Packages/laravel-crm/`, test immediately in the Laravel 13 host via browser or `php artisan` from `/Users/andrewdrake/Sites/laravel-13-crm-v2`. No publish step needed for code — only run `php artisan vendor:publish` when updating config, views, or assets.

## Architecture

### Layered stack (thin by design)
```
Routes (src/Http/routes.php)
  → Controllers (src/Http/Controllers/) — return views or redirect; no business logic
    → Views (resources/views/) — load Livewire components
      → Livewire components (src/Livewire/) — all UI state and form logic
        → Services (src/Services/) — create/update orchestration
          → Models (src/Models/) — Eloquent + Observers
```
Repositories (`src/Repositories/`) are minimal thin wrappers (`all()`/`find()`) — put business logic in Services, not Repositories.

Other top-level dirs under `src/`: `Events/` (e.g. `ChatMessageSent` broadcast event), `Listeners/` (e.g. `NewAuthDevice`), `Jobs/` (queued campaign dispatch + `SendImportPasswordReset` for CSV-imported users), `Mail/` (mailables: `SendQuote`, `SendInvoice`, `SendPurchaseOrder`, `EmailCampaignMessage`, `ChatRequestInitiated`, `MissedChatNotification`, `WelcomeImportedUser`), `Notifications/`, `Sms/` (`SmsCampaignMessage`), `Scopes/` (e.g. `BelongsToTeamsScope`), `Facades/` (`LaravelCrmFacade`), `Console/` (all `laravelcrm:*` artisan commands), and `Http/Middleware/` (custom middleware stack — see Routing section).

### Two Livewire namespaces
- **`src/Livewire/`** (`VentureDrake\LaravelCrm\Livewire\`) — current v2 components, registered with `crm-` prefix (e.g., `crm-lead-index`, `crm-deal-create`)
- **`src/Http/Livewire/`** (`VentureDrake\LaravelCrm\Http\Livewire\`) — legacy inline/sub-components, registered with short names (e.g., `live-lead-form`, `quote-items`)

All components are manually registered in `LaravelCrmServiceProvider` (not auto-discovered).

**Shared model sub-components** (v2, in `src/Livewire/`): `ModelPhones`, `ModelEmails`, `ModelAddresses`, `ModelProducts`, `RelatedPeople`, `RelatedOrganizations`, `RelatedDeals` — reusable across entity show pages.

**Top-level Livewire components** (v2, in `src/Livewire/`): `Dashboard`, `ActivityMenu`, `ActivityTabs` — used for the main dashboard and activity UI.

**Auth components** (v2, in `src/Livewire/Auth/`): `Login`, `ForgotPassword`, `ResetPassword` — CRM-specific authentication flows.

**KanbanBoard**: Reusable `src/Livewire/KanbanBoard.php` component with views in `resources/views/livewire/kanban-board/`. `src/Livewire/PipelineBoard.php` extends it for boards whose columns are pipeline stages: `Leads/LeadBoard`, `Deals/DealBoard` and `Quotes/QuoteBoard` declare their model, card columns and stage actions, and it owns the paging. Each column renders `laravel-crm.board.per_column` cards (env `LARAVEL_CRM_BOARD_PER_COLUMN`, default 20) and `loadMore()` appends the next page without a full render; headers come from one `GROUP BY` over the whole filtered set. `stageLimits` is the cards on screen per column and so the next page's offset: `render()` fetches at least a page and rewrites it with what it got, and `loadMore()` and drags adjust it. `loadMore()` and the drag handlers skip the render only through `canSkipRender()`: `skipRender()` covers the whole request, so a call that shares one has to render, and so does a drag made from an out-of-date board. `sortable.blade.php` catches a failed drag with a `request` hook, because nothing else would correct the screen: it calls `refreshBoard()` and makes the next drag render. `pipeline_stage_order` is usually `NULL` and `NULL` sorts first, so `reorder()` pushes unloaded cards behind the loaded ones after a drag. Keep a card's queries on the page, not per card: a render's query count must depend on the number of stages, not records. Never open or close an `@script` body with a comment: Livewire 3 and early 4 can't evaluate one that opens with it, and no JS test would show it (`tests/Feature/ScriptBlocksTest.php` fails the build instead). `Features/FeatureBoard` extends `KanbanBoard` directly and is not paged. Card menus go through `<x-crm-board-card-menu>`, not `<x-mary-dropdown>`: the board's `overflow-auto` wrapper clips anything inside it, so the menu teleports to `<body>` and exists only while open. Its `livewire:navigating` handler removes the teleport template as well as closing it: Back and Forward save the page's HTML before `x-if` can react, and a saved template restores as a list nothing can close. Its `wire:click`s still reach the board, and `sortable.blade.php` filters `[data-board-menu]` so pressing the chevron can't start a drag.

**Shared Send drawers**: `QuoteSend` and `InvoiceSend` fill their fields in `open()`, not `mount()`. Boards and index pages mount one instance with no record (`type="menu-item"` hides its button) and rows dispatch `quote-send` / `invoice-send` with an `id`; `open()` resolves that through the team scope and authorizes it. Show and related-index pages keep an instance bound to their record, which ignores events for any other.

**Lead conversion**: a lead converts once. `DealCreate` sets `converted_at` through `LeadService::markConverted()`, which also moves the lead to a `Converted`, `Closed Won` or `Won` stage when its pipeline has one. `DealService::create()` refuses a converted lead's `lead_id` with a `ValidationException`, so the form and the API are both held to it, and `DealCreate` turns it away before creating any contact. `Lead::deal()` is the deal the lead became, through `crm_deals.lead_id`. Deleting a lead's last deal clears `converted_at` (`DealObserver::deleted()` calls `LeadService::undoConversionWithoutDeals()`, and a migration in `database/updates` did the same for leads whose deal went before that) so it can convert again; its stage stays, as nothing records the one before. `restored` doesn't reverse this: nothing in the package restores a deal, and an API deal's `lead_id` is only a link, so a restore can't tell it converted the lead.

**Quote transitions**: `Services/QuoteStateService` (`markSent()`, `accept()`, `reject()`, `unaccept()`, `unreject()`) is the one place a quote's timestamp and pipeline stage change together; the show, index, board and send components and the portal all call it. A drag on `QuoteBoard` never removes a response: `moveBlockedReason()`, through the `refusesMove()` hook on `PipelineBoard`, turns away an accepted or rejected quote heading for Draft, Sent or the opposite response, using the stored quote and not the client's column, so an out-of-date card can't lose a response the user hasn't seen. Unaccept and Unreject are the only way back. It also stops a response when the amounts are wrong, as the menus do. Otherwise `moved()`, through `stageChanged()`, records the response for a move into Accepted or Rejected. Ordered, Won and a pipeline's own stages conflict with nothing, and a quote with an order can go anywhere but only changes column. Stage names match exactly, so a renamed stage records nothing. A refused drag saves nothing, and the render that follows corrects the screen. The quote form and the API can still set a stage alone. `markSent()` only moves a Draft (or stageless) quote that isn't accepted, rejected or ordered. A pipeline without the named stage keeps the current stage rather than clearing it. The portal refuses a response through `Quote::responseBlockedReason()` / `acceptBlockedReason()`; `Quote::validUntil()` is the end of the expiry day for a date-only expiry, as the form stores the start of the day, and the exact moment for any other time, such as one set through the API or by the sample data; a time at the very start of a day therefore counts as date-only. A cleared date is stored as `NULL`, and an older blank (`''` or a zero date) counts as no expiry, because the cast would read it as a real date: check `Quote::hasStoredDate()` before reading one. `isExpired()`, the portal's countdown and `QuoteEdit` all read `validUntil()`. The API converts `expire_at` to the app's timezone, because Eloquent drops an offset and this is the one date enforced to the moment; don't do the same to `issue_at`, `issue_date`, `due_date` or `expected_close`, which act as calendar dates and would move to a different day. `QuoteService::update()` keeps a stored time when the form saves the same date, because the form only shows the date.

**Activity sub-components** (v2, in `src/Livewire/Activities/`): `ActivityFeed`, `ActivityIndex` — used on entity show pages and the standalone activity view.

**Inline activity sub-components** (v2): Each activity type has `*Item` (single record row) and `*Related` (list embedded in a show page) components in their own namespace — `Calls/`, `Files/`, `Lunches/`, `Meetings/`, `Notes/`, `Tasks/`. These are v2 replacements for the legacy `Http/Livewire/Live*` components. `ActivityTabs` mounts every panel except the Activity tab and the pinned notes as a `lazy` component, so a record page loads a panel only when its tab comes into view. Never put a people list in a panel's `render()`: one activity row per timeline entry renders too. The call, meeting and lunch Guests picker is `HasGuestSearch`: `searchGuests()` returns nothing for an empty term and at most `GUEST_SEARCH_LIMIT` matches, plus the guests already chosen, which Mary's picker needs in its options.

**Related index sub-components**: `Deliveries/DeliveryRelatedIndex`, `Invoices/InvoiceRelatedIndex`, `Orders/OrderRelatedIndex`, `PurchaseOrders/PurchaseOrderRelatedIndex` — embedded lists of related records on entity show pages.

**Full-CRUD v2 entities**: `Tasks/`, `Teams/`, `Users/`, `Organizations/`, `People/`, `Products/` — each has `Create`, `Edit`, `Index`, `Show` components (e.g. `OrganizationIndex`, `PersonShow`) plus a `Traits/` subdirectory. All follow the `HasEntityCommon` trait pattern. `People/`, `Organizations/`, and `Users/` additionally ship a `*Import` Livewire component (`PersonImport`, `OrganizationImport`, `UserImport`) powering CSV import flows — imported users get welcomed via `Mail/WelcomeImportedUser` and a queued `Jobs/SendImportPasswordReset`. Domain pipeline entities follow the same shape: `Leads/`, `Deals/`, `Quotes/`, `Orders/`, `Invoices/`, `Deliveries/`, `PurchaseOrders/`. Pipeline send/pay entities also have dedicated action components: `QuoteSend` (`crm-quote-send`), `InvoiceSend` (`crm-invoice-send`), `InvoicePay` (`crm-invoice-pay`), `PurchaseOrderSend` (`crm-purchase-order-send`).

**Marketing campaigns** (v2): `EmailCampaigns/` and `SmsCampaigns/` (`*Create`, `*Edit`, `*Index`, `*Show`) plus reusable `EmailTemplates/` and `SmsTemplates/` CRUD components — gated by the `email-marketing` / `sms-marketing` modules. Dispatch is queued via `src/Jobs/SendEmailCampaignRecipient`, `Jobs/SendSmsCampaignRecipient`, and `Jobs/MaterialiseSmsCampaignRecipients`; outbound messages live in `src/Mail/EmailCampaignMessage.php` and `src/Sms/SmsCampaignMessage.php`. Scheduled by `laravelcrm:email-campaigns-dispatch` and `laravelcrm:sms-campaigns-dispatch`.

**Chat** (v2, in `src/Livewire/Chat/`): `ChatIndex` (conversations list), `ChatShow` (agent reply view, uses `wire:poll` + `echo:` listener for realtime). The visitor-facing widget is an iframe HTML page served by `Portal/ChatWidgetEmbedController@widget` at `/p/chat/{publicKey}` — there is no `ChatWidgetPanel` Livewire component. Chat embed routes live in `src/Http/chat-embed-routes.php` (registered **outside** the `web` middleware group to bypass CSRF/session). Companion settings CRUD lives in `src/Livewire/Settings/ChatWidgets/`. Embed snippet generation: `ChatWidget::embedSnippet()`. Realtime broadcasts via `Events/ChatMessageSent` on public channel `crm-chat.{conversation_external_id}` (falls back to polling). Operator-side notifications use `Mail/ChatRequestInitiated` (new-conversation alert) and `Mail/MissedChatNotification` (no-agent-available alert).

**Profile components** (v2, in `src/Livewire/Profile/`): `UpdateProfileInformationForm`, `UpdatePasswordForm`, `TwoFactorAuthenticationForm`, `LogoutOtherBrowserSessionsForm`, `DeleteUserForm`.

**Settings sub-components** (v2, in `src/Livewire/Settings/`): Full CRUD components for `CustomFieldGroups/`, `CustomFields/`, `Labels/`, `LeadSources/`, `Permissions/` (role management), `Pipelines/`, `PipelineStages/`, `ProductCategories/`, `TaxRates/`, plus `SettingEdit` and `Integrations/Xero/XeroConnect`, `Integrations/ClickSend/ClickSendConnect`.

**Email & SMS Marketing** (v2): Full-CRUD components in `src/Livewire/EmailCampaigns/`, `src/Livewire/EmailTemplates/`, `src/Livewire/SmsCampaigns/`, `src/Livewire/SmsTemplates/`. Backed by models `EmailCampaign`, `EmailTemplate`, `SmsCampaign`, `SmsTemplate` (plus `*Click`, `*Recipient` models). Services: `EmailCampaignService`, `EmailTemplateService`, `SmsCampaignService`, `SmsTemplateService`. SMS sending uses `ClickSendService` (HTTP Basic auth to `https://rest.clicksend.com/v3`; credentials stored as CRM settings `clicksend_username` / `clicksend_api_key` / `clicksend_default_from`).

**Additional services** in `src/Services/`: `ChatService` (handles chat message creation, AI handoff, and visitor session logic), `NumberGeneratorService` (generates human-readable IDs like `L1001`, `D1001` — called by Observers on `creating`).

### Shared Livewire traits
Traits in `src/Traits/` are shared across Livewire components and models:
- `HasGlobalSettings` — access to global CRM settings
- `NotifyToast` — toast notification helper (used alongside `Mary\Traits\Toast`)
- `SearchFilters` — reusable search/filter logic for index components
- `ResetsPaginationWhenPropsChanges` — auto-reset pagination when filter properties change
- `ClearsProperties` — the `clear()` behind every filter drawer's Reset button. It resets only known filter properties and pagination, preserving mounted timezone, currency, pipeline and sort state. Boards override `clearableProperties()` to also clear their column paging state while retaining `$sortable` and `$renderCount`. `tests/Feature/Livewire/FilterDrawerContractTest.php` fails the build if a component's drawer calls something the component doesn't have
- `HasCrmFields` / `HasCustomFormFields` — attach user-defined custom fields to models / render them in Livewire forms (seeded via `laravelcrm:fields`)

**User model traits** in `src/Traits/` (add to host app `App\Models\User`):
- `HasCrmAccess` — gates CRM access; required for all host users
- `HasCrmTeams` — team membership helpers; required when `LARAVEL_CRM_TEAMS=true`
- `HasCrmActivities`, `HasCrmAddresses`, `HasCrmEmails`, `HasCrmPhones` — relationship helpers for person/contact models
- `HasCrmUserRelations` — links CRM records back to the user
- `HasEncryptableFields` — wires `LaravelEncryptableTrait` at the User level
- `SendsCrmPasswordReset` — CRM-branded password reset emails

Form-specific traits live in `src/Livewire/Traits/`:
- Contact emails and phones are validated with `Http/Rules/ContactEmail` (`email:rfc`, never DNS) and `Http/Rules/PhoneNumber` (lenient, in one place), each with its own field message. They apply to the `emails.*.address` / `phones.*.number` lists on people and organizations and the inline `email` / `phone` on lead, deal, quote and order forms; API requests take them from `Api/V2/Concerns/ValidatesContactDetails`. CSV imports don't check phones, and Xero and chat don't check emails, so a record can hold values these rules refuse: each rule takes the values already saved, and edit forms supply them through `HasSavedContactDetails`, so only a changed value is checked. `LeadEdit` supplies none when the form is creating a person, because that person takes the form's phone and email
- `HasPersonSuggest`, `HasOrganizationSuggest` — typeahead/autocomplete suggestions used by lead/deal/quote create+edit forms
- `SearchesEncryptableContacts` — enables free-text search over encrypted name/email/phone fields in index components
- `SearchesRecords` — the search behind the quote, order, invoice, purchase order, lead and deal lists and the boards: the record's number, the columns given, and its contact. Numbers and LIKE terms go through `Support/RecordSearch`: `orNumber()` matches the stored `{model}_id` and its `prefix` + `number` parts, and `orLike()` escapes wildcards with `ESCAPE '!'`, because a backslash needs different quoting per database, and also matches the term as typed, because SQLite and C-locale Postgres only lower-case ASCII. `orNumber()` skips the `number` comparison above `MAX_NUMBER`: that column is a 32-bit integer on most tables, and Postgres errors on a bigger value where MySQL and SQLite find nothing. Don't put a search term straight into LIKE. Only quotes, orders, invoices and purchase orders have a `reference` column; `TestSchema` matches the real deliveries table, which has none. Deliveries match their order's reference

## Key Model Conventions

| Convention | Detail |
|---|---|
| **Base model** | All models extend `src/Models/Model.php` — a thin Eloquent base that adds a `saveQuietly()` helper (wraps `withoutEvents`) to bypass observers when needed. Audit logging has been removed from the package |
| **Table prefix** | `getTable()` returns `config('laravel-crm.db_table_prefix').'tablename'` (default `crm_`) |
| **External IDs** | Routes use `external_id` (UUID, set by Observer on `creating`), not integer PKs |
| **Human IDs** | Observers auto-generate `lead_id`, `quote_id`, etc. as `{prefix}{number}` (e.g. `L1001`) |
| **Money** | Amounts stored as integers × 100; set via `setAmountAttribute`; display with `money($amount, $currency)` helper (`cknow/laravel-money`) |
| **Encryption** | Sensitive fields (person names, emails, phones) encrypted via `LaravelEncryptableTrait`; declared in `$encryptable` array |
| **Multi-tenancy** | `BelongsToTeams` trait adds a global `BelongsToTeamsScope` — queries are automatically scoped to `auth()->user()->currentTeam` when `LARAVEL_CRM_TEAMS=true` |
| **Pipelines** | `Pipeline` + `PipelineStage` + `PipelineStageProbability` models drive the kanban board; `Pipeline` has-many `PipelineStage`; stages are ordered and colour-coded |
| **Products** | `Product` → `ProductVariation` → `ProductPrice`. A variation price row carries both `product_id` and `product_variation_id`; `Product::productPrices()` returns product-level rows only (`whereNull('product_variation_id')`). Deal, quote, order, invoice and purchase-order lines have an optional `product_variation_id` (delivery lines read it through `orderProduct`). Price a line with `Product::unitPriceFor($variation, $currency)` — the variation's price in that currency, else the product price — and label it with `displayName()` / `displayCode()` (`HasProductLineName` trait), never `->product->name`. Services keep a submitted variation only via `ResolvesProductVariation::variationIdFor()`. Xero lines go through `Support/XeroLineItem` |
| **Tax rates** | `TaxRate` model — applied to quote/invoice line items; managed via Settings > Tax Rates UI |
| **Usage tracking** | `UsageRequest` model records API/feature usage; populated automatically by `LogUsage` middleware |

## UI Stack

- **Tailwind CSS v4** + **DaisyUI v5** for styling — uses `@plugin "daisyui"` syntax in `resources/css/app.css` with custom light/dark themes
- **MaryUI** (`robsontenorio/mary`) for Livewire UI components — use `<x-mary-*>` components
- **Livewire 3/4** (`^3.0|^4.0` per `composer.json`) — uses `#[Url]` attributes for query-string binding
- **Toast notifications** via `Mary\Traits\Toast` (already in `HasLeadCommon` and similar traits)
- **Icons**: ForkAwesome (`<x-forkawesome-*>`), Boxicons (`<x-bx-*>`), FontAwesome (`<x-far-*>` / `<x-fas-*>`)
- **JS globals**: `sortablejs` (kanban drag-and-drop), `vanilla-picker` (color picker), `chart.js` v4 (dashboard charts), and `tinymce` v7 (rich-text editor for email campaigns/templates) exposed on `window` via `resources/js/app.js`
- All translatable strings use `__('laravel-crm::lang.key')` with `ucfirst()` — add keys to `resources/lang/`
- **Blade components** registered with `crm-` prefix: `crm-header`, `crm-delete-confirm`, `crm-board-card-menu`, `crm-index-toggle`, `crm-phones`, `crm-emails`, `crm-addresses`, `crm-note`, `crm-app-layout`, `crm-timeline-item`, `crm-custom-fields`, `crm-custom-field-values` — view components in `src/View/Components/`
- **Global helpers** autoloaded via `composer.json` `files` array in `src/Http/Helpers/`: `SelectOptions`, `PersonName`, `AddressLine`, `AutoComplete`, `CheckAmount`, `Validate`, `PublicProperties`

## Routing & Access Control

- Routes prefixed by `LARAVEL_CRM_ROUTE_PREFIX` (default `crm`), named `laravel-crm.*`
- Protected by `auth.laravel-crm` middleware + Laravel Policies (one policy per model in `src/Policies/`)
- **Self-contained CRM auth**: `src/Http/auth-routes.php` registers `laravel-crm.login`, `laravel-crm.password.request`, `laravel-crm.password.reset`, and `laravel-crm.logout` — no Fortify/Breeze required on the host app. These routes use the v2 Livewire auth components.
- **Middleware stack**: `auth.laravel-crm` is an alias for `Authenticate` alone — everything else is pushed onto a route group, not the alias. The `crm` group gets `Settings` + `HasCrmAccess` + `LastOnlineAt` (updates user last-seen timestamp) + `LogUsage` + `FormComponentsConfig` (configures MaryUI form components), plus `RouteSubdomain` (subdomain routing) when `route_subdomain` is set. The `crm-api` group gets `Settings` + `HasCrmAccess` + `LogUsage`, plus `TeamsPermission` (enforces team membership) + `XeroTenant` (sets active Xero tenant) when `teams` is enabled — those two also push onto `web`. Other aliases: `laravel-crm.api.json` (`ForceJsonResponse`), `laravel-crm.api.team` (`SetApiTeamContext`).
- **Flash notifications**: `php-flasher/flasher-laravel` for server-side flash messages
- Public portal routes at `/p/quotes/{external_id}` and `/p/invoices/{external_id}` — no auth required
- Email campaign tracking routes (`/p/email/o/{token}.gif`, `/p/email/c/{token}`, `/p/email/u/{token}`) and SMS tracking routes (`/p/sms/c/{token}`, `/p/sms/u/{token}`) are defined in `src/Http/email-tracking-routes.php` — registered **outside** the `web` middleware group (no CSRF/session) so tracking pixels work from email clients
- Chat widget embed routes (`/p/chat/{publicKey}`, `/p/chat/{publicKey}.js`, and JSON API endpoints) are in `src/Http/chat-embed-routes.php` — also outside `web` middleware so the widget works cross-origin without CSRF errors
- Route subdomain support via `LARAVEL_CRM_ROUTE_SUBDOMAIN`
- **Settings singleton**: `SettingService` registered as `laravel-crm.settings`; shared to all views via `SettingsComposer` (`View::composer('*')`)
- **Module toggles**: `config('laravel-crm.modules')` array enables/disables features (leads, deals, quotes, orders, invoices, deliveries, purchase-orders, teams, chat, email-marketing, sms-marketing). Blade directives `@hasleadsenabled`, `@hasdealsenabled`, `@hasquotesenabled`, `@hasordersenabled`, `@hasinvoicesenabled`, `@hasdeliveriesenabled`, `@haspurchaseordersenabled`, `@hasteamsenabled`, `@haschatenabled`, `@hasemailmarketingenabled`, `@hassmsmarketingenabled` gate UI sections

## Host Apps (Development / Testing)

### Laravel 13 — primary (`laravel-13-crm-v2`)

| Aspect | Detail |
|---|---|
| **Path** | `/Users/andrewdrake/Sites/laravel-13-crm-v2` |
| **Laravel version** | 13.x — PHP 8.4, Livewire 4, Pest 4 |
| **Role** | Primary host that consumes this package via Composer path repo (symlinked `vendor/venturedrake/laravel-crm`) |
| **URL** | Served by Laravel Herd at `http://laravel-13-crm-v2.test`, CRM at `/crm` |
| **Teams** | `LARAVEL_CRM_TEAMS` not set — single-tenant mode |
| **Encryption** | `LARAVEL_CRM_ENCRYPT_DB_FIELDS=false` |
| **User model** | `App\Models\User` uses `HasFactory`, `Notifiable`, `HasRoles` (Spatie), `HasCrmAccess`, `HasCrmTeams` |
| **Frontend** | Host: Tailwind v4 + Vite; package builds independently via its own `vite.config.js` |
| **Dev tooling** | Laravel Boost (MCP), Pail logs, Pest 4 (incl. browser testing) |

## Developer Workflows

```bash
# Install/update dependencies (package directory)
composer install
npm install

# Build frontend assets (outputs to public/vendor/laravel-crm, NOT public/build)
npm run build
npm run dev          # watch mode

# Code style (Laravel preset)
./vendor/bin/pint
composer format      # alias for pint -v
composer format-test # dry-run formatting check

# Tests
composer test        # Pest (uses testbench, SQLite in-memory) — runs vendor/bin/pest
vendor/bin/phpunit --testsuite Unit     # PHPUnit unit tests only
vendor/bin/phpunit --testsuite Feature  # PHPUnit feature tests only

# Key artisan commands (run from a host app)
php artisan laravelcrm:install              # initial setup
php artisan laravelcrm:permissions          # seed roles & permissions
php artisan laravelcrm:sample-data          # generate dev sample data
php artisan laravelcrm:add-user             # add a user with CRM access
php artisan laravelcrm:labels               # seed default labels
php artisan laravelcrm:lead-sources          # seed default lead sources
php artisan laravelcrm:fields               # seed default custom fields / field groups
php artisan laravelcrm:addresstypes         # seed address types
php artisan laravelcrm:organizationtypes    # seed organization types
php artisan laravelcrm:contacttypes         # seed contact types
php artisan laravelcrm:update               # re-publish + migrate + reseed (run after package update)
php artisan laravelcrm:v2                   # one-shot v1 → v2 migration helper
php artisan laravelcrm:encrypt              # encrypt sensitive DB fields
php artisan laravelcrm:decrypt              # decrypt sensitive DB fields
php artisan laravelcrm:xero contacts        # sync Xero contacts
php artisan laravelcrm:xero products        # sync Xero products
php artisan laravelcrm:reminders            # send activity reminders (scheduled every minute)
php artisan laravelcrm:archive              # archive old records (scheduled daily)
php artisan laravelcrm:email-campaigns-dispatch  # queue due email campaign sends (scheduled every minute)
php artisan laravelcrm:sms-campaigns-dispatch    # queue due SMS campaign sends (scheduled every minute)
```

### Scheduled Tasks (auto-registered in ServiceProvider)
- `laravelcrm:reminders` — every minute (activity reminders)
- `laravelcrm:email-campaigns-dispatch` — every minute (email marketing dispatch)
- `laravelcrm:sms-campaigns-dispatch` — every minute (SMS marketing dispatch)
- `laravelcrm:archive` — daily (record archiving)
- `xero:keep-alive` — every 5 minutes (only if Xero credentials configured)
- `laravelcrm:xero contacts` + `laravelcrm:xero products` — every 10 minutes (Xero sync)

## Adding a New CRM Entity (checklist)

1. Migration with `crm_` prefix table and `external_id`, `team_id`, `user_created_id`, `user_updated_id` columns
2. Model in `src/Models/` extending base `Model`
3. Observer in `src/Observers/` — set `external_id` (UUID), auto-increment `number`/`*_id` on `creating`
4. Policy in `src/Policies/` — register in `ServiceProvider::$policies`
5. Service in `src/Services/` with `create()` / `update()` methods
6. Repository in `src/Repositories/` (thin wrapper)
7. Livewire components in `src/Livewire/{Entity}/`: `EntityIndex`, `EntityShow`, `EntityCreate`, `EntityEdit` + shared `Traits/HasEntityCommon`
8. Controller in `src/Http/Controllers/` returning views
9. Routes in `src/Http/routes.php` under a named group
10. Views in `resources/views/{entity}/`
11. Register Livewire components and Observer in `LaravelCrmServiceProvider`

## Integration Points

- **Xero**: `dcblogdev/laravel-xero` — mirror models in `src/Models/Xero*.php`; sync via observers (`XeroContactObserver`, etc.). Invoice and purchase-order line items take their `ItemCode` / `Description` from `src/Support/XeroLineItem.php` (variation code only when a synced `XeroItem` already has it; variations are never pushed to Xero as items)
- **Spatie Permissions**: roles/permissions seeded by `laravelcrm:permissions`; `LARAVEL_CRM_TEAMS=true` requires Spatie teams mode
- **PDF generation**: `barryvdh/laravel-dompdf` and `mpdf/mpdf` for quotes/invoices
- **Countries**: `rinvex/countries` — country list used in address forms and select options
- **Money formatting**: `cknow/laravel-money` — provides the `money($amount, $currency)` global helper
- **Laravel Boost**: `laravel/boost` (dev) is installed — AI agents can use its MCP tooling for guideline lookups and tinker introspection when available

