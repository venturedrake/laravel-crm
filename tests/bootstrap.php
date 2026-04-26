<?php

use VentureDrake\LaravelCrm\Tests\Stubs\User;

require __DIR__.'/../vendor/autoload.php';

// Alias App\User once so models that reference it can resolve. We deliberately
// do NOT alias App\Models\User: the package service provider checks for that
// class to decide whether to call class_alias() itself, and a second boot in
// the same PHP process would then fail with a "cannot redeclare" fatal error.
if (! class_exists('App\\User', false)) {
    class_alias(User::class, 'App\\User');
}
