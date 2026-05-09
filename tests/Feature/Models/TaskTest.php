<?php

use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Task;

test('task uses prefixed table', function () {
    expect((new Task)->getTable())->toBe('crm_tasks');
});

test('task can be assigned polymorphically to a lead', function () {
    $lead = Lead::create(['title' => 'L']);

    $task = Task::create(['name' => 'Follow up', 'taskable_type' => Lead::class, 'taskable_id' => $lead->id]);

    expect(Str::isUuid($task->external_id))->toBeTrue();
    expect($task->name)->toBe('Follow up');
});
