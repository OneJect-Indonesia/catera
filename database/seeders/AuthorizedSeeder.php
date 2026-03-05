<?php

namespace Database\Seeders;

use App\Models\Authorized;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Authorized::factory(2000)->create();
    }
}
