<?php

namespace VentureDrake\LaravelCrm\Support;

/**
 * Reads the `laravel-crm.modules` toggle.
 *
 * This is the same rule the `@hasleadsenabled` / `@hasquotesenabled` / ... Blade
 * directives apply, lifted out so PHP callers (Livewire components deciding
 * which tabs to render, say) get the same answer as a blade without hand-rolling
 * a fourteenth copy of it.
 *
 * The implementation looks redundant and is not. The rule is:
 *
 *   - a list containing the slug  → enabled
 *   - a falsy config value        → enabled ("unconfigured means everything on")
 *   - anything else               → disabled
 *
 * and the second clause is load-bearing for `[]` as well as `null`, because
 * `! []` is `true`. So the tidier-looking
 *
 *   return is_array($modules) ? in_array($slug, $modules, true) : true;
 *
 * is *wrong*: it turns an empty array from "every module on" into "every module
 * off", silently blanking the entire CRM for any host that published the config
 * with the array emptied out. Both shapes are pinned in
 * tests/Feature/BladeDirectivesTest.php and tests/Feature/Support/ModulesTest.php.
 */
class Modules
{
    /**
     * Whether the module `$slug` (`leads`, `purchase-orders`, ...) is enabled.
     */
    public static function enabled(string $slug): bool
    {
        $modules = config('laravel-crm.modules');

        if (is_array($modules) && in_array($slug, $modules)) {
            return true;
        }

        return ! $modules;
    }

    /**
     * Whether any one of `$slugs` is enabled. An empty list is "no module
     * requirement", so it is always true — which is what lets a caller pass a
     * tab's module list straight through without special-casing the ungated
     * ones.
     *
     * @param  array<int, string>  $slugs
     */
    public static function anyEnabled(array $slugs): bool
    {
        if ($slugs === []) {
            return true;
        }

        foreach ($slugs as $slug) {
            if (self::enabled($slug)) {
                return true;
            }
        }

        return false;
    }
}
