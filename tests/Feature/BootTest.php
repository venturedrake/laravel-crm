<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Tests\TestCase;

class BootTest extends TestCase
{
    public function test_application_boots(): void
    {
        $this->assertTrue(true);
        $this->assertNotNull(app('laravel-crm'));
    }
}
