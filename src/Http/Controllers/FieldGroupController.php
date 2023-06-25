<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use VentureDrake\LaravelCrm\Http\Requests\StoreFieldGroupRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateFieldGroupRequest;
use VentureDrake\LaravelCrm\Models\FieldGroup;

class FieldGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        if (FieldGroup::all()->count() < 30) {
            $fieldGroups = FieldGroup::latest()->get();
        } else {
            $fieldGroups = FieldGroup::latest()->paginate(30);
        }

        return view('laravel-crm::field-groups.index', [
            'fieldGroups' => $fieldGroups,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::field-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFieldGroupRequest $request)
    {
        FieldGroup::create([
            'name' => $request->name,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.field_group_stored')))->success()->important();

        return redirect(route('laravel-crm.field-groups.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(FieldGroup $fieldGroup)
    {
        return view('laravel-crm::field-groups.show', [
            'fieldGroup' => $fieldGroup,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(FieldGroup $fieldGroup)
    {
        return view('laravel-crm::field-groups.edit', [
            'fieldGroup' => $fieldGroup,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFieldGroupRequest $request, FieldGroup $fieldGroup)
    {
        $fieldGroup->update([
            'name' => $request->name,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.field_group_updated')))->success()->important();

        return redirect(route('laravel-crm.field-groups.show', $fieldGroup));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(FieldGroup $fieldGroup)
    {
        $fieldGroup->delete();

        flash(ucfirst(trans('laravel-crm::lang.field_group_deleted')))->success()->important();

        return redirect(route('laravel-crm.field-groups.index'));
    }
}
