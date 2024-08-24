<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use VentureDrake\LaravelCrm\Models\Deal;
use VentureDrake\LaravelCrm\Models\Delivery;
use VentureDrake\LaravelCrm\Models\Invoice;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Order;
use VentureDrake\LaravelCrm\Models\PurchaseOrder;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Models\Setting;

class LaravelCrmPipelineTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Pipelines stage probabilities
        if(! \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_seeded_pipeline_probabilities')->first()) {
            $items = [
                [
                    [
                        'id' => 1,
                    ],
                    [
                        'name' => 'New',
                        'percent' => 1,
                    ],
                ],
                [
                    [
                        'id' => 2,
                    ],
                    [
                        'name' => '10%',
                        'percent' => 10,
                    ],
                ],
                [
                    [
                        'id' => 3,
                    ],
                    [
                        'name' => '20%',
                        'percent' => 20,
                    ],
                ],
                [
                    [
                        'id' => 4,
                    ],
                    [
                        'name' => '30%',
                        'percent' => 30,
                    ],
                ],
                [
                    [
                        'id' => 5,
                    ],
                    [
                        'name' => '40%',
                        'percent' => 40,
                    ],
                ],
                [
                    [
                        'id' => 6,
                    ],
                    [
                        'name' => '50%',
                        'percent' => 50,
                    ],
                ],
                [
                    [
                        'id' => 7,
                    ],
                    [
                        'name' => '60%',
                        'percent' => 60,
                    ],
                ],
                [
                    [
                        'id' => 8,
                    ],
                    [
                        'name' => '70%',
                        'percent' => 70,
                    ],
                ],
                [
                    [
                        'id' => 9,
                    ],
                    [
                        'name' => '80%',
                        'percent' => 80,
                    ],
                ],
                [
                    [
                        'id' => 10,
                    ],
                    [
                        'name' => '90%',
                        'percent' => 90,
                    ],
                ],
                [
                    [
                        'id' => 11,
                    ],
                    [
                        'name' => 'Won',
                        'percent' => 100,
                    ],
                ],
                [
                    [
                        'id' => 12,
                    ],
                    [
                        'name' => 'Lost',
                        'percent' => 0,
                    ],
                ],
            ];

            foreach ($items as $item) {
                \VentureDrake\LaravelCrm\Models\PipelineStageProbability::firstOrCreate($item[0], $item[1]);
            }

            Setting::updateOrCreate([
                'global' => 1,
                'name' => 'db_seeded_pipeline_probabilities',
            ], [
                'value' => 1,
            ]);
        }

        // Pipelines
        if(! \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_seeded_pipelines')->first()) {
            $items = [
                [
                    [
                        'id' => 1,
                    ],
                    [
                        'name' => 'Lead Pipeline',
                        'model' =>  get_class(new Lead()),
                    ],
                ],
                [
                    [
                        'id' => 2,
                    ],
                    [
                        'name' => 'Deal Pipeline',
                        'model' =>  get_class(new Deal()),
                    ],
                ],
                [
                    [
                        'id' => 3,
                    ],
                    [
                        'name' => 'Quote Pipeline',
                        'model' =>  get_class(new Quote()),
                    ],
                ],
                [
                    [
                        'id' => 4,
                    ],
                    [
                        'name' => 'Order Pipeline',
                        'model' =>  get_class(new Order()),
                    ],
                ],
                [
                    [
                        'id' => 5,
                    ],
                    [
                        'name' => 'Invoice Pipeline',
                        'model' =>  get_class(new Invoice()),
                    ],
                ],
                [
                    [
                        'id' => 6,
                    ],
                    [
                        'name' => 'Delivery Pipeline',
                        'model' =>  get_class(new Delivery()),
                    ],
                ],
                [
                    [
                        'id' => 7,
                    ],
                    [
                        'name' => 'Purchase Order Pipeline',
                        'model' =>  get_class(new PurchaseOrder()),
                    ],
                ],
            ];

            foreach ($items as $item) {
                \VentureDrake\LaravelCrm\Models\Pipeline::firstOrCreate($item[0], $item[1]);
            }

            Setting::updateOrCreate([
                'global' => 1,
                'name' => 'db_seeded_pipelines',
            ], [
                'value' => 1,
            ]);
        }

        // Pipelines stages
        if(! \VentureDrake\LaravelCrm\Models\Setting::where('name', 'db_seeded_pipelines_stages')->first()) {
            $items = [
                // Leads
                [
                    [
                        'id' => 1,
                    ],
                    [
                        'name' => 'New',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 2,
                    ],
                    [
                        'name' => 'Appointment Scheduled',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 3,
                    ],
                ],
                [
                    [
                        'id' => 3,
                    ],
                    [
                        'name' => 'Qualified To Buy',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 5,
                    ],
                ],
                [
                    [
                        'id' => 4,
                    ],
                    [
                        'name' => 'Presentation Scheduled',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 7,
                    ],
                ],
                [
                    [
                        'id' => 5,
                    ],
                    [
                        'name' => 'Decision Maker Bought-In',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 6,
                    ],
                    [
                        'name' => 'Contract Sent',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 10,
                    ],
                ],
                [
                    [
                        'id' => 7,
                    ],
                    [
                        'name' => 'Closed Won',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                [
                    [
                        'id' => 8,
                    ],
                    [
                        'name' => 'Closed Lost',
                        'pipeline_id' => 1,
                        'pipeline_stage_probability_id' => 12,
                    ],
                ],
                // Deals
                [
                    [
                        'id' => 9,
                    ],
                    [
                        'name' => 'Draft',
                        'pipeline_id' => 2,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 10,
                    ],
                    [
                        'name' => 'Pending',
                        'pipeline_id' => 2,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 11,
                    ],
                    [
                        'name' => 'Closed Won',
                        'pipeline_id' => 2,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                [
                    [
                        'id' => 12,
                    ],
                    [
                        'name' => 'Closed Lost',
                        'pipeline_id' => 2,
                        'pipeline_stage_probability_id' => 12,
                    ],
                ],
                // Quotes
                [
                    [
                        'id' => 13,
                    ],
                    [
                        'name' => 'Draft',
                        'pipeline_id' => 3,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 14,
                    ],
                    [
                        'name' => 'Sent',
                        'pipeline_id' => 3,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 15,
                    ],
                    [
                        'name' => 'Accepted',
                        'pipeline_id' => 3,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                [
                    [
                        'id' => 16,
                    ],
                    [
                        'name' => 'Rejected',
                        'pipeline_id' => 3,
                        'pipeline_stage_probability_id' => 12,
                    ],
                ],
                [
                    [
                        'id' => 17,
                    ],
                    [
                        'name' => 'Ordered',
                        'pipeline_id' => 3,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                // Orders
                [
                    [
                        'id' => 18,
                    ],
                    [
                        'name' => 'Draft',
                        'pipeline_id' => 4,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 19,
                    ],
                    [
                        'name' => 'Open',
                        'pipeline_id' => 4,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 20,
                    ],
                    [
                        'name' => 'Invoiced',
                        'pipeline_id' => 4,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                [
                    [
                        'id' => 21,
                    ],
                    [
                        'name' => 'Delivered',
                        'pipeline_id' => 4,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                [
                    [
                        'id' => 22,
                    ],
                    [
                        'name' => 'Completed',
                        'pipeline_id' => 4,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                // Invoices
                [
                    [
                        'id' => 23,
                    ],
                    [
                        'name' => 'Draft',
                        'pipeline_id' => 5,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 24,
                    ],
                    [
                        'name' => 'Awaiting Approval',
                        'pipeline_id' => 5,
                        'pipeline_stage_probability_id' => 5,
                    ],
                ],
                [
                    [
                        'id' => 25,
                    ],
                    [
                        'name' => 'Awaiting Payment',
                        'pipeline_id' => 5,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 26,
                    ],
                    [
                        'name' => 'Paid',
                        'pipeline_id' => 5,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                // Deliveries
                [
                    [
                        'id' => 27,
                    ],
                    [
                        'name' => 'Draft',
                        'pipeline_id' => 6,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 28,
                    ],
                    [
                        'name' => 'Packed',
                        'pipeline_id' => 6,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 29,
                    ],
                    [
                        'name' => 'Sent',
                        'pipeline_id' => 6,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                [
                    [
                        'id' => 30,
                    ],
                    [
                        'name' => 'Delivered',
                        'pipeline_id' => 6,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
                // Purchase Orders
                [
                    [
                        'id' => 31,
                    ],
                    [
                        'name' => 'Draft',
                        'pipeline_id' => 7,
                        'pipeline_stage_probability_id' => 1,
                    ],
                ],
                [
                    [
                        'id' => 32,
                    ],
                    [
                        'name' => 'Awaiting Approval',
                        'pipeline_id' => 7,
                        'pipeline_stage_probability_id' => 5,
                    ],
                ],
                [
                    [
                        'id' => 33,
                    ],
                    [
                        'name' => 'Approved',
                        'pipeline_id' => 7,
                        'pipeline_stage_probability_id' => 9,
                    ],
                ],
                [
                    [
                        'id' => 34,
                    ],
                    [
                        'name' => 'Paid',
                        'pipeline_id' => 7,
                        'pipeline_stage_probability_id' => 11,
                    ],
                ],
            ];

            foreach ($items as $item) {
                \VentureDrake\LaravelCrm\Models\PipelineStage::firstOrCreate($item[0], $item[1]);
            }

            Setting::updateOrCreate([
                'global' => 1,
                'name' => 'db_seeded_pipelines_stages',
            ], [
                'value' => 1,
            ]);
        }
    }
}
