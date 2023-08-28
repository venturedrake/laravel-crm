<?php

namespace VentureDrake\LaravelCrm\Observers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\SettingService;

class QuoteObserver
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
     * Handle the quote "creating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function creating(Quote $quote)
    {
        $quote->external_id = Uuid::uuid4()->toString();

        if (! app()->runningInConsole()) {
            $quote->user_created_id = auth()->user()->id ?? null;
        }

        if($lastQuote = Quote::withTrashed()->orderBy('number', 'DESC')->first()) {
            $quote->number = $lastQuote->number + 1;
        } else {
            $quote->number = 1000;
        }

        $quote->prefix = $this->settingService->get('quote_prefix')->value;
        $quote->quote_id = $quote->prefix.$quote->number;
    }

    /**
     * Handle the quote "created" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function created(Quote $quote)
    {
        //
    }

    /**
     * Handle the quote "updating" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function updating(Quote $quote)
    {
        if (! app()->runningInConsole()) {
            $quote->user_updated_id = auth()->user()->id ?? null;
        }
    }

    /**
     * Handle the quote "updated" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function updated(Quote $quote)
    {
        //
    }

    /**
     * Handle the quote "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\Quote  $quote
     * @return void
     */
    public function deleting(Quote $quote)
    {
        if (! app()->runningInConsole()) {
            $quote->user_deleted_id = auth()->user()->id ?? null;
            $quote->saveQuietly();
        }
    }

    /**
     * Handle the quote "deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function deleted(Quote $quote)
    {
        //
    }

    /**
     * Handle the quote "restored" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function restored(Quote $quote)
    {
        if (! app()->runningInConsole()) {
            $quote->user_deleted_id = auth()->user()->id ?? null;
            $quote->saveQuietly();
        }
    }

    /**
     * Handle the quote "force deleted" event.
     *
     * @param  \VentureDrake\LaravelCrm\Models\Quote  $quote
     * @return void
     */
    public function forceDeleted(Quote $quote)
    {
        //
    }
}
