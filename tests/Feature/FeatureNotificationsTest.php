<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use VentureDrake\LaravelCrm\Livewire\Features\FeatureBoard;
use VentureDrake\LaravelCrm\Models\Feature;
use VentureDrake\LaravelCrm\Models\FeatureComment;
use VentureDrake\LaravelCrm\Models\FeatureStatus;
use VentureDrake\LaravelCrm\Notifications\FeatureCommentPostedNotification;
use VentureDrake\LaravelCrm\Notifications\FeatureStatusChangedNotification;
use VentureDrake\LaravelCrm\Notifications\FeatureSubmittedNotification;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function ensureRoleTables(): void
{
    if (! Schema::hasTable('roles')) {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('model_has_roles')) {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }
}

function makeOwnerRole(): int
{
    return DB::table('roles')->insertGetId([
        'name' => 'Owner',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function makeEmployeeRole(): int
{
    return DB::table('roles')->insertGetId([
        'name' => 'Employee',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function assignRole(int $userId, int $roleId, ?int $teamId = null): void
{
    DB::table('model_has_roles')->insert([
        'role_id' => $roleId,
        'team_id' => $teamId,
        'model_type' => User::class,
        'model_id' => $userId,
    ]);
}

function makeFeatureUser(array $attrs = []): User
{
    return User::create(array_merge([
        'name' => 'User '.uniqid(),
        'email' => 'fn-'.uniqid().'@example.com',
        'password' => bcrypt('secret'),
    ], $attrs));
}

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);

    ensureRoleTables();
    DB::table('model_has_roles')->delete();
    DB::table('roles')->delete();

    FeatureStatus::query()->delete();
    $this->newStatus = FeatureStatus::create(['name' => 'New', 'is_default' => true, 'order' => 1]);
    $this->plannedStatus = FeatureStatus::create(['name' => 'Planned', 'order' => 2]);
});

test('creating a feature notifies all Owner-role users', function () {
    $ownerRoleId = makeOwnerRole();
    $ownerA = makeFeatureUser(['name' => 'Owner A']);
    $ownerB = makeFeatureUser(['name' => 'Owner B']);
    assignRole($ownerA->id, $ownerRoleId);
    assignRole($ownerB->id, $ownerRoleId);

    Notification::fake();

    Feature::create(['title' => 'New thing', 'is_public' => true]);

    Notification::assertSentTo($ownerA, FeatureSubmittedNotification::class);
    Notification::assertSentTo($ownerB, FeatureSubmittedNotification::class);
});

test('non-Owner users are not notified on feature creation', function () {
    $ownerRoleId = makeOwnerRole();
    $employeeRoleId = makeEmployeeRole();

    $owner = makeFeatureUser(['name' => 'Owner']);
    $employee = makeFeatureUser(['name' => 'Employee']);
    $unassigned = makeFeatureUser(['name' => 'Unassigned']);

    assignRole($owner->id, $ownerRoleId);
    assignRole($employee->id, $employeeRoleId);

    Notification::fake();

    Feature::create(['title' => 'Another', 'is_public' => true]);

    Notification::assertSentTo($owner, FeatureSubmittedNotification::class);
    Notification::assertNotSentTo($employee, FeatureSubmittedNotification::class);
    Notification::assertNotSentTo($unassigned, FeatureSubmittedNotification::class);
});

test('teams mode scopes Owners to the feature team', function () {
    config()->set('laravel-crm.teams', true);

    $ownerRoleId = makeOwnerRole();

    $ownerTeam1 = makeFeatureUser(['name' => 'Owner T1']);
    $ownerTeam2 = makeFeatureUser(['name' => 'Owner T2']);
    assignRole($ownerTeam1->id, $ownerRoleId, teamId: 1);
    assignRole($ownerTeam2->id, $ownerRoleId, teamId: 2);

    Notification::fake();

    Feature::create(['title' => 'Team 1 feature', 'is_public' => true, 'team_id' => 1]);

    Notification::assertSentTo($ownerTeam1, FeatureSubmittedNotification::class);
    Notification::assertNotSentTo($ownerTeam2, FeatureSubmittedNotification::class);

    config()->set('laravel-crm.teams', false);
});

test('status change notifies submitter and all voters, deduped', function () {
    $submitter = makeFeatureUser(['name' => 'Submitter']);
    $voterA = makeFeatureUser(['name' => 'Voter A']);
    $voterB = makeFeatureUser(['name' => 'Voter B']);

    $feature = Feature::create([
        'title' => 'Status feature',
        'is_public' => true,
        'submitted_by_user_id' => $submitter->id,
    ]);
    $feature->voters()->attach([$voterA->id, $voterB->id]);

    Notification::fake();

    $feature->update(['feature_status_id' => $this->plannedStatus->id]);

    Notification::assertSentTo($submitter, FeatureStatusChangedNotification::class);
    Notification::assertSentTo($voterA, FeatureStatusChangedNotification::class);
    Notification::assertSentTo($voterB, FeatureStatusChangedNotification::class);

    Notification::assertSentToTimes($submitter, FeatureStatusChangedNotification::class, 1);
    Notification::assertSentToTimes($voterA, FeatureStatusChangedNotification::class, 1);
    Notification::assertSentToTimes($voterB, FeatureStatusChangedNotification::class, 1);
});

test('updating non-status columns sends nothing', function () {
    $submitter = makeFeatureUser(['name' => 'Submitter']);
    $voter = makeFeatureUser(['name' => 'Voter']);

    $feature = Feature::create([
        'title' => 'Title before',
        'is_public' => true,
        'submitted_by_user_id' => $submitter->id,
    ]);
    $feature->voters()->attach($voter->id);

    Notification::fake();

    $feature->update(['title' => 'Title after', 'description' => 'New body']);

    Notification::assertNothingSent();
});

test('the actor is excluded from status-change emails', function () {
    $submitter = makeFeatureUser(['name' => 'Submitter']);
    $voter = makeFeatureUser(['name' => 'Voter Actor']);

    $feature = Feature::create([
        'title' => 'Excluded actor',
        'is_public' => true,
        'submitted_by_user_id' => $submitter->id,
    ]);
    $feature->voters()->attach($voter->id);

    $this->actingAs($voter);

    Notification::fake();

    $feature->update(['feature_status_id' => $this->plannedStatus->id]);

    Notification::assertSentTo($submitter, FeatureStatusChangedNotification::class);
    Notification::assertNotSentTo($voter, FeatureStatusChangedNotification::class);
});

test('a user who is both voter and submitter receives exactly one notification per status change', function () {
    $user = makeFeatureUser(['name' => 'Submitter Voter']);

    $feature = Feature::create([
        'title' => 'Dual role',
        'is_public' => true,
        'submitted_by_user_id' => $user->id,
    ]);
    $feature->voters()->attach($user->id);

    Notification::fake();

    $feature->update(['feature_status_id' => $this->plannedStatus->id]);

    Notification::assertSentToTimes($user, FeatureStatusChangedNotification::class, 1);
});

test('FeatureComment creation notifies ownerUser and Owner-role users, excluding the author', function () {
    $ownerRoleId = makeOwnerRole();

    $ownerUser = makeFeatureUser(['name' => 'Feature Owner User']);
    $ownerRoleUser = makeFeatureUser(['name' => 'Owner Role User']);
    $author = makeFeatureUser(['name' => 'Author']);

    assignRole($ownerRoleUser->id, $ownerRoleId);
    assignRole($author->id, $ownerRoleId);

    $feature = Feature::create([
        'title' => 'Commentable',
        'is_public' => true,
        'user_owner_id' => $ownerUser->id,
    ]);

    Notification::fake();

    FeatureComment::create([
        'feature_id' => $feature->id,
        'body' => 'Hello',
        'user_created_id' => $author->id,
    ]);

    Notification::assertSentTo($ownerUser, FeatureCommentPostedNotification::class);
    Notification::assertSentTo($ownerRoleUser, FeatureCommentPostedNotification::class);
    Notification::assertNotSentTo($author, FeatureCommentPostedNotification::class);
});

test('users with null or empty email are skipped on all three triggers', function () {
    $ownerRoleId = makeOwnerRole();

    // One empty-email user wears all three hats: Owner role (submission + comment recipient)
    // and voter/submitter (status-change recipient). Single user keeps us clear of the
    // users.email unique constraint while exercising every skip path in the trait.
    $emptyEmail = makeFeatureUser(['name' => 'Empty Email', 'email' => '']);
    $validOwner = makeFeatureUser(['name' => 'Valid Owner']);

    assignRole($emptyEmail->id, $ownerRoleId);
    assignRole($validOwner->id, $ownerRoleId);

    // Trigger 1: feature submission — empty-email Owner should be skipped, valid Owner notified.
    Notification::fake();

    $feature = Feature::create([
        'title' => 'No-email screen',
        'is_public' => true,
        'submitted_by_user_id' => $emptyEmail->id,
    ]);

    Notification::assertSentTo($validOwner, FeatureSubmittedNotification::class);
    Notification::assertNotSentTo($emptyEmail, FeatureSubmittedNotification::class);

    $feature->voters()->attach($emptyEmail->id);

    // Trigger 2: status change — empty-email submitter+voter should be skipped on both legs.
    Notification::fake();

    $feature->update(['feature_status_id' => $this->plannedStatus->id]);

    Notification::assertNotSentTo($emptyEmail, FeatureStatusChangedNotification::class);

    // Trigger 3: comment posted — empty-email Owner-role user should be skipped.
    Notification::fake();

    FeatureComment::create([
        'feature_id' => $feature->id,
        'body' => 'A comment',
        'user_created_id' => $validOwner->id,
    ]);

    Notification::assertNotSentTo($emptyEmail, FeatureCommentPostedNotification::class);
});

test('FeatureBoard onStageChanged drag fires the status-change notification', function () {
    $actor = $this->actingAsUser(['crm_access' => 1, 'name' => 'Actor']);

    $submitter = makeFeatureUser(['name' => 'Drag Submitter']);
    $voter = makeFeatureUser(['name' => 'Drag Voter']);

    $feature = Feature::create([
        'title' => 'Drag me',
        'is_public' => true,
        'submitted_by_user_id' => $submitter->id,
    ]);
    $feature->voters()->attach($voter->id);

    Notification::fake();

    Livewire::test(FeatureBoard::class)
        ->call('onStageChanged', $feature->id, $this->plannedStatus->id, [], [$feature->id]);

    Notification::assertSentTo($submitter, FeatureStatusChangedNotification::class);
    Notification::assertSentTo($voter, FeatureStatusChangedNotification::class);
    Notification::assertNotSentTo($actor, FeatureStatusChangedNotification::class);
});
