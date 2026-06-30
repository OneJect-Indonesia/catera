<?php

use App\Console\Commands\ClearSuccessQuotaSchedules;
use App\Console\Commands\ResetQuota;
use App\Console\Commands\ScheduleAddQuota;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// * daily quota reset scheduler
Schedule::command(ResetQuota::class)->dailyAt('00:00')->timezone('Asia/Jakarta')->withoutOverlapping();
// Schedule::command(ResetQuota::class)->dailyAt('10:00')->timezone('Asia/Jakarta')->withoutOverlapping();

// * automated add quota scheduler
Schedule::command(ScheduleAddQuota::class)->dailyAt('00:03')->timezone('Asia/Jakarta')->withoutOverlapping();
// Schedule::command(ScheduleAddQuota::class)->dailyAt('09:50')->timezone('Asia/Jakarta')->withoutOverlapping();

// * monthly quota schedules clearance for succes status
Schedule::command(ClearSuccessQuotaSchedules::class)->monthlyOn(1, '00:06')->timezone('Asia/Jakarta')->withoutOverlapping();
// Schedule::command(ClearSuccessQuotaSchedules::class)->dailyAt('10:10')->timezone('Asia/Jakarta');
