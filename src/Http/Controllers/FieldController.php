<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use VentureDrake\LaravelCrm\Http\Requests\StoreFieldRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateFieldRequest;
use VentureDrake\LaravelCrm\Models\Field;

class FieldController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        if (Field::all()->count() < 30) {
            $fields = Field::latest()->get();
        } else {
            $fields = Field::latest()->paginate(30);
        }
        
        return view('laravel-crm::fields.index', [
            'fields' => $fields,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::fields.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFieldRequest $request)
    {
        Field::create([
            'type' => $request->type,
            'name' => $request->name,
            'field_group_id' => $request->field_group_id,
            'required' => (($request->required == 'on') ? 1 : 0),
            'default' => $request->default,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.field_stored')))->success()->important();

        return redirect(route('laravel-crm.fields.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Field $field)
    {
        return view('laravel-crm::fields.show', [
            'field' => $field,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Field $field)
    {
        return view('laravel-crm::fields.edit', [
            'field' => $field,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFieldRequest $request, Field $field)
    {
        $field->update([
            'type' => $request->type,
            'name' => $request->name,
            'field_group_id' => $request->field_group_id,
            'required' => (($request->required == 'on') ? 1 : 0),
            'default' => $request->default,
        ]);

        flash(ucfirst(trans('laravel-crm::lang.field_updated')))->success()->important();

        return redirect(route('laravel-crm.fields.show', $field));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Field $field)
    {
        $field->delete();

        flash(ucfirst(trans('laravel-crm::lang.field_deleted')))->success()->important();

        return redirect(route('laravel-crm.fields.index'));
    }
}
