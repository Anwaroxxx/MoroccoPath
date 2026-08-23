<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automated source verification (spec §18): VERIFIED citations expire
// after the staleness window so stale facts can never pose as current.
Schedule::command('sources:reverify')->dailyAt('03:00');
