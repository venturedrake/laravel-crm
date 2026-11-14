<?php

use Illuminate\Http\Request;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(dirname(__DIR__, 2).'/.context/browser-host/public'.$path)) {
    return false;
}
$app = require __DIR__.'/bootstrap.php';
$app->handleRequest(Request::capture());
