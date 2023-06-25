<?php

namespace VentureDrake\LaravelCrm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class BelongsToTeamsScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['AllTeams'];

    public function apply(Builder $builder, Model $model)
    {
        if (config('laravel-crm.teams') && auth()->hasUser() && auth()->user()->currentTeam) {
            $this->extend($builder);

            if (Schema::hasColumn($model->getTable(), 'global')) {
                $builder->where(function ($query) use ($model) {
                    $query->orWhere($model->getTable().'.team_id', auth()->user()->currentTeam->id)
                        ->orWhere($model->getTable().'.global', 1);
                });
            } else {
                $builder->where($model->getTable().'.team_id', auth()->user()->currentTeam->id);
            }
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    protected function addAllTeams(Builder $builder)
    {
        $builder->macro('allTeams', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
