<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class quotaDailyReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:quota-daily-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quota will be reset become 1 every midnight';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Start daily reset system");

        DB::table('authorizeds')->update([
            'quota' => 1,
        ]);

        $this->fail('Something went wrong.');
        $this->info("Daily reset system completed");
    }
}
