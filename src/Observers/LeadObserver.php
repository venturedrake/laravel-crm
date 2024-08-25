<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Services\SettingService;

class LeadObserver
{
    /**
     * @var SettingService
     */
    private $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Handle the lead "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function creating(Lead $lead)
    {
        $lead->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $lead->user_created_id = auth()->user()->id ?? null;
        }

        if($lastLead = Lead::withTrashed()->orderBy('number', 'DESC')->first()) {
            $lead->number = $lastLead->number + 1;
        } else {
            $lead->number = 1000;
        }

        $lead->prefix = $this->settingService->get('lead_prefix')->value;
        $lead->lead_id = $lead->prefix.$lead->number;
    }

    /**
     * Handle the lead "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function created(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function updating(Lead $lead)
    {
        if (! app()->runningInConsole()) {
            $lead->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the lead "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function updated(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Lead  $lead
     * @return void
     */
    public function deleting(Lead $lead)
    {
        if (! app()->runningInConsole()) {
            $lead->user_deleted_id = auth()->user()->id ?? null;
            $lead->saveQuietly();
        }
    }

    /**
     * Handle the lead "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function deleted(Lead $lead)
    {
        //
    }

    /**
     * Handle the lead "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function restored(Lead $lead)
    {
        if (! app()->runningInConsole()) {
            $lead->user_deleted_id = auth()->user()->id ?? null;
            $lead->saveQuietly();
        }
    }

    /**
     * Handle the lead "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Lead  $lead
     * @return void
     */
    public function forceDeleted(Lead $lead)
    {
        //
    }
}
