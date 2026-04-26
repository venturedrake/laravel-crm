<?php

namespace VentureDrake\LaravelCrm\Tests\Feature\Models;

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Tests\TestCase;

class TaskTest extends TestCase
{
    public function test_task_uses_prefixed_table(): void
    {
        $this->assertSame('crm_tasks', (new Task)->getTable());
    }

    public function test_task_can_be_assigned_polymorphically_to_a_lead(): void
    {
        $lead = Lead::create(['title' => 'L']);

        $task = Task::create([
            'name' => 'Follow up',
            'taskable_type' => Lead::class,
            'taskable_id' => $lead->id,
        ]);

        $this->assertTrue(Str::isUuid($task->external_id));
        $this->assertSame('Follow up', $task->name);
    }
}
