# AGENTS.md — Laravel CRM

A Laravel **package** (`venturedrake/laravel-crm`) that installs a full CRM into any Laravel application. All source lives under `src/` with namespace `VentureDrake\LaravelCrm\`.

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
- **`src/Livewire/`** (`VentureDrake\LaravelCrm\Livewire\`) — current, full-page index/show/create/edit components
- **`src/Http/Livewire/`** (`VentureDrake\LaravelCrm\Http\Livewire\`) — legacy inline/sub-components (forms, boards, activity panels)

All components are registered in `LaravelCrmServiceProvider`.

### Shared form state via traits
Create and Edit components for each entity share a `HasXxxCommon` trait (e.g., `src/Livewire/Leads/Traits/HasLeadCommon.php`) that holds all public properties, validation rules, and injected services. Add new form fields to the common trait.

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
| **Custom fields** | `HasCrmFields` trait adds `fields()` morph relation; `FieldValue` records auto-created on model `created` event |
| **Activities** | `HasCrmActivities` trait adds morph relations: `tasks()`, `calls()`, `meetings()`, `lunches()`, `notes()`, `files()` |

## UI Stack

- **Tailwind CSS v4** + **DaisyUI v5** for styling
- **MaryUI** (`robsontenorio/mary`) for Livewire UI components — use `<x-mary-*>` components
- **Toast notifications** via `Mary\Traits\Toast` (already in `HasLeadCommon` and similar traits)
- **Icons**: ForkAwesome (`<x-forkawesome-*>`), Boxicons (`<x-bx-*>`), FontAwesome (`<x-far-*>` / `<x-fas-*>`)
- All translatable strings use `__('laravel-crm::lang.key')` with `ucfirst()` — add keys to `resources/lang/`

## Routing & Access Control

- Routes prefixed by `LARAVEL_CRM_ROUTE_PREFIX` (default `crm`), named `laravel-crm.*`
- Protected by `auth.laravel-crm` middleware + Laravel Policies (one policy per model in `src/Policies/`)
- Public portal routes at `/p/quotes/{external_id}` and `/p/invoices/{external_id}` — no auth required
- Route subdomain support via `LARAVEL_CRM_ROUTE_SUBDOMAIN`

## Developer Workflows

```bash
# Install/update dependencies
composer install
npm install

# Build frontend assets (outputs to vendor/laravel-crm, NOT public/build)
npm run build
npm run dev          # watch mode

# Code style (Laravel preset)
./vendor/bin/pint

# Run tests (Orchestra Testbench)
./vendor/bin/phpunit

# Artisan commands (run in the host app)
php artisan laravelcrm:install       # full install wizard
php artisan laravelcrm:update        # run after package updates
php artisan laravelcrm:permissions   # sync Spatie permission records
php artisan laravelcrm:fields        # seed default custom fields
```

## Adding a New CRM Entity (checklist)

1. Migration with `crm_` prefix table and `external_id`, `team_id`, `user_created_id`, `user_updated_id` columns
2. Model in `src/Models/` extending `Model`, using `BelongsToTeams`, `HasCrmActivities`, `HasCrmFields`, `SoftDeletes`
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

