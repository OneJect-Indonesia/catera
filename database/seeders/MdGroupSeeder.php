<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MdGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('catera.md_groups')->insertOrIgnore([
            ['nama_group' => 'merah', 'short_description' => 'Group Merah', 'created_at' => now(), 'updated_at' => now()],
            ['nama_group' => 'biru', 'short_description' => 'Group Biru', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
