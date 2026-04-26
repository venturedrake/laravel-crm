<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Pipeline;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrm\Tests\TestCase;

class PipelineTest extends TestCase
{
    public function test_pipeline_uses_prefixed_table(): void
    {
        $this->assertSame('crm_pipelines', (new Pipeline)->getTable());
    }

    public function test_pipeline_can_be_created_with_stages(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Sales',
            'model' => Lead::class,
        ]);

        $this->assertTrue(Str::isUuid($pipeline->external_id));

        $stage = PipelineStage::create([
            'name' => 'Qualified',
            'pipeline_id' => $pipeline->id,
        ]);

        $this->assertSame($pipeline->id, $stage->pipeline_id);
    }
}
