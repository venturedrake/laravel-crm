<?php

namespace VentureDrake\LaravelCrm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class BelongsToTeamsScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (config('laravel-crm.teams') && auth()->user()->currentTeam) {
            if (Schema::hasColumn($model->getTable(), 'global')) {
                $builder->where(function ($query) {
                    $query->orWhere('team_id', auth()->user()->currentTeam->id)
                        ->orWhere('global', 1);
                });
            } else {
                $builder->where('team_id', auth()->user()->currentTeam->id);
            }
        }
    }
}
