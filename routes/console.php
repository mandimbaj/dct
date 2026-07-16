<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('indicators:archive-approved')
    ->cron('40 9 */3 * *')
    ->withoutOverlapping();

Schedule::command('users:sync-warehouse')
    ->dailyAt('02:15')
    ->withoutOverlapping();
