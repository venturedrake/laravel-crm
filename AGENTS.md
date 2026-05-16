# AGENTS.md — Laravel CRM

A Laravel **package** (`venturedrake/laravel-crm`) that installs a full CRM into any Laravel application. All source lives under `src/` with namespace `VentureDrake\LaravelCrm\`. Supports Laravel 10–13 (`illuminate/support ^10.0|^11.0|^12.0|^13.0`).

## Workspace Overview

This package is developed across a **four-project workspace**:

| Project | Path | Purpose |
|---|---|---|
| **Package** (this repo) | `/Users/andrewdrake/Packages/laravel-crm` | CRM package source — all CRM code lives here |
| **Laravel 12 Host** (primary) | `/Users/andrewdrake/Sites/laravel-12-crm-v2` | Clean Laravel 12 host that **consumes** the package via Composer path repo — primary dev/test host |
| **Laravel 13 Host** | `/Users/andrewdrake/Sites/laravelcrm-v2` | Laravel 13 + Fortify + Flux UI Livewire starter kit — standalone, does **not** consume CRM package |
| **Docs** | `/Users/andrewdrake/Sites/laravel-crm-docs` | Markdown docs site (per-feature `.md` files: leads, deals, quotes, etc.) — update when adding/changing user-facing features |

The Laravel 12 host requires this package via a Composer **path repository** with the symlink `vendor/venturedrake/laravel-crm -> ../../../../Packages/laravel-crm/`, so changes to package source are reflected immediately without `composer update`.

> A separate **Premium Host** (`/Users/andrewdrake/Sites/laravel-crm-premium`, Laravel 11 + Jetstream + Xero) exists on disk but is **not** part of the current workspace; only use it when explicitly testing Xero/Jetstream-specific behavior.

**Key workflow**: Edit package code here in `/Users/andrewdrake/Packages/laravel-crm/`, test immediately in the Laravel 12 host via browser or `php artisan` from `/Users/andrewdrake/Sites/laravel-12-crm-v2`. No publish step needed for code — only run `php artisan vendor:publish` when updating config, views, or assets.

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

**KanbanBoard**: Reusable `src/Livewire/KanbanBoard.php` component with views in `resources/views/livewire/kanban-board/` — used by leads, deals, and quotes board views. Each entity's board component (`Leads/LeadBoard`, `Deals/DealBoard`, `Quotes/QuoteBoard`) wraps `KanbanBoard` with pipeline-specific logic.

**Activity sub-components** (v2, in `src/Livewire/Activities/`): `ActivityFeed`, `ActivityIndex` — used on entity show pages and the standalone activity view.

**Inline activity sub-components** (v2): Each activity type has `*Item` (single record row) and `*Related` (list embedded in a show page) components in their own namespace — `Calls/`, `Files/`, `Lunches/`, `Meetings/`, `Notes/`, `Tasks/`. These are v2 replacements for the legacy `Http/Livewire/Live*` components.

**Related index sub-components**: `Deliveries/DeliveryRelatedIndex`, `Invoices/InvoiceRelatedIndex`, `Orders/OrderRelatedIndex`, `PurchaseOrders/PurchaseOrderRelatedIndex` — embedded lists of related records on entity show pages.

**Full-CRUD v2 entities**: `Tasks/`, `Teams/`, `Users/`, `Organizations/`, `People/`, `Products/` — each has `Create`, `Edit`, `Index`, `Show` components (e.g. `OrganizationIndex`, `PersonShow`) plus a `Traits/` subdirectory. All follow the `HasEntityCommon` trait pattern. `People/`, `Organizations/`, and `Users/` additionally ship a `*Import` Livewire component (`PersonImport`, `OrganizationImport`, `UserImport`) powering CSV import flows — imported users get welcomed via `Mail/WelcomeImportedUser` and a queued `Jobs/SendImportPasswordReset`. Domain pipeline entities follow the same shape: `Leads/`, `Deals/`, `Quotes/`, `Orders/`, `Invoices/`, `Deliveries/`, `PurchaseOrders/`. Pipeline send/pay entities also have dedicated action components: `QuoteSend` (`crm-quote-send`), `InvoiceSend` (`crm-invoice-send`), `InvoicePay` (`crm-invoice-pay`), `PurchaseOrderSend` (`crm-purchase-order-send`).

**Marketing campaigns** (v2): `EmailCampaigns/` and `SmsCampaigns/` (`*Create`, `*Edit`, `*Index`, `*Show`) plus reusable `EmailTemplates/` and `SmsTemplates/` CRUD components — gated by the `email-marketing` / `sms-marketing` modules. Dispatch is queued via `src/Jobs/SendEmailCampaignRecipient`, `Jobs/SendSmsCampaignRecipient`, and `Jobs/MaterialiseSmsCampaignRecipients`; outbound messages live in `src/Mail/EmailCampaignMessage.php` and `src/Sms/SmsCampaignMessage.php`. Scheduled by `laravelcrm:email-campaigns-dispatch` and `laravelcrm:sms-campaigns-dispatch`.

**Chat** (v2, in `src/Livewire/Chat/`): `ChatIndex` (conversations list), `ChatShow` (agent reply view, uses `wire:poll` + `echo:` listener for realtime). The visitor-facing widget is an iframe HTML page served by `Portal/ChatWidgetEmbedController@widget` at `/p/chat/{publicKey}` — there is no `ChatWidgetPanel` Livewire component. Chat embed routes live in `src/Http/chat-embed-routes.php` (registered **outside** the `web` middleware group to bypass CSRF/session). Companion settings CRUD lives in `src/Livewire/Settings/ChatWidgets/`. Embed snippet generation: `ChatWidget::embedSnippet()`. Realtime broadcasts via `Events/ChatMessageSent` on public channel `crm-chat.{conversation_external_id}` (falls back to polling). Operator-side notifications use `Mail/ChatRequestInitiated` (new-conversation alert) and `Mail/MissedChatNotification` (no-agent-available alert).

**Profile components** (v2, in `src/Livewire/Profile/`): `UpdateProfileInformationForm`, `UpdatePasswordForm`, `TwoFactorAuthenticationForm`, `LogoutOtherBrowserSessionsForm`, `DeleteUserForm`.

**Settings sub-components** (v2, in `src/Livewire/Settings/`): Full CRUD components for `CustomFieldGroups/`, `CustomFields/`, `Labels/`, `LeadSources/`, `Permissions/` (role management), `Pipelines/`, `PipelineStages/`, `ProductAttributes/`, `ProductCategories/`, `TaxRates/`, plus `SettingEdit` and `Integrations/Xero/XeroConnect`, `Integrations/ClickSend/ClickSendConnect`.

**Email & SMS Marketing** (v2): Full-CRUD components in `src/Livewire/EmailCampaigns/`, `src/Livewire/EmailTemplates/`, `src/Livewire/SmsCampaigns/`, `src/Livewire/SmsTemplates/`. Backed by models `EmailCampaign`, `EmailTemplate`, `SmsCampaign`, `SmsTemplate` (plus `*Click`, `*Recipient` models). Services: `EmailCampaignService`, `EmailTemplateService`, `SmsCampaignService`, `SmsTemplateService`. SMS sending uses `ClickSendService` (HTTP Basic auth to `https://rest.clicksend.com/v3`; credentials stored as CRM settings `clicksend_username` / `clicksend_api_key` / `clicksend_default_from`).

**Additional services** in `src/Services/`: `ChatService` (handles chat message creation, AI handoff, and visitor session logic), `NumberGeneratorService` (generates human-readable IDs like `L1001`, `D1001` — called by Observers on `creating`).

### Shared Livewire traits
Traits in `src/Traits/` are shared across Livewire components and models:
- `HasGlobalSettings` — access to global CRM settings
- `NotifyToast` — toast notification helper (used alongside `Mary\Traits\Toast`)
- `SearchFilters` — reusable search/filter logic for index components
- `ResetsPaginationWhenPropsChanges` — auto-reset pagination when filter properties change
- `ClearsProperties` — bulk-clear component properties
- `HasCrmFields` / `HasCustomFormFields` — attach user-defined custom fields to models / render them in Livewire forms (seeded via `laravelcrm:fields`)

**User model traits** in `src/Traits/` (add to host app `App\Models\User`):
- `HasCrmAccess` — gates CRM access; required for all host users
- `HasCrmTeams` — team membership helpers; required when `LARAVEL_CRM_TEAMS=true`
- `HasCrmActivities`, `HasCrmAddresses`, `HasCrmEmails`, `HasCrmPhones` — relationship helpers for person/contact models
- `HasCrmUserRelations` — links CRM records back to the user
- `HasEncryptableFields` — wires `LaravelEncryptableTrait` at the User level
- `SendsCrmPasswordReset` — CRM-branded password reset emails

Form-specific traits live in `src/Livewire/Traits/`:
- `HasPersonSuggest`, `HasOrganizationSuggest` — typeahead/autocomplete suggestions used by lead/deal/quote create+edit forms
- `SearchesEncryptableContacts` — enables free-text search over encrypted name/email/phone fields in index components

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
| **Products** | `Product` → `ProductVariation` → `ProductPrice` (with `ProductAttribute` for variant dimensions); all linked to `PurchaseOrderLine`, `QuoteProduct`, `OrderProduct`, etc. |
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
- **Blade components** registered with `crm-` prefix: `crm-header`, `crm-delete-confirm`, `crm-index-toggle`, `crm-phones`, `crm-emails`, `crm-addresses`, `crm-note`, `crm-app-layout`, `crm-timeline-item`, `crm-custom-fields`, `crm-custom-field-values` — view components in `src/View/Components/`
- **Global helpers** autoloaded via `composer.json` `files` array in `src/Http/Helpers/`: `SelectOptions`, `PersonName`, `AddressLine`, `AutoComplete`, `CheckAmount`, `Validate`, `PublicProperties`

## Routing & Access Control

- Routes prefixed by `LARAVEL_CRM_ROUTE_PREFIX` (default `crm`), named `laravel-crm.*`
- Protected by `auth.laravel-crm` middleware + Laravel Policies (one policy per model in `src/Policies/`)
- **Self-contained CRM auth**: `src/Http/auth-routes.php` registers `laravel-crm.login`, `laravel-crm.password.request`, `laravel-crm.password.reset`, and `laravel-crm.logout` — no Fortify/Breeze required on the host app. These routes use the v2 Livewire auth components.
- **Middleware stack** (registered as aliases in service provider): `auth.laravel-crm` wraps `Authenticate` + `HasCrmAccess` + `SystemCheck` + `Settings` + `LogUsage`. Additional middleware: `TeamsPermission` (enforces team membership), `RouteSubdomain` (subdomain routing), `LastOnlineAt` (updates user last-seen timestamp), `FormComponentsConfig` (configures MaryUI form components), `XeroTenant` (sets active Xero tenant).
- **Flash notifications**: `php-flasher/flasher-laravel` for server-side flash messages
- Public portal routes at `/p/quotes/{external_id}` and `/p/invoices/{external_id}` — no auth required
- Email campaign tracking routes (`/p/email/o/{token}.gif`, `/p/email/c/{token}`, `/p/email/u/{token}`) and SMS tracking routes (`/p/sms/c/{token}`, `/p/sms/u/{token}`) are defined in `src/Http/email-tracking-routes.php` — registered **outside** the `web` middleware group (no CSRF/session) so tracking pixels work from email clients
- Chat widget embed routes (`/p/chat/{publicKey}`, `/p/chat/{publicKey}.js`, and JSON API endpoints) are in `src/Http/chat-embed-routes.php` — also outside `web` middleware so the widget works cross-origin without CSRF errors
- Route subdomain support via `LARAVEL_CRM_ROUTE_SUBDOMAIN`
- **Settings singleton**: `SettingService` registered as `laravel-crm.settings`; shared to all views via `SettingsComposer` (`View::composer('*')`)
- **Module toggles**: `config('laravel-crm.modules')` array enables/disables features (leads, deals, quotes, orders, invoices, deliveries, purchase-orders, teams, chat, email-marketing, sms-marketing). Blade directives `@hasleadsenabled`, `@hasdealsenabled`, `@hasquotesenabled`, `@hasordersenabled`, `@hasinvoicesenabled`, `@hasdeliveriesenabled`, `@haspurchaseordersenabled`, `@hasteamsenabled`, `@haschatenabled`, `@hasemailmarketingenabled`, `@hassmsmarketingenabled` gate UI sections

## Host Apps (Development / Testing)

### Host 1: Laravel 12 — primary (`laravel-12-crm-v2`)

| Aspect | Detail |
|---|---|
| **Path** | `/Users/andrewdrake/Sites/laravel-12-crm-v2` |
| **Laravel version** | 12.x — clean scaffold (no Jetstream / Fortify / Sanctum) |
| **Role** | Primary host that consumes this package via Composer path repo (symlinked `vendor/venturedrake/laravel-crm`) |
| **DB** | MySQL, database `laravel_12_crm_v2_dev`, table prefix `crm_` (default) |
| **URL** | `http://localhost` (via `php artisan serve` or `composer dev`), CRM at `/crm` |
| **Teams** | `LARAVEL_CRM_TEAMS` not set — single-tenant mode |
| **Encryption** | `LARAVEL_CRM_ENCRYPT_DB_FIELDS=false` |
| **User model** | `App\Models\User` uses `HasFactory`, `Notifiable`, `HasRoles` (Spatie), `HasCrmAccess`, `HasCrmTeams` |
| **Frontend** | Host: Tailwind v4 + Vite 6; package builds independently via its own `vite.config.js` |
| **Dev command** | `composer dev` (server + queue + `pail` logs + Vite concurrently) |

### Host 2: Laravel 13 standalone (`laravelcrm-v2`)

| Aspect | Detail |
|---|---|
| **Path** | `/Users/andrewdrake/Sites/laravelcrm-v2` |
| **Laravel version** | 13.x — Livewire starter kit with Fortify + Flux UI |
| **Role** | Separate Laravel project in workspace; does **not** consume the CRM package — used for reference/experiments only |
| **DB** | SQLite (`database/database.sqlite`) |
| **URL** | `http://laravelcrm-v2.test` (Herd) |
| **Frontend** | Tailwind v4 + Flux UI v2 + Vite |
| **Testing** | Pest v4 |

### Host 3: Premium (`laravel-crm-premium`) — on-disk only, not in workspace

| Aspect | Detail |
|---|---|
| **Path** | `/Users/andrewdrake/Sites/laravel-crm-premium` |
| **Laravel version** | 11.x with Jetstream (Livewire stack), Sanctum, Fortify |
| **Teams** | Jetstream teams feature is **commented out** — `LARAVEL_CRM_TEAMS` not set (single-tenant mode) |
| **DB** | MySQL, database `laravel_crm_v2_dev`, no table prefix (`LARAVEL_CRM_DB_TABLE_PREFIX=` empty) |
| **URL** | `http://laravel-crm-premium.test` (Herd), CRM at `/crm` |
| **User model** | `App\Models\User` uses `HasCrmAccess`, `HasCrmTeams`, `HasRoles` (Spatie), `SendsCrmPasswordReset`, `HasApiTokens`, `TwoFactorAuthenticatable`, `HasProfilePhoto` |
| **Frontend** | Host has its own Tailwind v3 + DaisyUI v4 build; package builds independently via its own `vite.config.js` |
| **Providers** | `AppServiceProvider`, `FortifyServiceProvider`, `JetstreamServiceProvider` |
| **Dev command** | `composer dev` (runs server + queue + logs + Vite concurrently) |
| **Use when** | Testing Xero integration, Jetstream-specific flows, or empty-table-prefix configurations |

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

- **Xero**: `dcblogdev/laravel-xero` — mirror models in `src/Models/Xero*.php`; sync via observers (`XeroContactObserver`, etc.)
- **Spatie Permissions**: roles/permissions seeded by `laravelcrm:permissions`; `LARAVEL_CRM_TEAMS=true` requires Spatie teams mode
- **PDF generation**: `barryvdh/laravel-dompdf` and `mpdf/mpdf` for quotes/invoices
- **Countries**: `rinvex/countries` — country list used in address forms and select options
- **Money formatting**: `cknow/laravel-money` — provides the `money($amount, $currency)` global helper
- **Laravel Boost**: `laravel/boost` (dev) is installed — AI agents can use its MCP tooling for guideline lookups and tinker introspection when available

