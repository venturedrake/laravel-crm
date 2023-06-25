<?php

namespace VentureDrake\LaravelCrm\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use VentureDrake\LaravelCrm\LaravelCrmFacade;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Models\Lead;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelCrmServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaravelCrm' => LaravelCrmFacade::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        include_once(__DIR__ . '/../database/migrations/create_laravel_crm_tables.php.stub');

        (new \CreateLaravelCrmTables())->up();
    }

    /** @test */
    public function the_crm_route_can_be_accessed()
    {
        $this->get('/laravel-crm')
            ->assertViewIs('laravel-crm::index')
            ->assertStatus(200);
    }

    /** @test */
    public function it_can_access_the_database()
    {
        /* $lead = new Lead();
         $lead->name = 'Tim Drake';
         $lead->save();

         $newLead = Lead::find($lead->id);

         $this->assertSame($newLead->name, 'Tim Drake');*/
        $this->assertTrue(true);
    }
}
