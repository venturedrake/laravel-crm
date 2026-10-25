<?php

namespace VentureDrake\LaravelCrm\Tests;

use Illuminate\Filesystem\Filesystem;
use VentureDrake\LaravelCrm\Tests\Stubs\V1Schema;

/**
 * Test case that boots a v1-shaped database schema instead of the standard TestSchema.
 * Used exclusively by the V1ToV2UpgradeTest.
 *
 * The database and public paths are a throwaway directory. The nested
 * laravelcrm:update publishes every migration stub into database/migrations
 * and then migrates it, so testbench's own directory — and whatever earlier
 * runs left there — must not be in play. Like ScratchPublicPathTestCase, the
 * paths are set before the service provider boots, because the provider
 * resolves the published migration names while registering its publish map.
 */
abstract class V1TestCase extends TestCase
{
    protected string $scratchDatabasePath;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // One path per process, not per test: most published stubs declare a
        // named class, and the migrator only reuses an already-declared class
        // when it comes from the same file. A fresh path per test would
        // redeclare it and fatal.
        $this->scratchDatabasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'crm-v1-database-'.getmypid();

        (new Filesystem)->ensureDirectoryExists($this->scratchDatabasePath.DIRECTORY_SEPARATOR.'migrations');

        $app->useDatabasePath($this->scratchDatabasePath);

        // laravelcrm:update also republishes the front-end assets.
        $app->usePublicPath($this->scratchDatabasePath.DIRECTORY_SEPARATOR.'public');
    }

    protected function defineDatabaseMigrations(): void
    {
        V1Schema::up();
    }

    protected function tearDown(): void
    {
        if (isset($this->scratchDatabasePath) && is_dir($this->scratchDatabasePath)) {
            (new Filesystem)->deleteDirectory($this->scratchDatabasePath);
        }

        parent::tearDown();
    }
}
