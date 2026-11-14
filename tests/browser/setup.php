<?php

use Illuminate\Contracts\Console\Kernel;
use VentureDrake\LaravelCrm\Tests\TestSchema;

$root = dirname(__DIR__, 2);
$host = $root.'/.context/browser-host';
foreach (['bootstrap/cache', 'database', 'storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache', 'storage/logs'] as $directory) {
    if (! is_dir($host.'/'.$directory)) {
        mkdir($host.'/'.$directory, 0777, true);
    }
}
file_put_contents($host.'/composer.json', '{}');
file_put_contents($host.'/database/database.sqlite', '');
if (! is_dir($host.'/public/vendor')) {
    mkdir($host.'/public/vendor', 0777, true);
}
if (! is_link($host.'/public/vendor/laravel-crm')) {
    symlink($root.'/public/vendor/laravel-crm', $host.'/public/vendor/laravel-crm');
}
$app = require __DIR__.'/bootstrap.php';
$app->make(Kernel::class)->bootstrap();
TestSchema::up();
