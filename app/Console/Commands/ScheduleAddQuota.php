<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScheduleAddQuota extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:schedule-add-quota';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'process scheduled add quota for selected user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start processing scheduled add quota for selected user');

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
                        ->update([
                            'status' => 'success',
                            'updated_at' => now()
                        ]);
                });
            }
        }

        $this->info('Scheduled add quota for selected user completed');
    }
}
