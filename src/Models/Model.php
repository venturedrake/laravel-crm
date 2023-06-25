<?php

namespace VentureDrake\LaravelCrm\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use OwenIt\Auditing\Contracts\Auditable;

class Model extends EloquentModel implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
