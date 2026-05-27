<?php

use Illuminate\Support\Facades\Hash;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

test('portal login page is accessible to guests', function () {
    $response = $this->get('/p/login');

    $response->assertStatus(200);
});

test('portal register page is accessible to guests', function () {
    $response = $this->get('/p/register');

    $response->assertStatus(200);
});

test('registering creates a user with crm_access = 0 and logs them in', function () {
    $response = $this->post('/p/register', [
        'name' => 'Voter Vince',
        'email' => 'voter+'.uniqid().'@example.com',
        'password' => 'secret-pw',
        'password_confirmation' => 'secret-pw',
    ]);

    $response->assertRedirect();

    $user = User::orderByDesc('id')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Voter Vince');
    expect((int) $user->crm_access)->toBe(0);
    expect(Hash::check('secret-pw', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

test('register validates required fields, unique email, and confirmed password', function () {
    User::create([
        'name' => 'Existing',
        'email' => 'taken@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    $response = $this->from('/p/register')->post('/p/register', [
        'name' => '',
        'email' => 'taken@example.com',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertRedirect('/p/register');
    $response->assertSessionHasErrors(['name', 'email', 'password']);
});

test('login authenticates valid credentials and supports intended redirects', function () {
    $user = User::create([
        'name' => 'Returning User',
        'email' => 'returner@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    // Prime the intended URL by hitting a protected page first.
    $this->get('/crm/dashboard');

    $response = $this->post('/p/login', [
        'email' => 'returner@example.com',
        'password' => 'secret-pw',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

test('login rejects invalid credentials', function () {
    User::create([
        'name' => 'Bad Login',
        'email' => 'bad@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    $response = $this->from('/p/login')->post('/p/login', [
        'email' => 'bad@example.com',
        'password' => 'wrong-pw',
    ]);

    $response->assertRedirect('/p/login');
    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('logout signs the user out and redirects to portal login', function () {
    $user = User::create([
        'name' => 'Logout User',
        'email' => 'lo@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    $this->actingAs($user);

    $response = $this->post('/p/logout');

    $response->assertRedirect('/p/login');
    $this->assertGuest();
});

test('user with crm_access = 0 is forbidden from the main CRM area', function () {
    $user = User::create([
        'name' => 'Portal Only',
        'email' => 'po@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    $response = $this->actingAs($user)->get('/crm/dashboard');

    $response->assertStatus(403);
});

test('portal layout header shows guest links when unauthenticated', function () {
    $response = $this->get('/p/login');

    $response->assertSee(route('laravel-crm.portal.login'), false);
    $response->assertSee(route('laravel-crm.portal.register'), false);
});

test('authenticated visits to login and register redirect away', function () {
    $user = User::create([
        'name' => 'Greeted User',
        'email' => 'greet@example.com',
        'password' => bcrypt('secret-pw'),
        'crm_access' => 0,
    ]);

    $this->actingAs($user)->get('/p/login')->assertRedirect();
    $this->actingAs($user)->get('/p/register')->assertRedirect();
});
