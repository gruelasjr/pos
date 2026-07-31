<?php

/**
 * Console routes loader.
 *
 * Registers Artisan console routes and commands.
 *
 * PHP 8.1+
 *
 * @package   Routes
 */

/**
 * Console routes and commands.
 *
 * Registers console commands for the application.
 *
 * PHP 8.1+
 *
 * @package   Routes
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessOutboxJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessOutboxJob())->everyMinute()->withoutOverlapping();
