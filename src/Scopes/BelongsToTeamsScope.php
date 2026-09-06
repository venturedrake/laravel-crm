<?php

namespace VentureDrake\LaravelCrm\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Str;

class BelongsToTeamsScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['AllTeams'];

    /**
     * Whether this scope constrains queries on the current request at all.
     *
     * Nova serves its own tenancy, so team scoping stands down there and every
     * query returns all teams' rows. Anything caching the result of a scoped
     * query has to partition itself on the same answer — see
     * SettingService::cacheKey() — so the condition lives here rather than
     * being restated at each call site where the two could drift apart.
     */
    public static function appliesToRequest(): bool
    {
        return ! config('nova.path')
            || ! Str::startsWith(request()->getRequestUri(), config('nova.path'));
    }

    public function apply(Builder $builder, Model $model)
    {
        if (config('laravel-crm.teams') && auth()->hasUser() && auth()->user()->currentTeam) {
            if (self::appliesToRequest()) {
                $this->extend($builder);

                if (in_array($model->getTable(), config('laravel-crm.model_with_global'))) {
                    $builder->where(function ($query) use ($model) {
                        $query->orWhere($model->getTable().'.team_id', auth()->user()->currentTeam->id)
                            ->orWhere($model->getTable().'.global', 1);
                    });
                } else {
                    $builder->where($model->getTable().'.team_id', auth()->user()->currentTeam->id);
                }
            }
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
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
