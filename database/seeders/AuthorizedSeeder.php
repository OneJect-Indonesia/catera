<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorizedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 100; $i++) {
            \App\Models\Authorized::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'group' => $i % 2 === 0 ? 'merah' : 'biru',
                'quota' => rand(10, 100),
                'is_active' => $i % 3 !== 0,
            ]);
        }
    }
}
