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
        } else {
            $view->with('dateFormat', 'Y/m/d');
            $view->with('timeFormat', 'H:i');
        }
    }
}
