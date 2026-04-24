<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Authorized>
 */
class AuthorizedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => DB::table('portal_application.users')->inRandomOrder()->value('id'),
            'uuid' => Str::uuid(),
            'group' => fake()->randomElement(['merah', 'biru']),
            'quota' => rand(0, 1),
            'is_active' => fake()->randomElement([true, false]),
        ];
    }
}
