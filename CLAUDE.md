# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Repo Is

A Laravel **package** (`venturedrake/laravel-crm`) that installs a full CRM into any Laravel application. All source lives under `src/` with namespace `VentureDrake\LaravelCrm\`. Supports Laravel 10–13, PHP 8.1+, Livewire 3–4.

This package is developed in a **multi-project workspace**. Changes here take effect immediately in host apps (via Composer path repository) — no publish step needed for code:

| Project | Path | Purpose |
|---|---|---|
| **This package** | `/Users/andrewdrake/Packages/laravel-crm` | All CRM source code |
| **Laravel 12 host** | `/Users/andrewdrake/Sites/laravel-12-crm-v2` | Primary dev/test host |
| **Premium host** | `/Users/andrewdrake/Sites/laravel-crm-premium` | Laravel 11 + Jetstream + Xero |

## Commands

```bash
# Frontend
npm run dev          # Vite watch mode
npm run build        # Production build (outputs to public/vendor/laravel-crm/)

# PHP code style
composer format      # Run Laravel Pint formatter
composer format-test # Dry-run formatting check

# Tests (Pest, not PHPUnit — uses testbench, SQLite in-memory)
composer test
composer test-coverage  # Generates HTML coverage report

# Key artisan commands (run from a host app)
php artisan laravelcrm:install              # Initial setup
php artisan laravelcrm:permissions          # Seed roles & permissions
php artisan laravelcrm:sample-data          # Generate dev sample data
php artisan laravelcrm:add-user             # Add a user with CRM access
php artisan laravelcrm:encrypt              # Encrypt sensitive DB fields
php artisan laravelcrm:decrypt              # Decrypt DB fields
php artisan laravelcrm:address-types        # Seed address types
php artisan laravelcrm:contact-types        # Seed contact types
php artisan laravelcrm:organization-types   # Seed organization types
php artisan laravelcrm:fields               # Manage custom fields
php artisan laravelcrm:labels               # Manage labels
php artisan laravelcrm:email-campaigns      # Dispatch email campaigns (scheduled)
php artisan laravelcrm:sms-campaigns        # Dispatch SMS campaigns (scheduled)
php artisan laravelcrm:reminders            # Send reminder notifications (scheduled)
php artisan laravelcrm:update               # Check for/apply package updates
php artisan laravelcrm:archive              # Archive old records
php artisan laravelcrm:v2                   # V1→V2 migration helper
php artisan laravelcrm:xero                 # Xero integration utilities
```

## Architecture

### Layered Stack

```
Routes (src/Http/routes.php)
  → Controllers (src/Http/Controllers/)  ← return views/redirects only, no business logic
    → Views (resources/views/)           ← load Livewire components
      → Livewire (src/Livewire/)         ← all UI state and form logic
        → Services (src/Services/)       ← create/update orchestration
          → Models (src/Models/)         ← Eloquent + Observers
```

Repositories (`src/Repositories/`) are thin wrappers — put business logic in Services.

### Two Livewire Namespaces

- **`src/Livewire/`** — current components, registered with `crm-` prefix (e.g. `crm-lead-index`)
- **`src/Http/Livewire/`** — legacy sub-components, registered with short names (e.g. `live-lead-form`)

All components are manually registered in `LaravelCrmServiceProvider` (not auto-discovered). When adding a new Livewire component, register it there.

### Key Source Directories

| Directory | Purpose |
|---|---|
| `src/Models/` | 75+ Eloquent models, all extend `src/Models/Model.php` |
| `src/Services/` | Business logic for create/update (LeadService, DealService, etc.) |
| `src/Observers/` | 58 observers — generate IDs, trigger audit events |
| `src/Policies/` | 41 authorization policies, one per model |
| `src/Console/` | 18 artisan commands |
| `src/Livewire/` | Current Livewire components by domain |
| `src/Http/Livewire/` | Legacy inline/sub-components |
| `src/Traits/` | Shared traits for models and Livewire components |
| `src/View/Components/` | Blade components registered with `crm-` prefix |
| `src/Http/Helpers/` | Global helpers autoloaded via composer (SelectOptions, PersonName, etc.) |
| `src/Http/Middleware/` | 10 custom middleware classes |
| `src/Events/` | Broadcast events (e.g. `ChatMessageSent`) |
| `src/Listeners/` | Event listeners (e.g. `NewAuthDevice` for 2FA) |
| `src/Jobs/` | Queued jobs for campaign dispatch (email, SMS) |
| `src/Mail/` | Mailable classes (SendInvoice, SendQuote, SendPurchaseOrder, EmailCampaignMessage) |
| `src/Notifications/` | Reminder notifications (call, task, meeting, lunch reminders) |
| `src/Scopes/` | Query scopes (`BelongsToTeamsScope`) |
| `resources/views/livewire/` | Livewire component views |
| `resources/lang/` | i18n strings under `laravel-crm::` namespace |
| `database/migrations/` | Migration `.stub` files published to host app during install |

## Model Conventions

| Convention | Detail |
|---|---|
| **Base model** | All models extend `src/Models/Model.php`, which implements `OwenIt\Auditing\Auditable` and defines `saveQuietly()` |
| **Table prefix** | `getTable()` prepends `config('laravel-crm.db_table_prefix')` (default `crm_`) |
| **External IDs** | Routes use `external_id` (UUID set by Observer on `creating`), not integer PKs |
| **Human IDs** | Observers auto-generate `lead_id`, `quote_id`, etc. (e.g. `L1001`) |
| **Money** | Stored as integers ×100; set via `setAmountAttribute`; display with `money($amount, $currency)` |
| **Encryption** | Sensitive fields (names, emails, phones) encrypted via `HasEncryptableFields` trait; declared in `$encryptable` |
| **Soft audit bypass** | Use `saveQuietly()` (defined on base Model) to skip observers/events |
| **Multi-tenancy** | `BelongsToTeams` trait + `BelongsToTeamsScope` add global scope to `currentTeam` when `laravel-crm.teams=true` |
| **Custom fields** | `HasCrmFields` trait on relevant models; values stored in `FieldValue` records |

## UI Stack

- **Tailwind CSS v4** + **DaisyUI v5** (`@plugin "daisyui"` syntax in `resources/css/app.css`)
- **MaryUI** (`robsontenorio/mary`) — use `<x-mary-*>` components for UI elements
- **Livewire 3** with `#[Url]` attributes for query-string binding
- **Toast notifications** via `Mary\Traits\Toast` (already in `HasLeadCommon` and similar traits)
- **Icons**: ForkAwesome (`<x-forkawesome-*>`), Boxicons (`<x-bx-*>`), FontAwesome (`<x-far-*>` / `<x-fas-*>`)
- **JS globals**: `sortablejs` (kanban drag-and-drop), `vanilla-picker` (color picker), `Chart.js` (dashboard charts) on `window`
- **Rich text**: TinyMCE 7 for email template and campaign editors
- **Blade components** with `crm-` prefix: `crm-header`, `crm-delete-confirm`, `crm-index-toggle`, `crm-phones`, `crm-emails`, `crm-addresses`, `crm-custom-fields`, `crm-note`, `crm-timeline-item`
- All translatable strings: `ucfirst(__('laravel-crm::lang.key'))` — add keys to `resources/lang/`
- Theme: two DaisyUI themes (`light` / `dark`); primary `#05b3a9`, secondary `#6505B3`, accent `#B34105`

## Routing & Access Control

- All routes prefixed by `config('laravel-crm.route_prefix')` (default `crm`), named `laravel-crm.*`
- Protected by `auth.laravel-crm` middleware + Laravel Policies
- Public portal (no auth): `/p/quotes/{external_id}`, `/p/invoices/{external_id}`
- Chat widget embed: `/p/chat/{publicKey}` — served as iframe HTML, registered in `src/Http/chat-embed-routes.php` outside `web` middleware (no CSRF/session)
- Module toggles: `config('laravel-crm.modules')` array; Blade directives `@hasleadsenabled`, `@hasdealsenabled`, etc.
- Settings singleton: `SettingService` at `laravel-crm.settings`; shared to all views via `SettingsComposer`
- Subdomain support: `config('laravel-crm.route_subdomain')` (optional)

## Adding a New CRM Entity

1. Migration stub in `database/migrations/` with `crm_` prefix, include `external_id`, `team_id`, `user_created_id`, `user_updated_id`
2. Model in `src/Models/` extending base `Model`
3. Observer in `src/Observers/` — set `external_id` (UUID) and auto-increment `*_id` on `creating`
4. Policy in `src/Policies/` — register in `ServiceProvider::$policies`
5. Service in `src/Services/` with `create()` / `update()` methods
6. Repository in `src/Repositories/` (thin wrapper)
7. Livewire components in `src/Livewire/{Entity}/`: `EntityIndex`, `EntityShow`, `EntityCreate`, `EntityEdit` + `Traits/HasEntityCommon`
8. Controller in `src/Http/Controllers/` returning views
9. Routes in `src/Http/routes.php` under a named group
10. Views in `resources/views/{entity}/`
11. Register Livewire components and Observer in `LaravelCrmServiceProvider`

## Key Integrations

- **Xero**: `dcblogdev/laravel-xero` (pinned 1.1.3) — mirror models in `src/Models/Xero*.php`; sync via observers; settings managed via `src/Livewire/Settings/Integrations/Xero/`
- **ClickSend SMS**: `src/Services/ClickSendService.php` — SMS campaign dispatch; settings keys `clicksend_username`, `clicksend_api_key`, `clicksend_default_from`
- **Spatie Permissions**: roles seeded by `laravelcrm:permissions`; `laravel-crm.teams=true` requires Spatie teams mode
- **PDF**: `barryvdh/laravel-dompdf` and `mpdf/mpdf` for quotes/invoices
- **Audit log**: `owen-it/laravel-auditing` — all models auto-audited
- **Flash notifications**: `php-flasher/flasher-laravel` for non-Livewire flash messages
