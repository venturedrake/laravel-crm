<?php

use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class LaravelCrmLabelsTableSeeder extends Seeder
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
                    'id' => 1
                ],
                [
                    'name' => 'Hot',
                    'hex' => 'dc3545',
                    'external_id' => Uuid::uuid4()->toString(),
                ]
            ],
            [
                [
                    'id' => 2
                ],
                [
                    'name' => 'Cold',
                    'hex' => '007bff',
                    'external_id' => Uuid::uuid4()->toString(),
                ]
            ],
            [
                [
                    'id' => 3
                ],
                [
                    'name' => 'Warm',
                    'hex' => 'ffc107',
                    'external_id' => Uuid::uuid4()->toString(),
                ]
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\Label::firstOrCreate($item[0], $item[1]);
        }
    }
}
