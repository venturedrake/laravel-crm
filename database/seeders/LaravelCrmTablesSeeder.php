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

        Permission::create(['name' => 'create crm leads']);
        Permission::create(['name' => 'view crm leads']);
        Permission::create(['name' => 'edit crm leads']);
        Permission::create(['name' => 'delete crm leads']);

        Permission::create(['name' => 'create crm deals']);
        Permission::create(['name' => 'view crm deals']);
        Permission::create(['name' => 'edit crm deals']);
        Permission::create(['name' => 'delete crm deals']);

        Permission::create(['name' => 'create crm people']);
        Permission::create(['name' => 'view crm people']);
        Permission::create(['name' => 'edit crm people']);
        Permission::create(['name' => 'delete crm people']);

        Permission::create(['name' => 'create crm organisations']);
        Permission::create(['name' => 'view crm organisations']);
        Permission::create(['name' => 'edit crm organisations']);
        Permission::create(['name' => 'delete crm organisations']);

        Permission::create(['name' => 'create crm users']);
        Permission::create(['name' => 'view crm users']);
        Permission::create(['name' => 'edit crm users']);
        Permission::create(['name' => 'delete crm users']);

        Permission::create(['name' => 'create crm teams']);
        Permission::create(['name' => 'view crm teams']);
        Permission::create(['name' => 'edit crm teams']);
        Permission::create(['name' => 'delete crm teams']);
        
        $role = Role::create(['name' => 'CRM Owner'])
            ->givePermissionTo(Permission::all());
        
        $role = Role::create(['name' => 'CRM Administrator'])
            ->givePermissionTo(Permission::all());
        
        $role = Role::create(['name' => 'CRM Manager'])
            ->givePermissionTo([
                'create crm leads',
                'view crm leads',
                'edit crm leads',
                'delete crm leads',
                'create crm deals',
                'view crm leads',
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
        
        $role = Role::create(['name' => 'CRM Team Leader']);
        $role = Role::create(['name' => 'CRM Employee']);
    }
}
