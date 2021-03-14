<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use VentureDrake\LaravelCrm\Models\Lead;

$factory->define(Lead::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'person_name' => $faker->name,
        'organisation_name' => $faker->company,
        'title' => $faker->sentence,
        'amount' => $faker->randomFloat(2, 100, 100000),
        'currency' => 'USD',
        'lead_status_id' => 1,
        'user_assigned_id' => 1,
    ];
});
