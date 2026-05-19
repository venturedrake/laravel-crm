<?php

namespace VentureDrake\LaravelCrm\Http\Controllers\Api\V2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class ApiController extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    protected int $defaultPerPage = 25;

    protected int $maxPerPage = 100;

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', $this->defaultPerPage);

        if ($perPage < 1) {
            $perPage = $this->defaultPerPage;
        }

        return min($perPage, $this->maxPerPage);
    }

    /**
     * Apply a `?sort=field` / `?sort=-field` query parameter to a builder.
     *
     * @param  array<int, string>  $allowed
     */
    protected function applySort(Builder $query, Request $request, array $allowed, string $default = '-created_at'): Builder
    {
        $sort = (string) $request->query('sort', $default);

        if ($sort === '') {
            $sort = $default;
        }

        $direction = 'asc';
        $column = $sort;

        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $column = substr($sort, 1);
        }

        if (! in_array($column, $allowed, true)) {
            return $query;
        }

        return $query->orderBy($column, $direction);
    }
}
