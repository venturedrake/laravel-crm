<?php

namespace VentureDrake\LaravelCrm\Observers;

use App\Team;
use DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeamObserver
{
    /**
     * Handle the team "creating" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function creating(Team $team)
    {
        //
    }
    
    /**
     * Handle the team "created" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function created(Team $team)
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (DB::table('permissions')
                    ->whereNull('team_id')
                    ->get() as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'description' => $permission->description,
                'crm_permission' => $permission->crm_permission,
                'team_id' => $team->id,
            ]);
        }

        $role = Role::create(['name' => 'Owner', 'crm_role' => 1])
            ->givePermissionTo(Permission::all());

        $role = Role::create(['name' => 'Admin', 'crm_role' => 1])
            ->givePermissionTo(Permission::all());

        $role = Role::create(['name' => 'Manager', 'crm_role' => 1])
            ->givePermissionTo([
                'create crm leads',
                'view crm leads',
                'edit crm leads',
                'delete crm leads',
                'create crm deals',
                'view crm deals',
                'edit crm deals',
                'delete crm deals',
                'create crm people',
                'view crm people',
                'edit crm people',
                'delete crm people',
                'create crm organisations',
                'view crm organisations',
                'edit crm organisations',
                'delete crm organisations',
            ]);

        $role = Role::create(['name' => 'Employee', 'crm_role' => 1])
            ->givePermissionTo([
                'create crm leads',
                'view crm leads',
                'edit crm leads',
                'delete crm leads',
                'create crm deals',
                'view crm deals',
                'edit crm deals',
                'delete crm deals',
                'create crm people',
                'view crm people',
                'edit crm people',
                'delete crm people',
                'create crm organisations',
                'view crm organisations',
                'edit crm organisations',
                'delete crm organisations',
            ]);
    }

    /**
     * Handle the team "updating" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function updating(Team $team)
    {
        //
    }

    /**
     * Handle the team "updated" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function updated(Team $team)
    {
        //
    }

    /**
     * Handle the team "deleting" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function deleting(Team $team)
    {
        //
    }

    /**
     * Handle the team "deleted" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function deleted(Team $team)
    {
        //
    }

    /**
     * Handle the team "restored" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function restored(Team $team)
    {
        //
    }

    /**
     * Handle the team "force deleted" event.
     *
     * @param  \App\Team  $team
     * @return void
     */
    public function forceDeleted(Team $team)
    {
        //
    }
}
