<?php

namespace VentureDrake\LaravelCrm\Observers;

use Dcblogdev\Xero\Models\XeroToken;

class XeroTokenObserver
{
    /**
     * Handle the xeroToken "creating" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function creating(XeroToken $xeroToken)
    {
        if (! app()->runningInConsole() && config('laravel-crm.teams') && auth()->user()->currentTeam) {
            $xeroToken->team_id = auth()->user()->currentTeam->id;
        }
    }

    /**
     * Handle the xeroToken "created" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function created(XeroToken $xeroToken)
    {
        //
    }

    /**
     * Handle the xeroToken "updating" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function updating(XeroToken $xeroToken)
    {
        //
    }

    /**
     * Handle the xeroToken "updated" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function updated(XeroToken $xeroToken)
    {
        //
    }

    /**
     * Handle the xeroToken "deleting" event.
     *
     * @param  \VentureDrake\LaravelCrm\XeroToken  $xeroToken
     * @return void
     */
    public function deleting(XeroToken $xeroToken)
    {
        //
    }

    /**
     * Handle the xeroToken "deleted" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function deleted(XeroToken $xeroToken)
    {
        //
    }

    /**
     * Handle the xeroToken "restored" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function restored(XeroToken $xeroToken)
    {
        //
    }

    /**
     * Handle the xeroToken "force deleted" event.
     *
     * @param  \Dcblogdev\Xero\Models\XeroToken  $xeroToken
     * @return void
     */
    public function forceDeleted(XeroToken $xeroToken)
    {
        //
    }
}
