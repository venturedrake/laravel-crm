<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use VentureDrake\LaravelCrm\Models\Organization;

$factory->define(Organization::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'name' => $faker->company,
        'user_owner_id' => 1,
    ];
});
