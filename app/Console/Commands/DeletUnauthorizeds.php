<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeletUnauthorizeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delet-unauthorizeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clearance unauthorizeds uid data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start clearance unauthorizeds uid data');

        DB::transaction(function () {
            DB::table('unauthorizeds')->truncate();
        });

        $this->info('Clearance unauthorizeds uid data completed');
    }
}
