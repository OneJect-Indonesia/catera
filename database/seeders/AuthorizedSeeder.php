<?php

namespace Database\Seeders;

use App\Models\Authorized;
use Illuminate\Database\Seeder;

class AuthorizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Authorized::factory(50)->create();
    }
}
