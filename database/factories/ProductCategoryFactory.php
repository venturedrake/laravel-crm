<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use VentureDrake\LaravelCrm\Models\ProductCategory;

$factory->define(ProductCategory::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'name' => $faker->word,
    ];
});
