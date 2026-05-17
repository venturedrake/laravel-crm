# Filament v5 Plugin for Laravel CRM — Plan

> Status: **Phase 1 substantially complete** — five Resources + two Dashboard widgets live in `laravel-13-crm-v2`; auth wired (`App\Models\User implements FilamentUser`).
> Target: Filament v5.6+ (stable). Coexists with existing `/crm` legacy UI.
> Last updated: 2026-05-17
> Plugin repo: `/Users/andrewdrake/Packages/laravel-crm-filament` (initial git history: `main` branch, single commit).
> Primary host for plugin dev/test: `/Users/andrewdrake/Sites/laravel-13-crm-v2` (Laravel 13, PHP 8.3, Livewire 4, Pest 4).

## Current state (end of Phase 0 / start of Phase 1)

- `composer.json` → `venturedrake/laravel-crm-filament`, requires PHP `^8.2`, Laravel `^11|^12|^13`, Filament `^5.0`, `venturedrake/laravel-crm: ^2.0|dev-develop`.
- `src/LaravelCrmFilamentServiceProvider.php` (Spatie `PackageServiceProvider`) — registers commands, publishes the panel stub.
- `src/LaravelCrmPlugin.php` implements `Filament\Contracts\Plugin` with `getId(): 'laravel-crm'`, fluent option helpers (`modules()`, `withChat()`, `withEmailMarketing()`, `withSmsMarketing()`, `withXero()`, `navigationGroup()`, `brand()`), `isModuleEnabled()` falling back to the core CRM's flat-array `config('laravel-crm.modules')`, and a `register(Panel)` that attaches Resources for each enabled module.
- `src/Console/InstallCommand.php` → `php artisan laravelcrm:filament-install` publishes `app/Providers/Filament/CrmPanelProvider.php` and auto-registers it in `bootstrap/providers.php`.
- `stubs/CrmPanelProvider.php.stub` — Filament v5 PanelProvider at `/admin`, primary colour `#05b3a9`, registers `LaravelCrmPlugin::make()` (all module flags commented out).
- `src/Support/FormPayload.php` — wraps Filament form-data arrays as `Illuminate\Support\Fluent` so existing services (`LeadService`, etc.) that use `$request->property` access keep working.
- **Phase 1 deliverables landed:**
  - `App\Models\User implements FilamentUser` in the host, with `canAccessPanel()` delegating to `HasCrmAccess::hasCrmAccess()`.
  - Five full-CRUD Resources, each routing records by `external_id` and delegating create/update to the matching `src/Services/*Service` in core via `FormPayload`:
    - `Resources/Leads/LeadResource` (module-gated)
    - `Resources/Deals/DealResource` (module-gated)
    - `Resources/People/PersonResource` (always on)
    - `Resources/Organizations/OrganizationResource` (always on)
    - `Resources/Tasks/TaskResource` (always on, with default "open only" filter)
  - Two Dashboard widgets attached via `LaravelCrmPlugin::register()` → `$panel->widgets()`:
    - `Widgets/CrmStatsOverview` — open leads / open deals / tasks due today
    - `Widgets/LeadsByStageChart` — bar chart of open leads per pipeline stage
  - Plugin registers 5 resources + 2 widgets (4 total counting Filament's defaults); panel `id='crm'`, path `/admin`.
  - Tests: `LeadResourceTest`, `ResourceCoverageTest` (dataset-driven across Deal/Person/Organization/Task), `PluginRegistrationTest`.
- Host (`laravel-13-crm-v2`) wired via Composer path repo `../../Packages/laravel-crm-filament`; install command run. Smoke-tested authenticated:
  - `/admin` → HTTP 200 (~65 KB), all 4 widget Livewire components rendered
  - `/admin/leads` → 200 (279 KB), `/admin/deals` → 200 (296 KB), `/admin/people` → 200 (207 KB), `/admin/organizations` → 200 (188 KB), `/admin/tasks` → 200 (261 KB)

**Still pending:** tag `v0.1.0-alpha` (held until plugin is published to a real GitHub remote).

## Goal

Build a fully native Filament v5 plugin that wraps the existing `venturedrake/laravel-crm` domain layer (models, services, observers, policies, migrations) and exposes it via `LaravelCrmPlugin::make()` for any host Filament Panel.

- **UI strategy:** Option A — full native Filament rebuild (Resources, Pages, Widgets, Clusters, RelationManagers). No embedded MaryUI/Livewire components in the plugin.
- **Coexistence:** legacy `/crm` MaryUI/Livewire UI stays fully intact and untouched. The Filament panel runs alongside it (e.g. at `/admin`), sharing the same database, services, and policies.
- **Repo strategy:** ship as a **separate package** — `venturedrake/laravel-crm-filament` in its own git repo. Core `venturedrake/laravel-crm` is unchanged apart from minor extension-point hardening.

## Why a separate repo

1. **Dependency matrix.** Core supports Laravel 10–13 / PHP 8.1+; Filament v5 requires Laravel 11+ / PHP 8.2+. Splitting avoids forcing an upgrade on existing hosts.
2. **Release cadence.** Filament moves quickly; plugin patch releases can ship without touching core CRM.
3. **Install footprint.** Legacy `/crm` users don't pull Filament's 30+ transitive deps. Filament-only users still get the full CRM domain layer via the core dependency.
4. **Clean coexistence.** The plugin package just requires `venturedrake/laravel-crm: ^2.x` and registers its own ServiceProvider + Plugin class.
5. **Discoverability.** Listable on filamentphp.com/plugins with its own README, screenshots, docs, issue tracker.
6. **Test isolation.** Plugin's Pest/Testbench suite only needs Filament + a Panel; core CRM tests stay focused on services/models/observers.

## Repo & install layout

```
venturedrake/laravel-crm                  ← unchanged
  src/Models, Services, Observers, Policies, Livewire (legacy /crm UI)

venturedrake/laravel-crm-filament         ← NEW
  composer.json
    require:
      php: ^8.2
      laravel/framework: ^11.0|^12.0|^13.0
      filament/filament: ^5.6
      venturedrake/laravel-crm: ^2.0
  src/
    LaravelCrmFilamentServiceProvider.php
    LaravelCrmPlugin.php
    Resources/{Lead,Deal,Quote,Order,Invoice,Delivery,PurchaseOrder,
               Person,Organization,Product,Task,User,Team}/
    Pages/{Dashboard,LeadBoard,DealBoard,QuoteBoard,Chat,
           EmailCampaignBuilder,SmsCampaignBuilder}/
    Widgets/{LeadsByStageChart,DealsValueStat,RecentActivityList,TasksDueTodayList}
    Clusters/Settings/
    Concerns/   (form schemas, table helpers, CustomFieldsSchema)
    stubs/CrmPanelProvider.php.stub
  tests/Feature/...
  docs/
```

Host install:
```bash
composer require venturedrake/laravel-crm            # always
composer require venturedrake/laravel-crm-filament   # optional, adds Filament panel
```

Host PanelProvider:
```php
->plugin(LaravelCrmPlugin::make()->modules([...]))
```

## Implementation steps

### 1. Scaffold the plugin entry point
- Create `src/LaravelCrmPlugin.php` implementing `Filament\Contracts\Plugin`:
  - `getId(): 'laravel-crm'`
  - `make()`, `register(Panel $panel)`, `boot(Panel $panel)`
  - Fluent options: `->modules([...])`, `->withChat()`, `->withXero()`, `->withEmailMarketing()`, `->withSmsMarketing()`, `->brand()`, `->navigationGroup()`
- `LaravelCrmFilamentServiceProvider` registers translations, views, and any plugin-specific config.
- No changes to core `LaravelCrmServiceProvider` routing — `/crm` keeps working.

### 2. Domain Resources (native rebuild)
For each model in `venturedrake/laravel-crm` `src/Models/`:
`Lead`, `Deal`, `Quote`, `Order`, `Invoice`, `Delivery`, `PurchaseOrder`,
`Person`, `Organization`, `Product`, `Task`, `User`, `Team`.

For each:
- `Resources/{Entity}/{Entity}Resource.php` + `Pages/{List,Create,Edit,View}{Entity}.php`
- `form()` built with `Forms\Components\*` (TextInput, Select, Repeater for line items, custom `MoneyInput` wrapper for integer×100)
- `table()` columns/filters/sorts mirroring current index pages
- `infolist()` for show pages
- `getRecordRouteKeyName()` returns `external_id`
- Page hooks (`mutateFormDataBefore*`, `handleRecordCreation/Update`) delegate to existing services in `src/Services/` (e.g. `LeadService::create()`) to preserve observers, number generation, encryption, audit behaviour.

### 3. Settings Cluster
- `Clusters/Settings.php` groups settings Resources under one navigation node.
- Resources for `Pipeline`, `PipelineStage`, `Label`, `LeadSource`, `TaxRate`, `Field`, `FieldGroup`, `ProductCategory`, `ProductAttribute`, `ChatWidget`, `EmailTemplate`, `SmsTemplate`, `Role` (Spatie).
- Custom `SettingsPage` for the key/value `Setting` model edited via `SettingService`.
- `IntegrationsPage` for Xero/ClickSend connect flows (native Filament forms backed by existing service classes).

### 4. Custom Pages & Widgets
- `Pages/Dashboard` extends `Filament\Pages\Dashboard` with widgets:
  - `LeadsByStageChart`, `DealsValueStat`, `RecentActivityList`, `TasksDueTodayList`
  (replaces `Livewire/Dashboard.php`).
- `Pages/LeadBoardPage`, `DealBoardPage`, `QuoteBoardPage` — native Filament `Page` classes with Alpine + sortablejs drag-and-drop. Replace `Livewire/KanbanBoard.php`.
- `Pages/ChatPage` — conversations list + reply view; wires to `Events/ChatMessageSent` broadcast.
- `Pages/EmailCampaignBuilderPage` / `SmsCampaignBuilderPage` — use Filament's `RichEditor` for templates.

### 5. RelationManagers (inline activity / related lists)
Under each parent Resource's `RelationManagers/`:
- `NotesRelationManager`, `TasksRelationManager`, `CallsRelationManager`, `MeetingsRelationManager`, `LunchesRelationManager`, `FilesRelationManager`
- `RelatedPeopleRelationManager`, `RelatedOrganizationsRelationManager`, `RelatedDealsRelationManager`
- Pipeline-specific: `OrdersRelationManager` (on Quote), `InvoicesRelationManager` (on Order), `DeliveriesRelationManager` (on Order), `PurchaseOrderLinesRelationManager` (on PurchaseOrder), etc.
- Address / Email / Phone editing handled via Filament `Repeater` components on the parent form (not RelationManagers).

### 6. Auth, access control, tenancy, modules, custom fields
In `LaravelCrmPlugin::register()`:
- Conditionally register Resources based on `config('laravel-crm.modules')` and plugin flags (`->withChat()` etc.), mirroring `@hasleadsenabled` directives.
- Reuse policies in `src/Policies/` — Filament auto-picks them up via Laravel Gate.
- Document `FilamentUser::canAccessPanel()` requirement; add the interface implementation to `HasCrmAccess` trait (or document override in host).
- When `LARAVEL_CRM_TEAMS=true`, recommend `$panel->tenant(Team::class)` in host PanelProvider; `BelongsToTeamsScope` continues to scope queries automatically.
- `CustomFieldsSchema` helper builds a reusable `Forms\Components\Section` from `Field` model values (`HasCrmFields`); injectable into any Resource form.
- Add `ui_drivers` config key in core `config/laravel-crm.php` (`['legacy', 'filament']`) — purely informational; both can run simultaneously.

### 7. Tests, docs, demo PanelProvider, install command
- Pest tests under `tests/Feature/` exercising each Resource via `livewire(ListLeads::class)` etc.
- Publishable stub `src/stubs/CrmPanelProvider.php.stub` — drop-in panel at `/admin`.
- Artisan command `php artisan laravelcrm:filament-install`:
  - publishes the PanelProvider stub
  - registers it in `bootstrap/providers.php`
  - reminds user to add `HasCrmAccess` + `FilamentUser` to `App\Models\User`
- Docs in plugin repo `docs/` and a new "Filament Plugin" section in `/Users/andrewdrake/Sites/laravel-crm-docs`.
- Update `AGENTS.md` / `CLAUDE.md` in both repos with the new workspace entry.

## Phased delivery

| Phase | Scope |
|---|---|
| **0** | New repo scaffold + `composer.json` + `LaravelCrmPlugin` + empty Panel + install command. Tag `v0.1.0-alpha`. |
| **1** | Dashboard + Resources: Leads, Deals, People, Organizations, Tasks. |
| **2** | Resources: Quotes, Orders, Invoices, Deliveries, PurchaseOrders, Products. |
| **3** | Settings Cluster + custom fields schema + Roles (Spatie). |
| **4** | Chat, Email Marketing, SMS Marketing, Xero integration. |
| **5** | Polish: theming, branding options, full RelationManager coverage, 1.0 release. |

## Workspace addition

Add to `AGENTS.md` (Workspace Overview table):

| Project | Path | Purpose |
|---|---|---|
| **Filament Plugin** (new) | `/Users/andrewdrake/Packages/laravel-crm-filament` | Filament v5 plugin wrapping the core CRM; consumed by Laravel 13 host via Composer path repo alongside the core package |

Wire it into `/Users/andrewdrake/Sites/laravel-13-crm-v2` as a second Composer path repository so both packages are symlinked and editable live. Filament panel served at `/admin`; legacy CRM continues at `/crm`.

## Small changes still needed in core repo

1. **Stabilise public API.** Treat Services, Models, Policies, Scopes, and config keys as semver-stable from the next `v2.x` release.
2. **Optional helper.** Add `LaravelCrm::resolveUserModel()` (or similar) so the plugin can resolve the configured User class without reaching into internals.
3. **No breaking changes required.** Plugin can be built against the current core surface.

## Open questions / considerations

1. **Service signatures.** Existing services accept request/array shapes coupled to current Livewire forms (nested products, addresses). Decision: **(A) shape Filament form output to match existing service signatures** — faster, lower risk, recommended.
2. **Encrypted-field search.** Filament table search will need to use `SearchesEncryptableContacts`-equivalent logic when encryption is enabled.
3. **Asset pipeline.** Filament has its own theme/asset pipeline; plugin should not ship its own Tailwind build. Theming via Filament colour/brand options only.
4. **Subdomain routing.** Filament panels support domain config; plugin must document interplay with `LARAVEL_CRM_ROUTE_SUBDOMAIN`.
5. **Public portal & tracking routes.** These stay in core — UI-driver independent.

## Next action (when resuming)

**Phase 1 close-out (small):**

1. **Person / Organization detail polish** — add address/email/phone repeaters to the create+edit forms so `PersonService::updatePersonPhones/Emails/Addresses` get real input (currently their service helpers are called with `null` and harmlessly skip).
2. **Encrypted-field table search** — Person/Organization columns are searchable in the legacy `/crm` UI via `SearchesEncryptableContacts`. Port that logic to a Filament `Concern` so the Resource tables search encrypted names when `LARAVEL_CRM_ENCRYPT_DB_FIELDS=true`.
3. **Smoke + Livewire feature tests** — add `livewire(ListLeads::class)->assertCanSeeTableRecords(...)` and a `get('/admin')->assertOk()` panel-boot test that runs in Testbench (not just the host).

Then **tag `v0.2.0`** on the plugin repo (after Phase 1 close-out) — actual GitHub push still pending.

**Phase 2 — kick-off next:**

1. **Pipeline / sales Resources** — `Quotes`, `Orders`, `Invoices`, `Deliveries`, `PurchaseOrders`, `Products`. Each needs:
   - Line-item `Repeater` (quote/order/invoice products, taxes, totals)
   - Money handling via a `MoneyInput` wrapper that round-trips integer×100
   - Pipeline-specific "send" action (`QuoteSend`, `InvoiceSend`, `PurchaseOrderSend`) — call the existing `Mail/` mailables
2. **RelationManagers on contact + pipeline parents** — Notes / Tasks / Calls / Meetings / Lunches / Files, plus RelatedPeople / RelatedOrganizations / RelatedDeals.
3. **Public portal links** — surface "Open portal" actions on Quote/Invoice rows pointing at `/p/quotes/{external_id}` and `/p/invoices/{external_id}`.

