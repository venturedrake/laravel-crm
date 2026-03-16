<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\HttpCache\Store;
use VentureDrake\LaravelCrm\Http\Requests\StoreLabelRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateLabelRequest;
use VentureDrake\LaravelCrm\Models\Label;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('laravel-crm::settings.labels.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('laravel-crm::settings.labels.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreLabelRequest $request)
    {
        Label::create([
            'external_id' => Uuid::uuid4()->toString(),
            'name' => $request->name,
            'description' => $request->description,
            'hex' => $request->hex ?? '6c757d',
        ]);

        flash(ucfirst(trans('laravel-crm::lang.label_stored')))->success()->important();

        return redirect(route('laravel-crm.labels.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(Label $label)
    {
        return view('laravel-crm::settings.labels.show', [
            'label' => $label,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Label $label)
    {
        return view('laravel-crm::settings.labels.edit', [
            'label' => $label,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateLabelRequest $request, Label $label)
    {
        $label->update([
            'name' => $request->name,
            'description' => $request->description,
            'hex' => $request->hex ?? '6c757d',
        ]);

        flash(ucfirst(trans('laravel-crm::lang.label_updated')))->success()->important();

        return redirect(route('laravel-crm.labels.show', $label));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Label $label)
    {
        $label->delete();

        flash(ucfirst(trans('laravel-crm::lang.label_deleted')))->success()->important();

        return redirect(route('laravel-crm.labels.index'));
    }
}
