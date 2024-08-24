<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Services\SettingService;

class DealObserver
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
     * Handle the deal "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function creating(Deal $deal)
    {
        $deal->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $deal->user_created_id = auth()->user()->id ?? null;
        }

        if($lastDeal = Deal::withTrashed()->orderBy('number', 'DESC')->first()) {
            $deal->number = $lastDeal->number + 1;
        } else {
            $deal->number = 1000;
        }

        $deal->prefix = $this->settingService->get('deal_prefix')->value;
        $deal->deal_id = $deal->prefix.$deal->number;
    }

    /**
     * Handle the deal "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function created(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function updating(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the deal "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function updated(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Deal  $deal
     * @return void
     */
    public function deleting(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_deleted_id = auth()->user()->id ?? null;
            $deal->saveQuietly();
        }
    }

    /**
     * Handle the deal "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function deleted(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function restored(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_deleted_id = auth()->user()->id ?? null;
            $deal->saveQuietly();
        }
    }

    /**
     * Handle the deal "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Deal  $deal
     * @return void
     */
    public function forceDeleted(Deal $deal)
    {
        //
    }
}
