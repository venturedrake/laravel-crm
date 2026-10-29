<?php

/*
 * The CRM tables reference users with plain foreign keys, so deleting a user who
 * had a personal setting (crm_settings.user_id) or had created a record failed
 * with an integrity violation. UserObserver::deleting now releases those rows.
 */

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use VentureDrake\LaravelCrm\Models\Setting;
use VentureDrake\LaravelCrm\Observers\UserObserver;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    // TestSchema declares no foreign keys, so rebuild the tables the production
    // stubs constrain with theirs, and have SQLite enforce them.
    Schema::drop('crm_settings');
    Schema::create('crm_settings', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name');
        $table->text('value')->nullable();
        $table->boolean('global')->default(false);
        $table->unsignedBigInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
        $table->unsignedBigInteger('team_id')->nullable();
        $table->timestamps();
    });

    Schema::create('crm_widgets', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('user_created_id')->nullable();
        $table->foreign('user_created_id')->references('id')->on('users');
        $table->unsignedBigInteger('user_owner_id')->nullable();
        $table->foreign('user_owner_id')->references('id')->on('users');
    });

    DB::statement('PRAGMA foreign_keys = ON');

    // The stub user is not App\Models\User at boot, so the provider never observed it.
    User::observe(UserObserver::class);

    $this->user = User::create(['name' => 'Leaving', 'email' => 'leaving@example.com']);
    $this->colleague = User::create(['name' => 'Staying', 'email' => 'staying@example.com']);
});

afterEach(function () {
    DB::statement('PRAGMA foreign_keys = OFF');
});

test('a user with personal settings and created records can be deleted', function () {
    Setting::create(['name' => 'theme', 'value' => 'dark', 'user_id' => $this->user->id]);
    DB::table('crm_widgets')->insert([
        'user_created_id' => $this->user->id,
        'user_owner_id' => $this->colleague->id,
    ]);

    $this->user->deleteOrFail();

    expect(User::find($this->user->id))->toBeNull()
        ->and(DB::table('crm_widgets')->first())
        ->user_created_id->toBeNull()
        ->user_owner_id->toBe($this->colleague->id);
});

test('personal settings are removed rather than becoming global', function () {
    Setting::create(['name' => 'theme', 'value' => 'dark', 'user_id' => $this->user->id]);
    Setting::create(['name' => 'theme', 'value' => 'light', 'user_id' => $this->colleague->id]);
    Setting::create(['name' => 'currency', 'value' => 'USD']);

    $this->user->deleteOrFail();

    expect(DB::table('crm_settings')->orderBy('id')->get(['name', 'user_id'])->map(fn ($row) => (array) $row)->all())
        ->toBe([
            ['name' => 'theme', 'user_id' => $this->colleague->id],
            ['name' => 'currency', 'user_id' => null],
        ]);
});

test('crm team memberships are removed', function () {
    DB::table('crm_team_user')->insert(['crm_team_id' => 1, 'user_id' => $this->user->id]);

    $this->user->deleteOrFail();

    expect(DB::table('crm_team_user')->count())->toBe(0);
});

test('a required team-membership user_id does not block the delete', function () {
    Schema::drop('crm_team_user');
    Schema::create('crm_team_user', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('crm_team_id');
        $table->unsignedBigInteger('user_id');
        $table->foreign('user_id')->references('id')->on('users');
        $table->timestamps();
    });
    DB::table('crm_team_user')->insert(['crm_team_id' => 1, 'user_id' => $this->user->id]);

    $this->user->deleteOrFail();

    expect(User::find($this->user->id))->toBeNull()
        ->and(DB::table('crm_team_user')->count())->toBe(0);
});

/*
 * Filament's DeleteAction and a host's own profile page call a plain delete()
 * outside any transaction, so nothing may be cleaned up before a delete that
 * a required reference is going to block.
 */
test('a delete blocked by a required reference leaves the user data untouched', function (string $method) {
    Schema::create('crm_gadgets', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('user_id');
        $table->foreign('user_id')->references('id')->on('users');
    });
    DB::table('crm_gadgets')->insert(['user_id' => $this->user->id]);
    DB::table('crm_widgets')->insert(['user_created_id' => $this->user->id]);
    Setting::create(['name' => 'theme', 'value' => 'dark', 'user_id' => $this->user->id]);
    DB::table('crm_team_user')->insert(['crm_team_id' => 1, 'user_id' => $this->user->id]);

    expect(fn () => $this->user->{$method}())->toThrow(QueryException::class);

    expect(User::find($this->user->id))->not->toBeNull()
        ->and(DB::table('crm_widgets')->value('user_created_id'))->toBe($this->user->id)
        ->and(DB::table('crm_settings')->where('user_id', $this->user->id)->count())->toBe(1)
        ->and(DB::table('crm_team_user')->where('user_id', $this->user->id)->count())->toBe(1);
})->with(['delete', 'deleteOrFail']);
