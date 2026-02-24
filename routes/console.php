<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:quota-daily-reset', function () {
    $this->info("Start daily reset system");

    DB::table('authorizeds')->update([
        'quota' => 1,
    ]);

    $this->info("Daily reset system completed");
})->purpose('Reset quota daily')->dailyAt('00:00')->timezone('Asia/Jakarta');
