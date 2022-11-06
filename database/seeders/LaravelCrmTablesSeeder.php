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
        // Labels
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

        // Lead statuses
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

        // Organisation Types
        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Sole Trader',
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Partnership',
                ],
            ],
            [
                [
                    'id' => 3,
                ],
                [
                    'name' => 'Company',
                ],
            ],
            [
                [
                    'id' => 4,
                ],
                [
                    'name' => 'Trust',
                ],
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\OrganisationType::firstOrCreate($item[0], $item[1]);
        }
        
        // Address types
        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Current',
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Previous',
                ],
            ],
            [
                [
                    'id' => 3,
                ],
                [
                    'name' => 'Postal',
                ],
            ],
            [
                [
                    'id' => 4,
                ],
                [
                    'name' => 'Business',
                ],
            ],
        ];

        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\AddressType::firstOrCreate($item[0], $item[1]);
        }
        
        // Contact types
        $items = [
            [
                [
                    'id' => 1,
                ],
                [
                    'name' => 'Primary',
                ],
            ],
            [
                [
                    'id' => 2,
                ],
                [
                    'name' => 'Secondary',
                ],
            ],
        ];
        
        foreach ($items as $item) {
            \VentureDrake\LaravelCrm\Models\ContactType::firstOrCreate($item[0], $item[1]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'create crm leads', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm leads', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm leads', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm leads', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm deals', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm deals', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm deals', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm deals', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm quotes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm quotes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm quotes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm quotes', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm orders', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm orders', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm orders', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm orders', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm people', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm people', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm people', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm people', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm organisations', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm organisations', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm organisations', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm organisations', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm contacts', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm contacts', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm contacts', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm contacts', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm users', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm users', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm users', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm users', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm teams', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm teams', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm teams', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm teams', 'crm_permission' => 1]);
        
        Permission::firstOrCreate(['name' => 'view crm settings', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm settings', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'view crm updates', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm roles', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm roles', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm roles', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm roles', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm products', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm products', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm products', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm products', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm product categories', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm product categories', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm product categories', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm product categories', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm product attributes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm product attributes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm product attributes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm product attributes', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm labels', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm labels', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm labels', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm labels', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm fields', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm fields', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm fields', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm fields', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm integrations', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm integrations', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm integrations', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm integrations', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm tasks', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm tasks', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm tasks', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm tasks', 'crm_permission' => 1]);
        
        if (config('permission.teams')) {
            $roleArray = ['name' => 'Owner', 'crm_role' => 1, 'team_id' => null];
        } else {
            $roleArray = ['name' => 'Owner', 'crm_role' => 1];
        }

        $role = Role::firstOrCreate($roleArray)
            ->givePermissionTo(Permission::all());

        if (config('permission.teams')) {
            $roleArray = ['name' => 'Admin', 'crm_role' => 1, 'team_id' => null];
        } else {
            $roleArray = ['name' => 'Admin', 'crm_role' => 1];
        }
        
        $role = Role::firstOrCreate($roleArray)
            ->givePermissionTo(Permission::all());

        if (config('permission.teams')) {
            $roleArray = ['name' => 'Manager', 'crm_role' => 1, 'team_id' => null];
        } else {
            $roleArray = ['name' => 'Manager', 'crm_role' => 1];
        }
        
        $role = Role::firstOrCreate($roleArray)
            ->givePermissionTo([
                'create crm leads',
                'view crm leads',
                'edit crm leads',
                'delete crm leads',
                'create crm deals',
                'view crm deals',
                'edit crm deals',
                'delete crm deals',
                'create crm quotes',
                'view crm quotes',
                'edit crm quotes',
                'delete crm quotes',
                'create crm orders',
                'view crm orders',
                'edit crm orders',
                'delete crm orders',
                'create crm people',
                'view crm people',
                'edit crm people',
                'delete crm people',
                'create crm organisations',
                'view crm organisations',
                'edit crm organisations',
                'delete crm organisations',
                'create crm contacts',
                'view crm contacts',
                'edit crm contacts',
                'delete crm contacts',
                'create crm tasks',
                'view crm tasks',
                'edit crm tasks',
                'delete crm tasks',
            ]);

        if (config('permission.teams')) {
            $roleArray = ['name' => 'Employee', 'crm_role' => 1, 'team_id' => null];
        } else {
            $roleArray = ['name' => 'Employee', 'crm_role' => 1];
        }
        
        $role = Role::firstOrCreate($roleArray)
            ->givePermissionTo([
                'create crm leads',
                'view crm leads',
                'edit crm leads',
                'delete crm leads',
                'create crm deals',
                'view crm deals',
                'edit crm deals',
                'delete crm deals',
                'create crm quotes',
                'view crm quotes',
                'edit crm quotes',
                'delete crm quotes',
                'create crm orders',
                'view crm orders',
                'edit crm orders',
                'delete crm orders',
                'create crm people',
                'view crm people',
                'edit crm people',
                'delete crm people',
                'create crm organisations',
                'view crm organisations',
                'edit crm organisations',
                'delete crm organisations',
                'create crm contacts',
                'view crm contacts',
                'edit crm contacts',
                'delete crm contacts',
                'create crm tasks',
                'view crm tasks',
                'edit crm tasks',
                'delete crm tasks',
            ]);
    }
}
