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
            // Since we deleted is_active from authorizeds, we join with portal_application.md_users to get the user status
            $activeUserIds = DB::table('portal_application.md_users')
                ->where('status', 'active')
                ->pluck('id');

            DB::table('catera.authorizeds')
                ->whereIn('user_id', $activeUserIds)
                ->update([
                    'quota' => 1,
                    'updated_at' => now(),
                ]);

            DB::table('catera.authorizeds')
                ->whereNotIn('user_id', $activeUserIds)
                ->update([
                    'quota' => 0,
                    'updated_at' => now(),
                ]);
        });

        $this->info('Daily reset system completed');
    }
}
