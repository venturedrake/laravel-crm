<?php

namespace VentureDrake\LaravelCrm\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait SearchFilters
{
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
                    $query->orWhereIn($field, $params[$field]);
                    if (in_array(0, $params[$field])) {
                        $query->orWhereNull($field);
                    }
                });
            }
        }
      
        return $query;
    }
}
