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
            [
                [
                    'id' => 5,
                ],
                [
                    'name' => 'Billing',
                ],
            ],
            [
                [
                    'id' => 6,
                ],
                [
                    'name' => 'Shipping',
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

        $timestamp = time();
        foreach (timezone_identifiers_list() as $zone) {
            date_default_timezone_set($zone);
            $zones['offset'] = date('P', $timestamp);
            $zones['diff_from_gtm'] = 'UTC/GMT '.date('P', $timestamp);

            \VentureDrake\LaravelCrm\Models\Timezone::updateOrCreate(['name' => $zone], $zones);
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

        Permission::firstOrCreate(['name' => 'create crm invoices', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm invoices', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm invoices', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm invoices', 'crm_permission' => 1]);

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

        Permission::firstOrCreate(['name' => 'create crm permissions', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm permissions', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm permissions', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm permissions', 'crm_permission' => 1]);

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

        Permission::firstOrCreate(['name' => 'create crm tax rates', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm tax rates', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm tax rates', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm tax rates', 'crm_permission' => 1]);

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

        Permission::firstOrCreate(['name' => 'create crm activities', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm activities', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm activities', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm activities', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm tasks', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm tasks', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm tasks', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm tasks', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm notes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm notes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm notes', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm notes', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm calls', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm calls', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm calls', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm calls', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm meetings', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm meetings', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm meetings', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm meetings', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm lunches', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm lunches', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm lunches', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm lunches', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm files', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm files', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm files', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm files', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm deliveries', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm deliveries', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm deliveries', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm deliveries', 'crm_permission' => 1]);

        Permission::firstOrCreate(['name' => 'create crm clients', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'view crm clients', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'edit crm clients', 'crm_permission' => 1]);
        Permission::firstOrCreate(['name' => 'delete crm clients', 'crm_permission' => 1]);

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
                'create crm activities',
                'view crm activities',
                'edit crm activities',
                'delete crm activities',
                'create crm tasks',
                'view crm tasks',
                'edit crm tasks',
                'delete crm tasks',
                'create crm notes',
                'view crm notes',
                'edit crm notes',
                'delete crm notes',
                'create crm calls',
                'view crm calls',
                'edit crm calls',
                'delete crm calls',
                'create crm meetings',
                'view crm meetings',
                'edit crm meetings',
                'delete crm meetings',
                'create crm lunches',
                'view crm lunches',
                'edit crm lunches',
                'delete crm lunches',
                'create crm files',
                'view crm files',
                'edit crm files',
                'delete crm files',
                'create crm invoices',
                'view crm invoices',
                'edit crm invoices',
                'delete crm invoices',
                'create crm deliveries',
                'view crm deliveries',
                'edit crm deliveries',
                'delete crm deliveries',
                'create crm clients',
                'view crm clients',
                'edit crm clients',
                'delete crm clients',
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
                'create crm activities',
                'view crm activities',
                'edit crm activities',
                'delete crm activities',
                'create crm tasks',
                'view crm tasks',
                'edit crm tasks',
                'delete crm tasks',
                'create crm notes',
                'view crm notes',
                'edit crm notes',
                'delete crm notes',
                'create crm calls',
                'view crm calls',
                'edit crm calls',
                'delete crm calls',
                'create crm meetings',
                'view crm meetings',
                'edit crm meetings',
                'delete crm meetings',
                'create crm lunches',
                'view crm lunches',
                'edit crm lunches',
                'delete crm lunches',
                'create crm files',
                'view crm files',
                'edit crm files',
                'delete crm files',
                'create crm invoices',
                'view crm invoices',
                'edit crm invoices',
                'delete crm invoices',
                'create crm deliveries',
                'view crm deliveries',
                'edit crm deliveries',
                'delete crm deliveries',
                'create crm clients',
                'view crm clients',
                'edit crm clients',
                'delete crm clients',
            ]);
    }
}
