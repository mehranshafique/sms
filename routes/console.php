<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:notify-absent')->dailyAt('16:00');
Schedule::command('attendance:send-weekly-reports')->weeklyOn(5, '18:00');
Schedule::command('attendance:send-monthly-reports')->monthlyOn(1, '08:00');
Schedule::command('derogations:process-compliance')->dailyAt('07:30');
