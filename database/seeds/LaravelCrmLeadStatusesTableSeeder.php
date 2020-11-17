<?php

use Illuminate\Database\Seeder;

class LaravelCrmLeadStatusesTableSeeder extends Seeder
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
                    'name' => 'Lead In',
                ]
            ],
            [
                [
                    'id' => 2
                ],
                [
                    'name' => 'Contacted',
                ]
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\LeadStatus::firstOrCreate($item[0], $item[1]);
        }
    }
}
