<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recorder runs as a persistent supervisor process (recorder:start) — no schedule needed here.

// Backfill break_value, has_coverage, and outcomes for markets that expired between CLOB events
Schedule::command('recorder:backfill-markets')->everyFiveMinutes()->withoutOverlapping();
