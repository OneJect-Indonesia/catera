<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetQuota extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-quota';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reset quota daily each user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start daily reset system');

        DB::transaction(function () {
            DB::table('authorizeds')->update([
                'quota' => DB::raw("CASE
                    WHEN is_active = true THEN 1
                    ELSE 0
                END"),
                'updated_at' => now()
            ]);
        });

        $this->info('Daily reset system completed');
    }
}
