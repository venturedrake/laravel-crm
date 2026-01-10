<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Permissions;

use Livewire\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;
use VentureDrake\LaravelCrm\Models\Role;

class RoleShow extends Component
{
    use Toast;

    public Role $role;

    public function delete($id)
    {
        if ($role = Role::find($id)) {
            if (! in_array($role->name, ['Owner', 'Admin']) && $role->users->count() < 1) {
                foreach (Permission::all() as $permission) {
                    $permission->removeRole($role);
                }

                $role->delete();

                $this->success(ucfirst(trans('laravel-crm::lang.role_deleted')), redirectTo: route('laravel-crm.roles.index'));
            } else {
                $this->error(ucfirst(trans('laravel-crm::lang.role_cant_be_deleted')));
            }
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.permissions.role-show');
    }
}
