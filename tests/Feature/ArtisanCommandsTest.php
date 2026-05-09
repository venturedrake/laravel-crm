<?php

use Illuminate\Contracts\Console\Kernel;

test('archive command runs without errors', function () {
    $this->artisan('laravelcrm:archive')->assertExitCode(0);
});

test('reminders command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:reminders');
});

test('install command is callable', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:install');
});

test('xero command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:xero');
});

test('v2 migration command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:v2');
});

test('update command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:update');
});

test('email campaigns dispatch command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:email-campaigns-dispatch');
});

test('sms campaigns dispatch command is registered', function () {
    expect($this->app->make(Kernel::class)->all())->toHaveKey('laravelcrm:sms-campaigns-dispatch');
});

test('email campaigns dispatch runs without errors', function () {
    $this->artisan('laravelcrm:email-campaigns-dispatch')->assertExitCode(0);
});

test('sms campaigns dispatch runs without errors', function () {
    $this->artisan('laravelcrm:sms-campaigns-dispatch')->assertExitCode(0);
});
