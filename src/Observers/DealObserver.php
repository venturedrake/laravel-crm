<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Services\NumberGeneratorService;
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
     * @return void
     */
    public function creating(Deal $deal)
    {
        $deal->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $deal->user_created_id = auth()->user()->id ?? null;
        }

        $deal->number = NumberGeneratorService::next(Deal::class, 1000);

        $deal->prefix = $this->settingService->get('deal_prefix');
        $deal->deal_id = $deal->prefix.$deal->number;
    }

    /**
     * Handle the deal "created" event.
     *
     * @return void
     */
    public function created(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "updating" event.
     *
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
     * @return void
     */
    public function deleted(Deal $deal)
    {
        //
    }

    /**
     * Handle the deal "restored" event.
     *
     * @return void
     */
    public function restored(Deal $deal)
    {
        if (! app()->runningInConsole()) {
            $deal->user_deleted_id = null;
            $deal->saveQuietly();
        }
    }

    /**
     * Handle the deal "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Deal $deal)
    {
        //
    }
}
