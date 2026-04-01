<?php

use App\Console\Commands\ClearSuccessRegistereds;
use App\Console\Commands\DeletUnauthorizeds;
use App\Console\Commands\ResetQuota;
use App\Console\Commands\ScheduleAddQuota;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// * daily quota reset scheduler
Schedule::command(ResetQuota::class)->dailyAt('00:00')->timezone('Asia/Jakarta');

// * monthly unauthorized clearance scheduler
Schedule::command(DeletUnauthorizeds::class)->monthlyOn(1, '00:04')->timezone('Asia/Jakarta');

// * automated add quota scheduler
Schedule::command(ScheduleAddQuota::class)->dailyAt('00:02')->timezone('Asia/Jakarta');

// * monthly registereds clearance for succes status
Schedule::command(ClearSuccessRegistereds::class)->monthlyOn(1, '00:06')->timezone('Asia/Jakarta');
