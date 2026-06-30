<?php

namespace App\Console\Commands;

use App\Models\QuotaSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleAddQuota extends Command
{
    protected $signature = 'app:schedule-add-quota';

    protected $description = 'process scheduled add quota for selected user';

    public function handle(): int
    {
        $this->info('Start processing scheduled add quota for selected user');

        $processed = 0;
        $failed = 0;

        QuotaSchedule::query()
            ->where('status', 'pending')
            ->whereNotNull('target_date')
            ->where('target_date', '<=', now()->toDateString())
            ->with('authorized')
            ->lazy()
            ->each(function (QuotaSchedule $schedule) use (&$processed, &$failed) {
                try {
                    if (! $schedule->authorized || ! $schedule->authorized->is_active) {
                        $schedule->update(['status' => 'failed']);

                        return;
                    }

                    $schedule->authorized->increment('quota', $schedule->add_quota);
                    $schedule->update(['status' => 'success']);

                    $processed++;
                } catch (\Exception $e) {
                    $schedule->update(['status' => 'failed']);

                    Log::error('Failed to process scheduled add quota', [
                        'quota_schedule_id' => $schedule->id,
                        'error' => $e->getMessage(),
                    ]);

                    $failed++;
                }
            });

        $this->info("Scheduled add quota completed. Processed: {$processed}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
