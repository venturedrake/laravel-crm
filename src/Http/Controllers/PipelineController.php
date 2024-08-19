<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StorePipelineRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePipelineRequest;
use VentureDrake\LaravelCrm\Models\Pipeline;

class PipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Pipeline::all()->count() < 30) {
            $pipelines = Pipeline::latest()->get();
        } else {
            $pipelines = Pipeline::latest()->paginate(30);
        }

        return view('laravel-crm::pipelines.index', [
            'pipelines' => $pipelines,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::pipelines.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePipelineRequest $request)
    {
        Pipeline::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.pipeline_stored')))->success()->important();

        return redirect(route('laravel-crm.pipelines.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Pipeline $pipeline)
    {
        return view('laravel-crm::pipelines.show', [
            'pipeline' => $pipeline,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Pipeline $pipeline)
    {
        return view('laravel-crm::pipelines.edit', [
            'pipeline' => $pipeline,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePipelineRequest $request, Pipeline $pipeline)
    {
        $pipeline->update([
            'name' => $request->name,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.pipeline_updated')))->success()->important();

        return redirect(route('laravel-crm.pipelines.show', $pipeline));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pipeline $pipeline)
    {
        $pipeline->delete();

        flash(ucfirst(trans('laravel-crm::lang.pipeline_deleted')))->success()->important();

        return redirect(route('laravel-crm.pipelines.index'));
    }
}
