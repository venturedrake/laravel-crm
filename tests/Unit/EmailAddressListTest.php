<?php

use Illuminate\Support\Facades\Validator;
use VentureDrake\LaravelCrm\Http\Rules\EmailAddressList;

it('splits a recipient field into trimmed addresses', function (?string $value, array $expected) {
    expect(EmailAddressList::parse($value))->toBe($expected);
})->with([
    'single' => ['a@example.com', ['a@example.com']],
    'comma' => ['a@example.com,b@example.com', ['a@example.com', 'b@example.com']],
    'semicolon' => ['a@example.com;b@example.com', ['a@example.com', 'b@example.com']],
    'mixed with spaces' => ['  a@example.com , b@example.com;c@example.com  ', ['a@example.com', 'b@example.com', 'c@example.com']],
    'trailing separator' => ['a@example.com, b@example.com;', ['a@example.com', 'b@example.com']],
    'empty entries' => ['a@example.com,, ;b@example.com', ['a@example.com', 'b@example.com']],
    'only separators' => [' , ; ', []],
    'empty' => ['', []],
    'null' => [null, []],
]);

it('accepts a list of valid addresses', function () {
    $validator = Validator::make(['to' => 'a@example.com; b@example.com'], ['to' => [new EmailAddressList]]);

    expect($validator->passes())->toBeTrue();
});

it('names the first invalid address', function () {
    $validator = Validator::make(['to' => 'a@example.com, not-an-email, also bad'], ['to' => [new EmailAddressList]]);

    expect($validator->errors()->get('to'))->toBe(["not-an-email isn't a valid email address"]);
});

it('treats a field of only separators as empty', function () {
    $validator = Validator::make(['to' => ' , ; '], ['to' => [new EmailAddressList]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('to'))->toContain('required');
});
