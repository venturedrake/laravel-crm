<?php

namespace VentureDrake\LaravelCrm\Observers;

use App\Team;
use Carbon\Carbon;
use DB;

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
                if ($role->name == 'Owner') {
                    if ((app()->version() >= 8 && class_exists('App\Models\User')) || (class_exists('App\Models\User') && ! class_exists('App\User'))) {
                        DB::table('model_has_roles')->updateOrInsert([
                            'role_id' => $newRole->id,
                            'model_type' => 'App\Models\User',
                            'model_id' => auth()->user()->id,
                        ]);
                    } else {
                        DB::table('model_has_roles')->updateOrInsert([
                            'role_id' => $newRole->id,
                            'model_type' => 'App\User',
                            'model_id' => auth()->user()->id,
                        ]);
                    }
                }
                
                foreach (DB::table('permissions')
                             ->leftJoin('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                             ->where('role_has_permissions.role_id', $role->id)
                             ->get() as $permission) {
                    DB::table('permissions')->updateOrInsert([
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'description' => $permission->description,
                        'crm_permission' => $permission->crm_permission,
                        'team_id' => $team->id,
                    ], [
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    if ($newPermission = DB::table('permissions')->where([
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'description' => $permission->description,
                        'crm_permission' => $permission->crm_permission,
                        'team_id' => $team->id,
                    ])->first()) {
                        DB::table('role_has_permissions')->updateOrInsert([
                            'permission_id' => $newPermission->id,
                            'role_id' => $newRole->id,
                        ]);
                    }
                }
            }
        }

        auth()->user()->assignRole('Owner');
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
