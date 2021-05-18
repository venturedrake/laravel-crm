<?php

namespace VentureDrake\LaravelCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LaravelCrmTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Hot',
                    'hex' => 'dc3545',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Cold',
                    'hex' => '007bff',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
            [
                [
                    'id' => 3,
                ],
                [
                    'name' => 'Warm',
                    'hex' => 'ffc107',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\Label::firstOrCreate($item[0], $item[1]);
        }

        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Lead In',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Contacted',
                    'external_id' => Uuid::uuid4()->toString(),
                ],
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\LeadStatus::firstOrCreate($item[0], $item[1]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'create crm leads', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm leads', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm leads', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm leads', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm deals', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm deals', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm deals', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm deals', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm people', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm people', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm people', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm people', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm organisations', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm organisations', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm organisations', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm organisations', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm users', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm users', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm users', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm users', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm teams', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm teams', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm teams', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm teams', 'crm_permission' => 1]);
        
        Permission::create(['name' => 'view crm settings', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm settings', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm roles', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm roles', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm roles', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm roles', 'crm_permission' => 1]);

        Permission::create(['name' => 'create crm products', 'crm_permission' => 1]);
        Permission::create(['name' => 'view crm products', 'crm_permission' => 1]);
        Permission::create(['name' => 'edit crm products', 'crm_permission' => 1]);
        Permission::create(['name' => 'delete crm products', 'crm_permission' => 1]);
        
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
}
