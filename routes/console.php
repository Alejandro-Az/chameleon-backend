<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Security Center / Ops Heartbeat
Schedule::call(function () {
    Cache::put('kaan:scheduler:last_run', now(), now()->addMinutes(15));
})->everyMinute()->name('kaan_heartbeat')->withoutOverlapping()->onOneServer();
