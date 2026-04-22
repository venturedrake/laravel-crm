# AGENTS.md — Laravel CRM

A Laravel **package** (`venturedrake/laravel-crm`) that installs a full CRM into any Laravel application. All source lives under `src/` with namespace `VentureDrake\LaravelCrm\`. Supports Laravel 10–13 (`illuminate/support ^10.0|^11.0|^12.0|^13.0`).

## Workspace Overview

This package is developed across a **three-project workspace**:

| Project | Path | Purpose |
|---|---|---|
| **Package** (this repo) | `/Users/andrewdrake/Packages/laravel-crm` | CRM package source — all CRM code lives here |
| **Laravel 13 Host** | `/Users/andrewdrake/Sites/laravelcrm-v2` | Laravel 13 + Fortify + Flux UI host app (Livewire starter kit) — separate project, does NOT consume CRM package |
| **Premium Host** | `/Users/andrewdrake/Sites/laravel-crm-premium` | Laravel 11 + Jetstream host app with Xero integration |

The Premium host requires this package via a Composer **path repository** (`"url": "../../Packages/laravel-crm"`), so changes to package source are reflected immediately without `composer update`.

**Key workflow**: Edit package code here in `/Users/andrewdrake/Packages/laravel-crm/`, test immediately in the Premium host app via browser or artisan. No publish step needed for code — only run `php artisan vendor:publish` when updating config, views, or assets.

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

### Two Livewire namespaces
- **`src/Livewire/`** (`VentureDrake\LaravelCrm\Livewire\`) — current v2 components, registered with `crm-` prefix (e.g., `crm-lead-index`, `crm-deal-create`)
- **`src/Http/Livewire/`** (`VentureDrake\LaravelCrm\Http\Livewire\`) — legacy inline/sub-components, registered with short names (e.g., `live-lead-form`, `quote-items`)

All components are manually registered in `LaravelCrmServiceProvider` (not auto-discovered).

**Shared model sub-components** (v2, in `src/Livewire/`): `ModelPhones`, `ModelEmails`, `ModelAddresses`, `ModelProducts`, `RelatedPeople`, `RelatedOrganizations`, `RelatedDeals` — reusable across entity show pages.

**Top-level Livewire components** (v2, in `src/Livewire/`): `Dashboard`, `ActivityMenu`, `ActivityTabs` — used for the main dashboard and activity UI.

**Auth components** (v2, in `src/Livewire/Auth/`): `Login`, `ForgotPassword`, `ResetPassword` — CRM-specific authentication flows.

**KanbanBoard**: Reusable `src/Livewire/KanbanBoard.php` component with views in `resources/views/livewire/kanban-board/` — used by leads, deals, and quotes board views.

### Shared Livewire traits
Traits in `src/Traits/` (not `src/Livewire/Traits/`) are shared across Livewire components and models:
- `HasGlobalSettings` — access to global CRM settings
- `NotifyToast` — toast notification helper (used alongside `Mary\Traits\Toast`)
- `SearchFilters` — reusable search/filter logic for index components
- `ResetsPaginationWhenPropsChanges` — auto-reset pagination when filter properties change
- `ClearsProperties` — bulk-clear component properties

## Key Model Conventions

| Convention | Detail |
|---|---|
| **Base model** | All models extend `src/Models/Model.php`, which implements `OwenIt\Auditing\Auditable` — every model is auto-audited |
| **Table prefix** | `getTable()` returns `config('laravel-crm.db_table_prefix').'tablename'` (default `crm_`) |
| **External IDs** | Routes use `external_id` (UUID, set by Observer on `creating`), not integer PKs |
| **Human IDs** | Observers auto-generate `lead_id`, `quote_id`, etc. as `{prefix}{number}` (e.g. `L1001`) |
| **Money** | Amounts stored as integers × 100; set via `setAmountAttribute`; display with `money($amount, $currency)` helper |
| **Encryption** | Sensitive fields (person names, emails, phones) encrypted via `LaravelEncryptableTrait`; declared in `$encryptable` array |
| **Multi-tenancy** | `BelongsToTeams` trait adds a global `BelongsToTeamsScope` — queries are automatically scoped to `auth()->user()->currentTeam` when `LARAVEL_CRM_TEAMS=true` |

## UI Stack

- **Tailwind CSS v4** + **DaisyUI v5** for styling — uses `@plugin "daisyui"` syntax in `resources/css/app.css` with custom light/dark themes
- **MaryUI** (`robsontenorio/mary`) for Livewire UI components — use `<x-mary-*>` components
- **Toast notifications** via `Mary\Traits\Toast` (already in `HasLeadCommon` and similar traits)
- **Icons**: ForkAwesome (`<x-forkawesome-*>`), Boxicons (`<x-bx-*>`), FontAwesome (`<x-far-*>` / `<x-fas-*>`)
- **JS globals**: `sortablejs` (kanban drag-and-drop) and `vanilla-picker` (color picker) exposed on `window` via `resources/js/app.js`
- All translatable strings use `__('laravel-crm::lang.key')` with `ucfirst()` — add keys to `resources/lang/`
- **Blade components** registered with `crm-` prefix: `crm-header`, `crm-delete-confirm`, `crm-index-toggle`, `crm-phones`, `crm-emails`, `crm-addresses`, `crm-note`, `crm-app-layout`, `crm-timeline-item` — view components in `src/View/Components/`
- **Global helpers** autoloaded via `composer.json` `files` array in `src/Http/Helpers/`: `SelectOptions`, `PersonName`, `AddressLine`, `AutoComplete`, `CheckAmount`, `Validate`, `PublicProperties`

## Routing & Access Control

- Routes prefixed by `LARAVEL_CRM_ROUTE_PREFIX` (default `crm`), named `laravel-crm.*`
- Protected by `auth.laravel-crm` middleware + Laravel Policies (one policy per model in `src/Policies/`)
- **Flash notifications**: `php-flasher/flasher-laravel` for server-side flash messages
- Public portal routes at `/p/quotes/{external_id}` and `/p/invoices/{external_id}` — no auth required
- Route subdomain support via `LARAVEL_CRM_ROUTE_SUBDOMAIN`
- **Settings singleton**: `SettingService` registered as `laravel-crm.settings`; shared to all views via `SettingsComposer` (`View::composer('*')`)
- **Module toggles**: `config('laravel-crm.modules')` array enables/disables features (leads, deals, quotes, orders, invoices, deliveries, purchase-orders, teams). Blade directives `@hasleadsenabled`, `@hasdealsenabled`, `@hasquotesenabled`, `@hasordersenabled`, `@hasinvoicesenabled`, `@hasdeliveriesenabled`, `@haspurchaseordersenabled`, `@hasteamsenabled` gate UI sections

## Host Apps (Development / Testing)

### Host 1: Laravel 13 (`laravelcrm-v2`)

| Aspect | Detail |
|---|---|
| **Path** | `/Users/andrewdrake/Sites/laravelcrm-v2` |
| **Laravel version** | 13.x — Livewire starter kit with Fortify + Flux UI |
| **Role** | Separate Laravel project in workspace; does **not** consume the CRM package |
| **DB** | SQLite (`database/database.sqlite`) |
| **URL** | `http://laravelcrm-v2.test` (Herd) |
| **Frontend** | Tailwind v4 + Flux UI v2 + Vite |
| **Testing** | Pest v4 |

### Host 2: Premium (`laravel-crm-premium`)

| Aspect | Detail |
|---|---|
| **Path** | `/Users/andrewdrake/Sites/laravel-crm-premium` |
| **Laravel version** | 11.x with Jetstream (Livewire stack), Sanctum, Fortify |
| **Teams** | Jetstream teams feature is **commented out** — `LARAVEL_CRM_TEAMS` not set (single-tenant mode) |
| **DB** | MySQL, database `laravel_crm_v2_dev`, no table prefix (`LARAVEL_CRM_DB_TABLE_PREFIX=` empty) |
| **URL** | `http://laravel-crm-premium.test` (Herd), CRM at `/crm` |
| **User model** | `App\Models\User` uses `HasCrmAccess`, `HasCrmTeams`, `HasRoles` (Spatie), `AuthenticationLoggable`, `SendsCrmPasswordReset`, `HasApiTokens`, `TwoFactorAuthenticatable`, `HasProfilePhoto` |
| **Frontend** | Host has its own Tailwind v3 + DaisyUI v4 build; package builds independently via its own `vite.config.js` |
| **Providers** | `AppServiceProvider`, `FortifyServiceProvider`, `JetstreamServiceProvider` |
| **Dev command** | `composer dev` (runs server + queue + logs + Vite concurrently) |

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
composer test        # PHPUnit (uses testbench, SQLite in-memory)

# Key artisan commands (run from a host app)
php artisan laravelcrm:install              # initial setup
php artisan laravelcrm:permissions          # seed roles & permissions
php artisan laravelcrm:sample-data          # generate dev sample data
php artisan laravelcrm:add-user             # add a user with CRM access
php artisan laravelcrm:labels               # seed default labels
php artisan laravelcrm:address-types        # seed address types
php artisan laravelcrm:organization-types   # seed organization types
php artisan laravelcrm:contact-types        # seed contact types
php artisan laravelcrm:encrypt              # encrypt sensitive DB fields
php artisan laravelcrm:decrypt              # decrypt sensitive DB fields
php artisan laravelcrm:xero contacts        # sync Xero contacts
php artisan laravelcrm:xero products        # sync Xero products
php artisan laravelcrm:reminders            # send activity reminders (scheduled every minute)
php artisan laravelcrm:archive              # archive old records (scheduled daily)
```

### Scheduled Tasks (auto-registered in ServiceProvider)
- `laravelcrm:reminders` — every minute (activity reminders)
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
- **Audit log**: `owen-it/laravel-auditing` — all models auto-audited; use `saveQuietly()` (defined on base `Model`) to bypass events
- **GeoIP**: `torann/geoip` + `geoip2/geoip2` for location-aware features

