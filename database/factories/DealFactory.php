<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Organisation;
use Faker\Generator as Faker;

$factory->define(Deal::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'person_id' => Person::all()->random(1)->first()->id,
        'organisation_id' => Organisation::all()->random(1)->first()->id,
        'title' => $faker->sentence,
        'amount' => $faker->randomFloat(2,100,100000),
        'currency' => 'USD',
        'user_owner_id' => 1,
        'user_assigned_id' => 1
    ];
});
