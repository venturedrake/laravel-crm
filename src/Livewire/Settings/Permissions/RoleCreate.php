<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use VentureDrake\LaravelCrm\Livewire\Settings\Permissions\Traits\HasRoleCommon;
use VentureDrake\LaravelCrm\Models\Role;

class RoleCreate extends Component
{
    use HasRoleCommon;

    public function save()
    {
        $this->validate();

        $permissionsArray = [];

        foreach ($this->permissions as $permission) {
            $permissionsArray[] = Permission::where('id', $permission)->first()->name;
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if (config('laravel-crm.teams')) {
            $role = Role::create([
                'name' => $this->name,
                'description' => $this->description,
                'crm_role' => 1,
                'team_id' => auth()->user()->currentTeam->id,
            ]);
        } else {
            $role = Role::create([
                'name' => $this->name,
                'description' => $this->description,
                'crm_role' => 1,
            ]);
        }

        $role->syncPermissions($permissionsArray);

        $this->success(
            ucfirst(trans('laravel-crm::lang.role_created')),
            redirectTo: route('laravel-crm.roles.index')
        );
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.permissions.role-create');
    }
}
