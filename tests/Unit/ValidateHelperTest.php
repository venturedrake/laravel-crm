<?php

use function VentureDrake\LaravelCrm\Http\Helpers\Validate\validEmail;
use function VentureDrake\LaravelCrm\Http\Helpers\Validate\validPhone;
use function VentureDrake\LaravelCrm\Http\Helpers\Validate\validUrl;

// validEmail

test('valid email passes', function () {
    expect(validEmail('user@example.com'))->toBeTrue();
});

test('invalid email fails', function () {
    expect(validEmail('not-an-email'))->toBeFalse();
    expect(validEmail('missing@tld'))->toBeFalse();
    expect(validEmail(''))->toBeFalse();
    expect(validEmail(null))->toBeFalse();
});

test('email with subdomain passes', function () {
    expect(validEmail('user@mail.example.co.uk'))->toBeTrue();
});

// validPhone

test('valid phone with plus prefix passes', function () {
    expect(validPhone('+61412345678'))->toBeTrue();
});

test('valid phone digits only passes', function () {
    expect(validPhone('0412345678'))->toBeTrue();
});

test('invalid phone fails', function () {
    expect(validPhone('not-a-phone'))->toBeFalse();
    expect(validPhone(''))->toBeFalse();
    expect(validPhone(null))->toBeFalse();
});

// validUrl

test('valid http url passes', function () {
    expect(validUrl('http://example.com'))->toBeTrue();
});

test('valid https url passes', function () {
    expect(validUrl('https://example.com/path?q=1'))->toBeTrue();
});

test('invalid url fails', function () {
    expect(validUrl('not a url'))->toBeFalse();
    expect(validUrl(''))->toBeFalse();
    expect(validUrl(null))->toBeFalse();
});
