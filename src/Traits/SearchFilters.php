<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait SearchFilters
{
    public static function searchValue($request)
    {
        if ($request->search) {
            $request->session()->put(class_basename($request->route()->getController()).'.search', $request->search);

            return $request->search;
        } elseif ($request->session()->has(class_basename($request->route()->getController()).'.search')) {
            return $request->session()->get(class_basename($request->route()->getController()).'.search');
        }
    }

    public static function resetSearchValue($request)
    {
        $request->session()->forget(class_basename($request->route()->getController()).'.search');
    }

    public static function filters($request, $action = 'index')
    {
        if ($request->isMethod('post') && $action != 'search') {
            $request->session()->put(class_basename($request->route()->getController()).'.params', $request->except('_token'));

            return $request->except('_token');
        } elseif ($request->session()->has(class_basename($request->route()->getController()).'.params')) {
            return $request->session()->get(class_basename($request->route()->getController()).'.params');
        }

        return $request->except('_token');
    }

    public static function filterValue($key)
    {
        if ($params = request()->session()->get(class_basename(request()->route()->getController()).'.params')) {
            return $params[$key] ?? null;
        }
    }

    public static function filterActive($key, $options)
    {
        if ($params = request()->session()->get(class_basename(request()->route()->getController()).'.params')) {
            if (isset($params[$key]) && count($params[$key]) < count($options)) {
                return true;
            }
        }
    }

    public static function anyFilterActive($filters = [])
    {
        foreach ($filters as $key => $options) {
            if (SearchFilters::filterActive($key, $options)) {
                return true;
            }
        }
    }

    public function scopeFilter($query, $params)
    {
        foreach ($this->filterable as $field) {
            if (Str::contains($field, '.')) {
                $relation = explode('.', $field);
                $field = Str::singular($relation[0]).'_'.$relation[1];

                if (isset($params[$field]) && is_array($params[$field])) {
                    $query->where(function ($query) use ($params, $field, $relation) {
                        if (in_array(0, $params[$field])) {
                            $query->orDoesntHave($relation[0]);
                        }

                        $query->orWhereHas($relation[0], function ($query) use ($relation, $params, $field) {
                            $query->whereIn($relation[1], $params[$field]);
                        });
                    });
                }
            } elseif (isset($params[$field]) && is_array($params[$field])) {
                $query->where(function ($query) use ($params, $field) {
                    $query->orWhereIn($this->getTable().'.'.$field, $params[$field]);
                    if (in_array(0, $params[$field])) {
                        $query->orWhereNull($this->getTable().'.'.$field);
                    }
                });
            }
        }

        return $query;
    }
}
