<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\LaravelCrm;
use VentureDrake\LaravelCrm\Tests\TestCase;

class FacadeTest extends TestCase
{
    public function test_facade_resolves_underlying_class(): void
    {
        $this->assertInstanceOf(LaravelCrm::class, \LaravelCrm::getFacadeRoot());
    }

    public function test_facade_methods_exist(): void
    {
        $crm = app('laravel-crm');

        $this->assertTrue(method_exists($crm, 'searchLeads'));
        $this->assertTrue(method_exists($crm, 'getLeads'));
        $this->assertTrue(method_exists($crm, 'getLead'));
    }
}
