<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Permissions;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use VentureDrake\LaravelCrm\Livewire\Settings\Permissions\Traits\HasRoleCommon;
use VentureDrake\LaravelCrm\Models\Role;

class RoleEdit extends Component
{
    use HasRoleCommon;

    public Role $role;

    public function mount()
    {
        $this->name = $this->role->name;
        $this->description = $this->role->description;
        $this->permissions = $this->role->permissions->pluck('id')->toArray();
    }

    public function save()
    {
        $this->validate();

        if (! in_array($this->role->name, ['Owner', 'Admin']) && $this->role->users->count() < 1) {
            $permissionsArray = [];
            foreach ($this->permissions as $permission) {
                $permissionsArray[] = Permission::where('id', $permission)->first()->name;
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $this->role->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->role->syncPermissions($permissionsArray);

            $this->success(
                ucfirst(trans('laravel-crm::lang.role_updated')),
                redirectTo: route('laravel-crm.roles.index')
            );
        } else {
            $this->error(
                ucfirst(trans('laravel-crm::lang.role_cant_be_updated'))
            );
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.permissions.role-edit');
    }
}
