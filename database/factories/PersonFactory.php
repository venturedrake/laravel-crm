<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use VentureDrake\LaravelCrm\Models\Person;

$factory->define(Person::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'user_owner_id' => 1,
    ];
});
