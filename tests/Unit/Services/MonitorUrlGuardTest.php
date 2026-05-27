<?php

use VentureDrake\LaravelCrm\Services\MonitorUrlGuard;

beforeEach(function () {
    config()->set('laravel-crm.monitoring.allow_private_targets', false);
});

it('rejects empty urls', function () {
    expect(MonitorUrlGuard::isAllowed(null))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed(''))->toBeFalse();
});

it('rejects non-http schemes', function () {
    expect(MonitorUrlGuard::isAllowed('file:///etc/passwd'))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed('gopher://example.com'))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed('javascript:alert(1)'))->toBeFalse();
});

it('rejects loopback and private literal IPs', function () {
    expect(MonitorUrlGuard::isAllowed('http://127.0.0.1/'))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed('http://10.0.0.1/'))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed('http://192.168.0.1/'))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed('http://169.254.169.254/latest/meta-data/'))->toBeFalse();
    expect(MonitorUrlGuard::isAllowed('http://[::1]/'))->toBeFalse();
});

it('allows routable public IPs', function () {
    expect(MonitorUrlGuard::isAllowed('http://8.8.8.8/'))->toBeTrue();
    expect(MonitorUrlGuard::isAllowed('https://1.1.1.1/'))->toBeTrue();
});

it('honors the opt-out flag', function () {
    config()->set('laravel-crm.monitoring.allow_private_targets', true);

    expect(MonitorUrlGuard::isAllowed('http://127.0.0.1/'))->toBeTrue();
});
