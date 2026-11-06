<?php

use Illuminate\Support\Facades\Validator;
use VentureDrake\LaravelCrm\Http\Rules\ContactEmail;
use VentureDrake\LaravelCrm\Http\Rules\PhoneNumber;

function phonePasses(mixed $value): bool
{
    return Validator::make(['phone' => $value], ['phone' => ['nullable', new PhoneNumber]])->passes();
}

function emailPasses(mixed $value): bool
{
    return Validator::make(['email' => $value], ['email' => ['nullable', new ContactEmail]])->passes();
}

test('a phone number may be written many ways', function (string $number) {
    expect(phonePasses($number))->toBeTrue();
})->with([
    '+61 2 9999 9999',
    '(02) 9999-9999',
    '0412 345 678 ext 12',
    '0412 345 678 EXT 12',
    '0412 345 678 ext. 12',
    '0412 345 678 x12',
    '0412345678x12',
    '02.9999.9999',
    '123456',
    '+1 (555) 010-9999',
]);

test('anything else is not a phone number', function (mixed $number) {
    expect(phonePasses($number))->toBeFalse();
})->with([
    'letters' => ['zzz'],
    'the lead repro' => ['abc-not-a-phone'],
    'a letter among digits' => ['0412 345 67a'],
    'too few digits' => ['12345'],
    'digits only in the extension' => ['123 ext 456789'],
    'only punctuation' => ['+-() .'],
    'an extension with no number' => ['0412 345 678 ext'],
    'a slash' => ['02/9999/9999'],
    'a trailing newline' => ["0412345678\n"],
    'not a string' => [[123456]],
]);

test('a blank phone is left alone', function (?string $value) {
    expect(phonePasses($value))->toBeTrue();
})->with([null, '']);

test('a phone sent as a whole number is checked like any other', function () {
    expect(phonePasses(61299999999))->toBeTrue()
        ->and(phonePasses(12345))->toBeFalse();
});

test('a very long value is refused without backtracking', function (string $value) {
    $start = microtime(true);

    expect(phonePasses($value))->toBeFalse()
        ->and(microtime(true) - $start)->toBeLessThan(1.0);
})->with([
    'spaces' => ['1'.str_repeat(' ', 300_000).'a'],
    'digits' => [str_repeat('1', 300_000).'a'],
]);

test('a saved value passes as it is, and nothing else does', function () {
    $phone = fn (string $value) => Validator::make(
        ['phone' => $value],
        ['phone' => [new PhoneNumber(['1300 FLOWERS'])]],
    )->passes();

    $email = fn (string $value) => Validator::make(
        ['email' => $value],
        ['email' => [new ContactEmail(['N/A'])]],
    )->passes();

    expect($phone('1300 FLOWERS'))->toBeTrue()
        ->and($phone('1300 ROSES'))->toBeFalse()
        ->and($email('N/A'))->toBeTrue()
        ->and($email('n/a'))->toBeFalse();
});

test('a well-formed email passes without a DNS lookup', function (string $email) {
    expect(emailPasses($email))->toBeTrue();
})->with(['pat@example.com', 'pat.o+crm@sub.example.co.uk', 'pat@no-such-domain.invalid']);

test('a malformed email is refused', function (string $email) {
    expect(emailPasses($email))->toBeFalse();
})->with(['pat@@invalid', 'bad-email', 'pat@', '@example.com', 'two words@example.com']);

test('a blank email is left alone', function (?string $value) {
    expect(emailPasses($value))->toBeTrue();
})->with([null, '']);

test('each rule explains itself in the field\'s own message', function () {
    $errors = Validator::make(
        ['email' => 'bad-email', 'phone' => 'zzz'],
        ['email' => [new ContactEmail], 'phone' => [new PhoneNumber]],
    )->errors();

    expect($errors->first('email'))->toBe('Enter a valid email address, like name@example.com')
        ->and($errors->first('phone'))->toBe('Enter a valid phone number, like +61 2 9999 9999');
});
