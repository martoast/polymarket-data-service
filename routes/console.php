<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recorder runs as a persistent supervisor process (recorder:start) — no schedule needed here.

// Backfill outcomes for windows that expired since last run (every 5 minutes)
Schedule::command('recorder:backfill-windows')->everyFiveMinutes()->withoutOverlapping();
