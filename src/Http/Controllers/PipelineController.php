<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
