<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use VentureDrake\LaravelCrm\Tests\TestCase;

class ArtisanCommandsTest extends TestCase
{
    public function test_archive_command_runs_without_errors(): void
    {
        $this->artisan('laravelcrm:archive')->assertExitCode(0);
    }

    public function test_reminders_command_is_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $this->assertArrayHasKey('laravelcrm:reminders', $kernel->all());
    }

    public function test_install_command_is_callable(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $this->assertArrayHasKey('laravelcrm:install', $kernel->all());
    }

    public function test_xero_command_is_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $this->assertArrayHasKey('laravelcrm:xero', $kernel->all());
    }

    public function test_v2_migration_command_is_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $this->assertArrayHasKey('laravelcrm:v2', $kernel->all());
    }

    public function test_update_command_is_registered(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $this->assertArrayHasKey('laravelcrm:update', $kernel->all());
    }
}
