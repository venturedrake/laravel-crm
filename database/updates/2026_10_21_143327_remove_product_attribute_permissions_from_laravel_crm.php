<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/*
 * Product Attributes was an unfinished v1 stub and has been removed (#53). The
 * seeder no longer creates its four permissions, but existing installs still have
 * them, and the role form lists every permission row, so they would keep showing
 * up as checkboxes that grant nothing.
 *
 * The pivot rows are deleted explicitly rather than left to Spatie's cascading
 * foreign keys: SQLite only enforces those with foreign_key_constraints on, and
 * hosts arrive here with a wide range of schemas.
 *
 * down() recreates the permissions but cannot restore which roles or users held
 * them.
 */
return new class extends Migration
{
    private array $permissions = [
        'create crm product attributes',
        'view crm product attributes',
        'edit crm product attributes',
        'delete crm product attributes',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissionsTable = config('permission.table_names.permissions');

        if (! $permissionsTable || ! Schema::hasTable($permissionsTable)) {
            return;
        }

        $ids = DB::table($permissionsTable)->whereIn('name', $this->permissions)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $pivotKey = config('permission.column_names.permission_pivot_key') ?: 'permission_id';

        foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
            $pivotTable = config('permission.table_names.'.$pivot);

            if ($pivotTable && Schema::hasTable($pivotTable)) {
                DB::table($pivotTable)->whereIn($pivotKey, $ids)->delete();
            }
        }

        DB::table($permissionsTable)->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $permissionsTable = config('permission.table_names.permissions');

        if (! $permissionsTable || ! Schema::hasTable($permissionsTable)) {
            return;
        }

        $permissionModel = config('permission.models.permission');
        $hasCrmFlag = Schema::hasColumn($permissionsTable, 'crm_permission');

        foreach ($this->permissions as $name) {
            $permissionModel::firstOrCreate(
                $hasCrmFlag ? ['name' => $name, 'crm_permission' => 1] : ['name' => $name]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
