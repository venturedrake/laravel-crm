<?php

namespace VentureDrake\LaravelCrm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToTeamsScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
            $builder->where('team_id', auth()->user()->currentTeam->id);
        }
    }
}
