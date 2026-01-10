<?php

namespace VentureDrake\LaravelCrm\Livewire\Settings\Permissions;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Traits\ClearsProperties;
use VentureDrake\LaravelCrm\Traits\ResetsPaginationWhenPropsChanges;

class RoleIndex extends Component
{
    use ClearsProperties, ResetsPaginationWhenPropsChanges, Toast, WithPagination;

    public $layout = 'index';

    #[Url]
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public $dateFormat;

    public function mount()
    {
        $this->dateFormat = app('laravel-crm.settings')->get('date_format', config('laravel-crm.date_format'));
    }

    public function headers()
    {
        return [
            ['key' => 'name', 'label' => ucfirst(__('laravel-crm::lang.name'))],
            ['key' => 'users', 'label' => ucfirst(__('laravel-crm::lang.users')), 'format' => fn ($row, $field) => count($field)],
            ['key' => 'created_at', 'label' => ucfirst(__('laravel-crm::lang.created')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
            ['key' => 'updated_at', 'label' => ucfirst(__('laravel-crm::lang.updated')), 'format' => fn ($row, $field) => $field->format($this->dateFormat)],
        ];
    }

    public function roles(): LengthAwarePaginator
    {
        return Role::crm()
            ->when(config('laravel-crm.teams'), function ($query) {
                return $query->where('team_id', auth()->user()->currentTeam->id);
            })->orderBy(...array_values($this->sortBy))
            ->paginate(25);
    }

    public function delete($id)
    {
        if ($role = Role::find($id)) {
            if (! in_array($role->name, ['Owner', 'Admin']) && $role->users->count() < 1) {
                foreach (Permission::all() as $permission) {
                    $permission->removeRole($role);
                }

                $role->delete();

                $this->success(ucfirst(trans('laravel-crm::lang.role_deleted')));
            } else {
                $this->error(ucfirst(trans('laravel-crm::lang.role_cant_be_deleted')));
            }
        }
    }

    public function render()
    {
        return view('laravel-crm::livewire.settings.permissions.role-index', [
            'headers' => $this->headers(),
            'roles' => $this->roles(),
        ]);
    }
}
