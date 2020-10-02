<?php

namespace VentureDrake\LaravelCrm\Tests;

use Orchestra\Testbench\TestCase;
use VentureDrake\LaravelCrm\LaravelCrmFacade;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;

class RouteTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelCrmServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaravelCrm' => LaravelCrmFacade::class
        ];
    }
    
    /** @test */
    public function the_crm_route_can_be_accessed()
    {
        $this->get('/crm')
            ->assertStatus(200);
    }
}
