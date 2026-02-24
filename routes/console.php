<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// * daily quota reset scheduler
Artisan::command('app:quota-daily-reset', function () {
    $this->info('Start daily reset system');

    DB::table('authorizeds')->update([
        'quota' => 1,
    ]);

    $this->info('Daily reset system completed');
})->purpose('Reset quota daily')->dailyAt('00:00')->timezone('Asia/Jakarta');

// * automated add quota scheduler
Artisan::command('app:process-scheduled-quota', function () {
    $this->info('Start processing scheduled add quota');

    $registereds = DB::table('registereds')
        ->where('status', 'pending')
        ->whereNotNull('target_date')
        ->where('target_date', '<=', \Carbon\Carbon::today()->toDateString())
        ->get();

    foreach ($registereds as $registered) {
        $addQuota = $registered->add_quota;
        if ($addQuota > 0) {
            DB::transaction(function () use ($registered, $addQuota) {
                DB::table('authorizeds')
                    ->where('uuid', $registered->authorized_uuid)
                    ->increment('quota', $addQuota);

                DB::table('registereds')
                    ->where('id', $registered->id)
                    ->update(['status' => 'success']);
            });
        }
    }

    $this->info('Scheduled add quota completed');
})->purpose('Process scheduled additional quota')->dailyAt('00:02')->timezone('Asia/Jakarta');
