<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-detect if we are running in cPanel (public_html) or locally (public)
$basePath = __DIR__.'/..';
if (file_exists(__DIR__.'/../daser_rest/bootstrap/app.php')) {
    $basePath = __DIR__.'/../daser_rest';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once $basePath.'/bootstrap/app.php';

// If we are in cPanel, bind the public path to __DIR__ (which is public_html)
if ($basePath !== __DIR__.'/..') {
    $app->usePublicPath(__DIR__);
}

$app->handleRequest(Request::capture());
