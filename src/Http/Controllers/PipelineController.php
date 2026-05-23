<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StorePipelineRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePipelineRequest;
use VentureDrake\LaravelCrm\Models\Pipeline;

class PipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (Pipeline::all()->count() < 30) {
            $pipelines = Pipeline::latest()->get();
        } else {
            $pipelines = Pipeline::latest()->paginate(30);
        }

        return view('laravel-crm::settings.pipelines.index', [
            'pipelines' => $pipelines,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('laravel-crm::pipelines.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StorePipelineRequest $request)
    {
        Pipeline::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
        ]);

        flash()->success(ucfirst(trans('laravel-crm::lang.pipeline_stored')));

        return redirect(route('laravel-crm.pipelines.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(Pipeline $pipeline)
    {
        return view('laravel-crm::settings.pipelines.show', [
            'pipeline' => $pipeline,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Pipeline $pipeline)
    {
        return view('laravel-crm::settings.pipelines.edit', [
            'pipeline' => $pipeline,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdatePipelineRequest $request, Pipeline $pipeline)
    {
        $pipeline->update([
            'name' => $request->name,
        ]);

        flash()->success(ucfirst(trans('laravel-crm::lang.pipeline_updated')));

        return redirect(route('laravel-crm.pipelines.show', $pipeline));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Pipeline $pipeline)
    {
        $pipeline->delete();

        flash()->success(ucfirst(trans('laravel-crm::lang.pipeline_deleted')));

        return redirect(route('laravel-crm.pipelines.index'));
    }
}
