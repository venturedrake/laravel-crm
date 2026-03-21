<?php

namespace VentureDrake\LaravelCrm\Services;

use VentureDrake\LaravelCrm\Models\Task;
use VentureDrake\LaravelCrm\Repositories\TaskRepository;

class TaskService
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * TaskService constructor.
     */
    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function create($request)
    {
        return Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'due_at' => $request->due_at,
            'user_owner_id' => $request->user_owner_id,
            'user_assigned_id' => $request->user_assigned_id,
        ]);
    }

    public function update($request, Task $task)
    {
        $task->update([
            'name' => $request->name,
            'description' => $request->description,
            'due_at' => $request->due_at,
            'user_owner_id' => $request->user_owner_id,
            'user_assigned_id' => $request->user_assigned_id,
        ]);

        return $task;
    }
}

