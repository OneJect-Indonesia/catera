<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnauthorizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Unauthorized::factory(50)->create();
    }
}
