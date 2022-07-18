<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Scopes\BelongsToTeamsScope;

/**
 * @mixin Model
 */
trait BelongsToTeams
{
    /**
     * Boot the belongs to teams trait for a model.
     *
     * @return void
     */
    public static function bootBelongsToTeams()
    {
        if(! app()->runningInConsole()){
            static::addGlobalScope(new BelongsToTeamsScope(auth()->user()->currentTeam ?? null));

            static::creating(function (Model $model) {
                if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
                    $model->team_id = auth()->user()->currentTeam->id ?? null;
                }
            });  
        }
    }
}
