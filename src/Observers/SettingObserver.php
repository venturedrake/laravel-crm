<?php

namespace VentureDrake\LaravelCrm\Observers;

use VentureDrake\LaravelCrm\Models\Setting;

class SettingObserver
{
    /**
     * Handle the email "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Setting  $setting
     * @return void
     */
    public function creating(Setting $setting)
    {
        if ($setting->global) {
            switch ($setting->name) {
                case "app_name":
                case "app_env":
                case "app_url":
                case "version":
                case "install_id":
                case "version_latest":
                    $setting->global = 1;

                    break;
            }
        }
    }
    
    /**
     * Handle the Setting "created" event.
     *
     * @param  \App\Models\Setting  $setting
     * @return void
     */
    public function created(Setting $setting)
    {
        //
    }

    /**
     * Handle the email "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Setting  $setting
     * @return void
     */
    public function updating(Setting $setting)
    {
    }

    /**
     * Handle the Setting "updated" event.
     *
     * @param  \App\Models\Setting  $setting
     * @return void
     */
    public function updated(Setting $setting)
    {
        //
    }

    /**
     * Handle the Setting "deleted" event.
     *
     * @param  \App\Models\Setting  $setting
     * @return void
     */
    public function deleted(Setting $setting)
    {
        //
    }

    /**
     * Handle the Setting "restored" event.
     *
     * @param  \App\Models\Setting  $setting
     * @return void
     */
    public function restored(Setting $setting)
    {
        //
    }

    /**
     * Handle the Setting "force deleted" event.
     *
     * @param  \App\Models\Setting  $setting
     * @return void
     */
    public function forceDeleted(Setting $setting)
    {
        //
    }
}
