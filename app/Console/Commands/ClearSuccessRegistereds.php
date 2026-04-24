<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearSuccessRegistereds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-success-registereds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clearance registereds success status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start clearance registereds success status');

        $twoMonthsAgo = Carbon::now()->subMonths(2);

        DB::transaction(function () use ($twoMonthsAgo) {
            DB::table('registereds')->where('status', 'success')->where('updated_at', '<=', $twoMonthsAgo)->delete();
        });

        $this->info('Clearance registereds success status completed');
    }
}
