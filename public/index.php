<?php

/**
 * Front controller: public entrypoint.
 *
 * Bootstraps the application and handles incoming HTTP requests for the
 * public web entrypoint.
 *
 * PHP 8.1+
 *
 * @package   Public
 */

/**
 * Front controller (public entrypoint).
 *
 * Boots the application and serves incoming HTTP requests.
 *
 * PHP 8.1+
 *
 * @package   Public
 */

/**
 * Front controller for HTTP requests.
 *
 * Boots the application and forwards the current request to the router.
 *
 * PHP 8.1+
 *
 * @package   Public
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
