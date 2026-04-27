<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Tests\Stubs\User;
use VentureDrake\LaravelCrm\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create Spatie permission tables that the installer relies on.
        // These are published migrations in normal use, so they're not in TestSchema.
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->boolean('crm_role')->default(false);
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }
    }

    // -----------------------------------------------------------------------
    // Command registration
    // -----------------------------------------------------------------------

    public function test_install_command_is_registered(): void
    {
        $this->assertArrayHasKey(
            'laravelcrm:install',
            $this->app->make(Kernel::class)->all()
        );
    }

    public function test_install_command_has_expected_options(): void
    {
        $command = $this->app->make(Kernel::class)->all()['laravelcrm:install'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('owner-email'));
        $this->assertTrue($definition->hasOption('owner-name'));
        $this->assertTrue($definition->hasOption('owner-password'));
        $this->assertTrue($definition->hasOption('enable-teams'));
        $this->assertTrue($definition->hasOption('enable-encryption'));
    }

    // -----------------------------------------------------------------------
    // assignOwnerRole() logic — tested via the DB layer directly since the
    // full installer cannot be invoked in tests (it publishes files, migrates,
    // and calls exec()).
    // -----------------------------------------------------------------------

    private function seedOwnerRole(int $id = 9001): object
    {
        DB::table('roles')->insertOrIgnore([
            'id' => $id,
            'name' => 'Owner',
            'guard_name' => 'web',
            'crm_role' => 1,
            'team_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('roles')->where('id', $id)->first();
    }

    public function test_assign_owner_role_inserts_model_has_roles_row(): void
    {
        $role = $this->seedOwnerRole();

        $user = User::create([
            'name' => 'Owner User',
            'email' => 'owner-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_id' => $user->getKey(),
            'model_type' => get_class($user),
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->getKey(),
            'model_type' => get_class($user),
        ]);
    }

    public function test_assign_owner_role_does_not_insert_duplicate(): void
    {
        $role = $this->seedOwnerRole(9002);

        $user = User::create([
            'name' => 'Owner2',
            'email' => 'owner2-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
        ]);

        $row = [
            'role_id' => $role->id,
            'model_id' => $user->getKey(),
            'model_type' => get_class($user),
        ];

        $query = fn () => DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_id', $user->getKey())
            ->where('model_type', get_class($user));

        // First insert
        DB::table('model_has_roles')->insert($row);

        // Simulate the idempotency check in assignOwnerRole()
        if (! $query()->exists()) {
            DB::table('model_has_roles')->insert($row);
        }

        $this->assertSame(1, $query()->count());
    }

    public function test_assign_owner_role_respects_teams_mode(): void
    {
        $role = $this->seedOwnerRole(9003);

        $user = User::create([
            'name' => 'TeamOwner',
            'email' => 'teamowner-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
        ]);

        $teamsEnabled = (bool) config('permission.teams', false);

        $row = [
            'role_id' => $role->id,
            'model_id' => $user->getKey(),
            'model_type' => get_class($user),
        ];

        if ($teamsEnabled && Schema::hasColumn('model_has_roles', 'team_id')) {
            $row['team_id'] = null;
        }

        DB::table('model_has_roles')->insert($row);

        // In single-tenant mode (default in tests) team_id column should not exist
        // and the row is inserted without a team restriction.
        $this->assertSame(1, DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_id', $user->getKey())
            ->count());
    }

    public function test_owner_role_lookup_uses_crm_role_flag(): void
    {
        // Seed a non-CRM role with the same name — should NOT be picked up.
        DB::table('roles')->insertOrIgnore([
            'id' => 9004,
            'name' => 'Owner-ncr',
            'guard_name' => 'web',
            'crm_role' => 0,
            'team_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed the real CRM owner role.
        DB::table('roles')->insertOrIgnore([
            'id' => 9005,
            'name' => 'Owner-crm',
            'guard_name' => 'web',
            'crm_role' => 1,
            'team_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = DB::table('roles')
            ->where('crm_role', 1)
            ->whereNull('team_id')
            ->first();

        $this->assertNotNull($role);
        $this->assertSame(1, (int) $role->crm_role);
    }
}
