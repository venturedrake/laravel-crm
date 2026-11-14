<?php

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Cknow\Money\MoneyServiceProvider;
use Codeat3\BladeForkAwesome\BladeForkAwesomeServiceProvider;
use Flasher\Laravel\FlasherServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use MallardDuck\BladeBoxicons\BladeBoxiconsServiceProvider;
use Mary\MaryServiceProvider;
use OwenVoke\BladeFontAwesome\BladeFontAwesomeServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use VentureDrake\LaravelCrm\LaravelCrmServiceProvider;
use VentureDrake\LaravelCrm\Livewire\ModelProducts;
use VentureDrake\LaravelCrm\Tests\Stubs\User;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$root = dirname(__DIR__, 2);
$loader = require (getenv('CRM_BROWSER_VENDOR') ?: $root.'/vendor').'/autoload.php';
$loader->setPsr4('VentureDrake\\LaravelCrm\\', [$root.'/src']);
$loader->setPsr4('VentureDrake\\LaravelCrm\\Tests\\', [$root.'/tests']);
// Refuse optimized autoload maps that still point at another checkout.
if ((new ReflectionClass(ModelProducts::class))->getFileName() !== $root.'/src/Livewire/ModelProducts.php') {
    throw new RuntimeException('Browser dependencies must load ModelProducts from this worktree. Use install-matrix.php.');
}
require_once __DIR__.'/FixtureProvider.php';
class_alias(User::class, 'App\\Models\\User');

return Application::configure(basePath: $root.'/.context/browser-host')
    ->withRouting(using: function () {})
    ->withProviders([
        PermissionServiceProvider::class,
        LivewireServiceProvider::class,
        BladeIconsServiceProvider::class,
        BladeHeroiconsServiceProvider::class,
        BladeForkAwesomeServiceProvider::class,
        BladeBoxiconsServiceProvider::class,
        BladeFontAwesomeServiceProvider::class,
        MaryServiceProvider::class,
        FlasherServiceProvider::class,
        MoneyServiceProvider::class,
        FixtureProvider::class,
        LaravelCrmServiceProvider::class,
    ])
    ->withMiddleware(fn ($middleware) => $middleware->validateCsrfTokens(except: ['fixture/*']))
    ->withExceptions(function ($exceptions) {})
    ->create();
