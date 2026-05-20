<?php

use Laravel\Sanctum\PersonalAccessToken;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function authTokenUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'API User',
        'email' => 'api-user-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

test('POST /auth/token returns 201 with token and user for valid credentials', function () {
    $user = authTokenUser();

    $response = $this->postJson('/api/crm/v2/auth/token', [
        'email' => $user->email,
        'password' => 'secret-password',
        'device_name' => 'pest-test',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    $payload = $response->json();

    expect($payload['user']['id'])->toBe($user->id);
    expect($payload['user']['email'])->toBe($user->email);
    expect($payload['token'])->toBeString()->not->toBe('');

    expect(PersonalAccessToken::count())->toBe(1);
});

test('POST /auth/token returns 422 for invalid credentials', function () {
    $user = authTokenUser();

    $response = $this->postJson('/api/crm/v2/auth/token', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');

    expect(PersonalAccessToken::count())->toBe(0);
});

test('POST /auth/token returns 422 when an unknown email is supplied', function () {
    $response = $this->postJson('/api/crm/v2/auth/token', [
        'email' => 'does-not-exist@example.com',
        'password' => 'secret-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('POST /auth/token returns 422 when fields are missing', function () {
    $response = $this->postJson('/api/crm/v2/auth/token', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password']);
});

test('POST /auth/token returns 422 when the user lacks crm_access', function () {
    $user = authTokenUser(['crm_access' => false]);

    $response = $this->postJson('/api/crm/v2/auth/token', [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    expect(PersonalAccessToken::count())->toBe(0);
});

test('GET /auth/me returns the authenticated user', function () {
    $user = authTokenUser();
    $plainToken = $user->createToken('me-test')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$plainToken,
        'Accept' => 'application/json',
    ])->getJson('/api/crm/v2/auth/me');

    $response->assertOk();
    $response->assertJson([
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
        ],
    ]);
});

test('GET /auth/me returns 401 when no token is supplied', function () {
    $response = $this->getJson('/api/crm/v2/auth/me');

    $response->assertStatus(401);
});

test('DELETE /auth/token revokes the current bearer token and returns 204', function () {
    $user = authTokenUser();
    $plainToken = $user->createToken('revoke-test')->plainTextToken;

    expect(PersonalAccessToken::count())->toBe(1);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$plainToken,
        'Accept' => 'application/json',
    ])->deleteJson('/api/crm/v2/auth/token');

    $response->assertStatus(204);

    expect(PersonalAccessToken::count())->toBe(0);
    expect(PersonalAccessToken::where('tokenable_id', $user->id)->exists())->toBeFalse();
});

test('DELETE /auth/token returns 401 when unauthenticated', function () {
    $response = $this->deleteJson('/api/crm/v2/auth/token');

    $response->assertStatus(401);
});
