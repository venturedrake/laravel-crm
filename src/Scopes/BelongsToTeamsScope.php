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
     * The signed-URL document routes, where the signature is the authorisation
     * and the document carries its own team.
     */
    protected const SIGNED_DOCUMENT_ROUTES = [
        'laravel-crm.portal.invoices.*',
        'laravel-crm.portal.quotes.*',
        'laravel-crm.portal.purchase-orders.*',
    ];

    /**
     * Whether this scope constrains queries on the current request at all.
     *
     * Nova serves its own tenancy, so team scoping stands down there and every
     * query returns all teams' rows. Anything caching the result of a scoped
     * query has to partition itself on the same answer — see
     * SettingService::cacheKey() — so the condition lives here rather than
     * being restated at each call site where the two could drift apart.
     *
     * The signed document routes stand down for a different reason: those
     * links are authorised by their signature, and the recipient is a customer
     * with no account at all. Leaving the scope on meant a staff member who
     * happened to be signed in to team A got a 404 on team B's valid link,
     * because the route-model binding filtered the record out. Who is logged in
     * should not decide whether a signed link opens. Narrowed to these route
     * names rather than the whole `/p` prefix — the public feature board drops
     * global scopes and filters on PortalTeam itself, so it neither needs nor
     * wants this. Safe only because SettingService::forTeam() pins the settings
     * map to the document's team first; without that ordering, standing the
     * scope down here would widen the very leak it is paired with.
     */
    public static function appliesToRequest(): bool
    {
        if (self::signedDocumentRequest()) {
            return false;
        }

        return ! config('nova.path')
            || ! Str::startsWith(request()->getRequestUri(), config('nova.path'));
    }

    /**
     * Is this request one of the signed document routes?
     *
     * Separate from appliesToRequest() because SettingService has to tell the
     * two stand-down reasons apart when it partitions its cache.
     */
    public static function signedDocumentRequest(): bool
    {
        return request()->routeIs(...self::SIGNED_DOCUMENT_ROUTES);
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
