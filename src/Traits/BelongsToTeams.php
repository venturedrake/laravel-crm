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
        static::addGlobalScope(new BelongsToTeamsScope);

        static::creating(function (Model $model) {
            $model->team_id = auth()->user()->currentTeam->id ?? null;
        });
    }
}
