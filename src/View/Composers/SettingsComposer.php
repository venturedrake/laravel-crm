<?php

namespace VentureDrake\LaravelCrm\View\Composers;

use Illuminate\View\View;

class SettingsComposer
{
    /**
     * Share the handful of settings every view formats against.
     *
     * Values come from the SettingService rather than from a cache of this
     * class's own. The service's cache is partitioned by team and dropped by
     * SettingObserver on any write; the private cache this composer used to
     * keep was neither, so one team's date format, tax name and organisation
     * details leaked into every other team's views and a settings change took
     * up to an hour to show up.
     */
    public function compose(View $view)
    {
        $defaults = [
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
            'timezone' => 'UTC',
            'taxName' => 'Tax',
            'dynamicProducts' => 'true',
        ];

        $settings = app('laravel-crm.settings');

        // Registered against every view, so this runs for views rendered
        // before the package's migrations have created crm_settings.
        if (! $settings->tableExists()) {
            $view->with([
                'crmDateFormat' => $defaults['dateFormat'],
                'crmTimeFormat' => $defaults['timeFormat'],
                'crmTimezone' => $defaults['timezone'],
                'crmTaxName' => $defaults['taxName'],
                'crmDynamicProducts' => $defaults['dynamicProducts'],
            ]);

            return;
        }

        $dynamicProducts = $settings->get('dynamic_products');

        $view->with([
            'crmDateFormat' => $settings->get('date_format') ?? $defaults['dateFormat'],
            'crmTimeFormat' => $settings->get('time_format') ?? $defaults['timeFormat'],
            'crmTimezone' => $settings->get('timezone') ?? $defaults['timezone'],
            'crmTaxName' => $settings->get('tax_name') ?? $defaults['taxName'],
            'crmDynamicProducts' => $dynamicProducts === null
                ? $defaults['dynamicProducts']
                : ($dynamicProducts == 1 ? 'true' : 'false'),
        ]);
    }
}
