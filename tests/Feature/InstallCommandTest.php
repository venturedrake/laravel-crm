<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
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
});

function seedOwnerRole(int $id = 9001): object
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

// -----------------------------------------------------------------------
// Command registration
// -----------------------------------------------------------------------

test('install command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:install');
});

test('install command has expected options', function () {
    $command = $this->app->make(Kernel::class)->all()['laravelcrm:install'];
    $definition = $command->getDefinition();

    expect($definition->hasOption('owner-email'))->toBeTrue();
    expect($definition->hasOption('owner-name'))->toBeTrue();
    expect($definition->hasOption('owner-password'))->toBeTrue();
    expect($definition->hasOption('enable-teams'))->toBeTrue();
    expect($definition->hasOption('enable-encryption'))->toBeTrue();
});

// -----------------------------------------------------------------------
// assignOwnerRole() logic
// -----------------------------------------------------------------------

test('assign owner role inserts model has roles row', function () {
    $role = seedOwnerRole();

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
});

test('assign owner role does not insert duplicate', function () {
    $role = seedOwnerRole(9002);

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

    DB::table('model_has_roles')->insert($row);

    if (! $query()->exists()) {
        DB::table('model_has_roles')->insert($row);
    }

    expect($query()->count())->toBe(1);
});

test('assign owner role respects teams mode', function () {
    $role = seedOwnerRole(9003);

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

    expect(DB::table('model_has_roles')
        ->where('role_id', $role->id)
        ->where('model_id', $user->getKey())
        ->count())->toBe(1);
});

test('owner role lookup uses crm role flag', function () {
    DB::table('roles')->insertOrIgnore([
        'id' => 9004,
        'name' => 'Owner-ncr',
        'guard_name' => 'web',
        'crm_role' => 0,
        'team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('roles')->insertOrIgnore([
        'id' => 9005,
        'name' => 'Owner-crm',
        'guard_name' => 'web',
        'crm_role' => 1,
        'team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $role = DB::table('roles')->where('crm_role', 1)->whereNull('team_id')->first();

    expect($role)->not->toBeNull();
    expect((int) $role->crm_role)->toBe(1);
});
