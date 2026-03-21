<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Response;
use VentureDrake\LaravelCrm\Models\Task;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('laravel-crm::tasks.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('laravel-crm::tasks.create');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Task $task)
    {
        return view('laravel-crm::tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Task $task)
    {
        return view('laravel-crm::tasks.edit', compact('task'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Task $task)
    {
        $task->delete();

        flash(ucfirst(trans('laravel-crm::lang.task_deleted')))->success()->important();

        return redirect(route('laravel-crm.tasks.index'));
    }

    public function complete(Task $task)
    {
        $task->update([
            'completed_at' => Carbon::now(),
        ]);

        flash(ucfirst(trans('laravel-crm::lang.task_completed')))->success()->important();

        return redirect(route('laravel-crm.tasks.index'));
    }
}
