# Laravel CRM — Test Suite

A PHPUnit + Orchestra Testbench based test suite covering the package's core
features and conventions.

## Running

```bash
composer test                       # run the full suite
vendor/bin/phpunit --testsuite Unit # run only unit tests
vendor/bin/phpunit --testsuite Feature
```

The suite uses an in-memory SQLite database and a custom test schema
(`tests/TestSchema.php`) that mirrors the most-used tables in the package
(leads, deals, people, organizations, settings, emails, phones, addresses,
labels, notes, activities, tasks, fields, pipelines, audits, etc.).

## Bootstrap notes

`tests/bootstrap.php` is loaded by PHPUnit before any tests run. It aliases
`App\User` to the test stub user (`tests/Stubs/User.php`) so the package
models — which import `App\User` directly — can resolve. We deliberately do
**not** alias `App\Models\User`: the package service provider would then
attempt a duplicate `class_alias()` on every test boot, fatally erroring on
the second boot in the same PHP process.

## Layout

```
tests/
  bootstrap.php                ← global bootstrap (autoload + class aliases)
  TestCase.php                 ← Orchestra Testbench base class
  TestSchema.php               ← in-memory schema used by feature tests
  Stubs/User.php               ← minimal Authenticatable stub
  Unit/                        ← pure PHP helper tests (no Laravel boot)
  Feature/                     ← package + Laravel + DB integration tests
    BootTest.php
    BladeDirectivesTest.php
    BelongsToTeamsScopeTest.php
    ConfigTest.php
    EncryptableFieldsTest.php
    FacadeTest.php
    RoutingTest.php
    ServiceProviderTest.php
    SettingServiceTest.php
    SettingsComposerTest.php
    SoftDeleteAuditingTest.php
    ArtisanCommandsTest.php
    Helpers/SelectOptionsHelperTest.php
    Models/                    ← model + observer behavior
    Services/                  ← business-logic service tests
```

## What is covered

- **Service Provider**: facade + settings singleton bindings, config merge,
  translations + views loading, middleware aliases, console commands,
  policies, blade components, named routes, route prefixing, and the
  `Collection::paginate` macro.
- **Blade Directives**: `@hasleadsenabled`, `@hasdealsenabled`, etc. honour
  the `laravel-crm.modules` config.
- **Helpers**: `PersonName`, `AddressLine`, `CheckAmount`, `Validate`,
  `PublicProperties`, `SelectOptions` (phone/email types, date/time formats,
  `optionsFromModel`, `fieldModels`).
- **SettingService**: get/set/all/first/forgetCache + cache hit/miss
  semantics.
- **SettingsComposer**: default values + DB-backed overrides.
- **Models & Observers**: prefixed table names, UUID `external_id`
  generation, `lead_id`/`deal_id` auto-increment with `prefix + number`
  pattern, money attribute (×100), soft deletes, restoration, `saveQuietly`,
  audit trail integration, polymorphic relations (emails / phones /
  addresses / notes / tasks / activities), and label sync.
- **Encryption**: `LaravelEncryptableTrait` on/off behaviour for `Person`.
- **Multi-tenancy**: `BelongsToTeamsScope` is a no-op when teams disabled;
  `allTeams` macro is registered.
- **Routing**: unauthenticated requests do not return 200 OK; route prefix
  config is honoured; user_interface flag exists.
- **Artisan Commands**: registered + (where safely runnable) execute.

## What is *not* covered

- Full Livewire component rendering (would require the entire migration
  surface area and a logged-in session for every component).
- Xero integration round-trips (third-party API).
- PDF rendering of quotes/invoices (heavy and platform dependent).
- View rendering of every Blade view (covered indirectly via the service
  provider's view-loading test).

These can be added incrementally by extending `TestSchema` with the
remaining tables and using Livewire's testing helpers.

