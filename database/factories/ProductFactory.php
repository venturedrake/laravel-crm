<?php

namespace VentureDrake\LaravelCrm\Database\Factories;

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;

$factory->define(Product::class, function (Faker $faker) {
    return [
        'external_id' => $faker->uuid,
        'name' => $faker->word,
        'product_category_id' => ProductCategory::all()->random(1)->first()->id,
        'user_owner_id' => 1,
    ];
});
