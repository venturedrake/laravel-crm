<?php

namespace VentureDrake\LaravelCrm\View\Composers;

use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use VentureDrake\LaravelCrm\Models\Setting;

class SettingsComposer
{
    public function compose(View $view)
    {
        if (Schema::hasTable(config('laravel-crm.db_table_prefix').'settings')) {
            $view->with('dateFormat', Setting::where('name', 'date_format')->first()->value ?? 'Y/m/d');
            $view->with('timeFormat', Setting::where('name', 'time_format')->first()->value ?? 'H:i');
            $view->with('timezone', Setting::where('name', 'timezone')->first()->value ?? 'UTC');
            $view->with('taxName', Setting::where('name', 'tax_name')->first()->value ?? 'Tax');

            if($setting = Setting::where('name', 'dynamic_products')->first()) {
                if($setting->value == 1) {
                    $view->with('dynamicProducts', 'true');
                } else {
                    $view->with('dynamicProducts', 'false');
                }
            } else {
                $view->with('dynamicProducts', 'true');
            }
        } else {
            $view->with('dateFormat', 'Y/m/d');
            $view->with('timeFormat', 'H:i');
            $view->with('timezone', 'UTC');
            $view->with('taxName', 'Tax');
        }
    }
}
