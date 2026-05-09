<?php

namespace VentureDrake\LaravelCrm\Tests;

use VentureDrake\LaravelCrm\Tests\Stubs\V1Schema;

/**
 * Test case that boots a v1-shaped database schema instead of the standard TestSchema.
 * Used exclusively by the V1ToV2UpgradeTest.
 */
abstract class V1TestCase extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        V1Schema::up();
    }
}
