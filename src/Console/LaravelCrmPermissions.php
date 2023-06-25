<?php

namespace VentureDrake\LaravelCrm\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class LaravelCrmPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravelcrm:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Laravel CRM permissions';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating LaravelCRM Permissions...');

        $this->comment('Clearing permissions cache');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $tableNames = config('permission.table_names');

        foreach (DB::table('teams')->get() as $team) {
            foreach (DB::table($tableNames['roles'])
                         ->where('crm_role', 1)
                         ->whereNull('team_id')
                         ->get() as $role) {
                $this->info('Inserting role '.$role->name.' for team '.$team->name);

                DB::table($tableNames['roles'])->updateOrInsert([
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'description' => $role->description,
                    'crm_role' => $role->crm_role,
                    'team_id' => $team->id,
                ], [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                if ($newRole = DB::table($tableNames['roles'])->where([
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'description' => $role->description,
                    'crm_role' => $role->crm_role,
                    'team_id' => $team->id,
                ])->first()) {
                    foreach (DB::table($tableNames['permissions'])
                                 ->leftJoin($tableNames['role_has_permissions'], $tableNames['permissions'].'.id', '=', $tableNames['role_has_permissions'].'.permission_id')
                                 ->where($tableNames['role_has_permissions'].'.role_id', $role->id)
                                 ->get() as $permission) {
                        $this->info('Assigning permission '.$permission->name.' for role '.$role->name);

                        DB::table($tableNames['role_has_permissions'])->updateOrInsert([
                            'permission_id' => $permission->id,
                            'role_id' => $newRole->id,
                        ]);
                    }
                }
            }
        }

        $this->info('LaravelCRM Permissions Update Complete.');
    }
}
