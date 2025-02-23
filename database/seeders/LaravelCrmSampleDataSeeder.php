<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Label;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Product;
use VentureDrake\LaravelCrm\Models\ProductCategory;

class LaravelCrmSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $this->command->line('Sedding organisations...');
        factory(Organization::class, 100)->create();

        $this->command->line('Seeding people...');
        factory(Person::class, 200)->create();

        $this->command->line('Seeding leads...');
        factory(Lead::class, 100)->create();

        foreach (Lead::all() as $lead) {
            $lead->labels()->syncWithoutDetaching(Label::inRandomOrder()->take(rand(0, 3))->pluck('id')->toArray());
        }

        $this->command->line('Seeding deals...');
        factory(Deal::class, 50)->create();

        $this->command->line('Seeding products...');
        factory(Product::class, 10)->create();

        $this->command->line('Seeding product categories...');
        factory(ProductCategory::class, 5)->create();
    }
}
