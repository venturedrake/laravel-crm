<?php

use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class LaravelCrmTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Hot',
                    'hex' => 'dc3545',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Cold',
                    'hex' => '007bff',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
            [
                [
                    'id' => 3,
                ],
                [
                    'name' => 'Warm',
                    'hex' => 'ffc107',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\Label::firstOrCreate($item[0], $item[1]);
        }

        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Lead In',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Contacted',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\LeadStatus::firstOrCreate($item[0], $item[1]);
        }

        /*        if (config('app.env') == 'local') {
                    factory(\VentureDrake\LaravelCrm\Models\Organisation::class, 100)->create();
                    factory(\VentureDrake\LaravelCrm\Models\Person::class, 200)->create();
                    factory(\VentureDrake\LaravelCrm\Models\Lead::class, 100)->create();
                    factory(\VentureDrake\LaravelCrm\Models\Deal::class, 50)->create();
                }*/
    }
}
