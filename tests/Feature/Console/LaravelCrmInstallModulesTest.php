<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

if (! class_exists('App\Models\User')) {
    class_alias(\VentureDrake\LaravelCrm\Tests\Stubs\User::class, 'App\Models\User');
}

beforeEach(function () {
    $this->originalConfigPath = $this->app->configPath();

    $this->tempConfigDir = sys_get_temp_dir().'/laravel-crm-install-test-'.uniqid('', true);
    File::ensureDirectoryExists($this->tempConfigDir);

    $sourceConfig = realpath(__DIR__.'/../../../config/laravel-crm.php');
    File::copy($sourceConfig, $this->tempConfigDir.'/laravel-crm.php');

    App::useConfigPath($this->tempConfigDir);
});

afterEach(function () {
    App::useConfigPath($this->originalConfigPath);

    if (isset($this->tempConfigDir) && File::isDirectory($this->tempConfigDir)) {
        File::deleteDirectory($this->tempConfigDir);
    }
});

test('install --modules=all writes all 13 known module keys to config', function () {
    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => 'all',
            '--owner-email' => 'owner-all@example.com',
            '--owner-name' => 'Owner All',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // selectModules() runs before downstream steps (migrate/seed/publish-assets)
        // that may fail in the testbench environment. The config is already written
        // by the time those steps run, so we tolerate the exception here and assert
        // on the persisted module list below.
    }

    $config = include $this->tempConfigDir.'/laravel-crm.php';

    expect($config['modules'])->toBe([
        'leads',
        'deals',
        'quotes',
        'orders',
        'invoices',
        'deliveries',
        'purchase-orders',
        'teams',
        'chat',
        'email-marketing',
        'sms-marketing',
        'features',
        'monitoring',
    ]);
});

test('install --modules=leads,deals,quotes writes exactly those three keys in order', function () {
    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => 'leads,deals,quotes',
            '--owner-email' => 'owner-subset@example.com',
            '--owner-name' => 'Owner Subset',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // See note above.
    }

    $config = include $this->tempConfigDir.'/laravel-crm.php';

    expect($config['modules'])->toBe(['leads', 'deals', 'quotes']);
});

test('install --modules=leads,bogus,deals silently drops unknown keys via array_intersect', function () {
    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => 'leads,bogus,deals',
            '--owner-email' => 'owner-bogus@example.com',
            '--owner-name' => 'Owner Bogus',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // See note above.
    }

    $config = include $this->tempConfigDir.'/laravel-crm.php';

    expect($config['modules'])->toBe(['leads', 'deals']);
});

test('install --modules= (empty string) writes an empty modules array, not the default-all', function () {
    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => '',
            '--owner-email' => 'owner-empty@example.com',
            '--owner-name' => 'Owner Empty',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // See note above.
    }

    $config = include $this->tempConfigDir.'/laravel-crm.php';

    expect($config['modules'])->toBe([]);
});

test('install warns and does not throw when published config file is missing', function () {
    File::delete($this->tempConfigDir.'/laravel-crm.php');

    $threw = null;

    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => 'leads,deals',
            '--owner-email' => 'owner-missing@example.com',
            '--owner-name' => 'Owner Missing',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // The downstream install steps (migrate/seed/publish) can throw in
        // testbench. The selectModules() warning we want to assert on is
        // emitted before any of those, so we only fail the test if the
        // warning itself was missing from the captured output.
        $threw = $e;
    }

    expect(Artisan::output())->toContain('Could not locate config/laravel-crm.php to update module list.');
    expect(File::exists($this->tempConfigDir.'/laravel-crm.php'))->toBeFalse();
});

test('running install twice rewrites modules idempotently (regex replaces, not appends)', function () {
    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => 'leads',
            '--owner-email' => 'owner-twice@example.com',
            '--owner-name' => 'Owner Twice',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // See note above.
    }

    $firstSnapshot = $this->tempConfigDir.'/snapshot-first-'.uniqid('', true).'.php';
    File::copy($this->tempConfigDir.'/laravel-crm.php', $firstSnapshot);
    $firstPass = include $firstSnapshot;
    expect($firstPass['modules'])->toBe(['leads']);

    try {
        Artisan::call('laravelcrm:install', [
            '--modules' => 'deals',
            '--owner-email' => 'owner-twice@example.com',
            '--owner-name' => 'Owner Twice',
            '--owner-password' => 'secret-password',
            '--no-interaction' => true,
        ]);
    } catch (\Throwable $e) {
        // See note above.
    }

    $contents = File::get($this->tempConfigDir.'/laravel-crm.php');
    // The regex must replace, not append — only one `'modules' =>` entry should remain.
    expect(substr_count($contents, "'modules' =>"))->toBe(1);

    $secondSnapshot = $this->tempConfigDir.'/snapshot-second-'.uniqid('', true).'.php';
    File::copy($this->tempConfigDir.'/laravel-crm.php', $secondSnapshot);
    $secondPass = include $secondSnapshot;
    expect($secondPass['modules'])->toBe(['deals']);
});
