<?php

namespace VentureDrake\LaravelCrm\Support;

use Dcblogdev\Xero\Facades\Xero;
use Dcblogdev\Xero\XeroServiceProvider;

/**
 * Guards every entry point into the Xero integration.
 *
 * `dcblogdev/laravel-xero` is a **suggested** dependency, not a required one. It
 * caps `guzzlehttp/guzzle` at `^7.9.3` in every version it has ever published,
 * so while it sat in `require` this package was uninstallable in any app that
 * had moved to Guzzle 8 — including a stock Laravel 13.32+ app, which allows
 * `^7.8.2 || ^8.0`. Composer reported the clash against *our* Guzzle constraint
 * first, which made it look like a one-line bump; it was not, because the cap
 * came in transitively through Xero.
 *
 * So the classes under `Dcblogdev\Xero\` may simply not exist at runtime. A
 * `use` statement never autoloads, so imports of the facade are harmless, but
 * any *reference* — `Xero::isConnected()`, `XeroToken::observe(...)` — is a
 * fatal error on an install without the package. Everything that touches those
 * classes goes through here first.
 *
 * `installed()` probes the service provider rather than the facade because the
 * test suite binds a stub onto the `xero` container key without registering the
 * real provider; probing the facade class would still be correct today, but the
 * provider is the thing that actually means "the package is present".
 */
class XeroIntegration
{
    /**
     * Whether `dcblogdev/laravel-xero` is installed in the host app.
     */
    public static function installed(): bool
    {
        return class_exists(XeroServiceProvider::class);
    }

    /**
     * Whether Xero is installed *and* the host app holds a live connection.
     *
     * Callers that were previously written as `if (Xero::isConnected())` want
     * this one — it collapses "not installed" and "not connected" into the same
     * no-op branch they already handle.
     */
    public static function connected(): bool
    {
        return static::installed() && Xero::isConnected();
    }
}
