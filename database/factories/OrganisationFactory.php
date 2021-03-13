<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use VentureDrake\LaravelCrm\Models\Organisation;
use Faker\Generator as Faker;

$factory->define(Organisation::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'name' => $faker->company,
        'user_owner_id' => 1,
    ];
});
