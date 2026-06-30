<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearSuccessQuotaSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-success-quota-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clearance quota schedules success status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start clearance quota schedules success status');

        $twoMonthsAgo = Carbon::now()->subMonths(2);

        DB::transaction(function () use ($twoMonthsAgo) {
            DB::table('quota_schedules')->where('status', 'success')->where('updated_at', '<=', $twoMonthsAgo)->delete();
        });

        $this->info('Clearance quota schedules success status completed');
    }
}
