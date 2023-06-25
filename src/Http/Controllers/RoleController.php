<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use Spatie\Permission\Models\Permission;
use VentureDrake\LaravelCrm\Http\Requests\StoreRoleRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateRoleRequest;
use VentureDrake\LaravelCrm\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::crm()
            ->when(config('laravel-crm.teams'), function ($query) {
                return $query->where('team_id', auth()->user()->currentTeam->id);
            })
            ->get();

        return view('laravel-crm::roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('laravel-crm::roles.create', [
            'permissions' => \VentureDrake\LaravelCrm\Models\Permission::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        $permissionsArray = [];
        foreach ($request->permission as $permissionKey => $permissionValue) {
            $permissionsArray[] = Permission::where('id', $permissionKey)->first()->name;
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if (config('laravel-crm.teams')) {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'crm_role' => 1,
                'team_id' => auth()->user()->currentTeam->id,
            ]);
        } else {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'crm_role' => 1,
            ]);
        }

        $role->syncPermissions($permissionsArray);

        flash(ucfirst(trans('laravel-crm::lang.role_stored')))->success()->important();

        return redirect(route('laravel-crm.roles.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return view('laravel-crm::roles.show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        return view('laravel-crm::roles.edit', [
            'role' => $role,
            'permissions' => \VentureDrake\LaravelCrm\Models\Permission::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        if (! in_array($role->name, ['Owner','Admin']) && $role->users->count() < 1) {
            $permissionsArray = [];
            foreach ($request->permission as $permissionKey => $permissionValue) {
                $permissionsArray[] = Permission::where('id', $permissionKey)->first()->name;
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
            $role->syncPermissions($permissionsArray);
            flash(ucfirst(trans('laravel-crm::lang.role_updated')))->success()->important();
        } else {
            flash(ucfirst(trans('laravel-crm::lang.role_cant_be_updated')))->error()->important();
        }

        return redirect(route('laravel-crm.roles.show', $role));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        if (! in_array($role->name, ['Owner','Admin']) && $role->users->count() < 1) {
            foreach (Permission::all() as $permission) {
                $permission->removeRole($role);
            }

            $role->delete();

            flash(ucfirst(trans('laravel-crm::lang.role_deleted')))->success()->important();
        } else {
            flash(ucfirst(trans('laravel-crm::lang.role_cant_be_deleted')))->error()->important();
        }

        return redirect(route('laravel-crm.roles.index'));
    }
}
