<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Http\Requests\StorePipelineStageRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdatePipelineStageRequest;
use VentureDrake\LaravelCrm\Models\PipelineStage;

class PipelineStageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (PipelineStage::all()->count() < 30) {
            $pipelineStages = PipelineStage::latest()->get();
        } else {
            $pipelineStages = PipelineStage::latest()->paginate(30);
        }

        return view('laravel-crm::pipeline-stages.index', [
            'pipelineStages' => $pipelineStages,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::pipeline-stages.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePipelineStageRequest $request)
    {
        PipelineStage::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'description' => $request->description,
            'pipeline_id' => $request->pipeline_id,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.pipeline_stage_stored')))->success()->important();

        return redirect(route('laravel-crm.pipeline-stages.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(PipelineStage $pipelineStage)
    {
        return view('laravel-crm::pipeline-stages.show', [
            'pipelineStage' => $pipelineStage,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(PipelineStage $pipelineStage)
    {
        return view('laravel-crm::pipeline-stages.edit', [
            'pipelineStage' => $pipelineStage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePipelineStageRequest $request, PipelineStage $pipelineStage)
    {
        $pipelineStage->update([
            'name' => $request->name,
            'description' => $request->description,
            'pipeline_id' => $request->pipeline_id,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.pipeline_stage_updated')))->success()->important();

        return redirect(route('laravel-crm.pipeline-stages.show', $pipelineStage));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(PipelineStage $pipelineStage)
    {
        $pipelineStage->delete();

        flash(ucfirst(trans('laravel-crm::lang.pipeline_stage_deleted')))->success()->important();

        return redirect(route('laravel-crm.pipeline-stages.index'));
    }
}
