<?php

use VentureDrake\LaravelCrm\Tests\Stubs\User;

beforeEach(function () {
    config()->set('laravel-crm.modules', ['features']);
});

test('registering via the portal creates a user with crm_access = 0', function () {
    $email = 'newuser+'.uniqid().'@example.com';

    $response = $this->post('/crm/p/register', [
        'name' => 'New Portal User',
        'email' => $email,
        'password' => 'secret-pw',
        'password_confirmation' => 'secret-pw',
    ]);

    $response->assertRedirect();

    $user = User::where('email', $email)->first();

    expect($user)->not->toBeNull();
    expect((int) $user->crm_access)->toBe(0);
    $this->assertAuthenticatedAs($user);
});

test('user with crm_access = 0 receives 403 from the main CRM area', function () {
    $user = User::create([
        'name' => 'Portal Only',
        'email' => 'po+'.uniqid().'@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    $this->actingAs($user)->get('/crm/dashboard')->assertStatus(403);
});

test('user that registered via the portal cannot reach /crm', function () {
    $email = 'denied+'.uniqid().'@example.com';

    $this->post('/crm/p/register', [
        'name' => 'Denied User',
        'email' => $email,
        'password' => 'secret-pw',
        'password_confirmation' => 'secret-pw',
    ]);

    $user = User::where('email', $email)->first();

    $this->actingAs($user)->get('/crm/dashboard')->assertStatus(403);
});
