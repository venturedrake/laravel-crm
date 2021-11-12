<?php

namespace VentureDrake\LaravelCrm\Observers;

use App\Team;
use Carbon\Carbon;
use DB;
use Ramsey\Uuid\Uuid;
use VentureDrake\LaravelCrm\Models\Role;

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
        
        // Get the roles
        foreach (DB::table('roles')
                     ->where('crm_role', 1)
                     ->whereNull('team_id')
                     ->get() as $role) {
            DB::table('roles')->updateOrInsert([
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'description' => $role->description,
                'crm_role' => $role->crm_role,
                'team_id' => $team->id,
            ], [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            if ($newRole = DB::table('roles')->where([
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'description' => $role->description,
                'crm_role' => $role->crm_role,
                'team_id' => $team->id,
            ])->first()) {
                foreach (DB::table('permissions')
                             ->leftJoin('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                             ->where('role_has_permissions.role_id', $role->id)
                             ->get() as $permission) {
                    DB::table('role_has_permissions')->updateOrInsert([
                        'permission_id' => $permission->id,
                        'role_id' => $newRole->id,
                    ]);
                }
            }
        }

        if ($role = Role::where([
            'name' => 'Owner',
            'team_id' => auth()->user()->currentTeam->id,
            'crm_role' => 1,
        ])->first()) {
            auth()->user()->assignRole($role);
        }

        foreach (DB::table('labels')
                     ->whereNull('team_id')
                     ->get() as $label) {
            DB::table('labels')->insert([
                'external_id' => Uuid::uuid4()->toString(),
                'name' => $label->name,
                'hex' => $label->hex,
                'description' => $label->description,
                'team_id' => $team->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        foreach (DB::table('organisation_types')
                     ->whereNull('team_id')
                     ->get() as $organisationType) {
            DB::table('organisation_types')->insert([
                'name' => $organisationType->name,
                'description' => $organisationType->description,
                'team_id' => $team->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        foreach (DB::table('address_types')
                     ->whereNull('team_id')
                     ->get() as $addressType) {
            DB::table('address_types')->insert([
                'name' => $addressType->name,
                'description' => $addressType->description,
                'team_id' => $team->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
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
