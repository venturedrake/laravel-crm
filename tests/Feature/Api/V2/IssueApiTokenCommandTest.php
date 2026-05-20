<?php

use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\PersonalAccessToken;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

function issueTokenCommandUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Ops User',
        'email' => 'ops-'.uniqid().'@example.com',
        'password' => bcrypt('secret-password'),
        'crm_access' => true,
    ], $attributes));
}

test('laravel-crm:api-token issues a token and persists it', function () {
    $user = issueTokenCommandUser();

    $exitCode = Artisan::call('laravel-crm:api-token', [
        'email' => $user->email,
        '--name' => 'Mobile App',
    ]);

    expect($exitCode)->toBe(0);

    $output = Artisan::output();
    expect(trim($output))->not->toBe('');

    expect(PersonalAccessToken::count())->toBe(1);

    $token = PersonalAccessToken::first();
    expect($token->name)->toBe('Mobile App');
    expect($token->tokenable_id)->toBe($user->id);

    [, $plainText] = explode('|', trim($output));
    expect(hash('sha256', $plainText))->toBe($token->token);
});

test('laravel-crm:api-token defaults the token name when --name is omitted', function () {
    $user = issueTokenCommandUser();

    $exitCode = Artisan::call('laravel-crm:api-token', [
        'email' => $user->email,
    ]);

    expect($exitCode)->toBe(0);
    expect(PersonalAccessToken::count())->toBe(1);
    expect(PersonalAccessToken::first()->name)->toBe('api-token');
});

test('laravel-crm:api-token exits non-zero when user not found', function () {
    $exitCode = Artisan::call('laravel-crm:api-token', [
        'email' => 'nobody@example.com',
    ]);

    expect($exitCode)->not->toBe(0);
    expect(Artisan::output())->toContain('No user found');
    expect(PersonalAccessToken::count())->toBe(0);
});

test('laravel-crm:api-token exits non-zero when user lacks crm_access', function () {
    $user = issueTokenCommandUser(['crm_access' => false]);

    $exitCode = Artisan::call('laravel-crm:api-token', [
        'email' => $user->email,
    ]);

    expect($exitCode)->not->toBe(0);
    expect(Artisan::output())->toContain('does not have CRM access');
    expect(PersonalAccessToken::count())->toBe(0);
});
