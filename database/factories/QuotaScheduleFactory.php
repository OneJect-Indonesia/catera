<?php

namespace Database\Factories;

use App\Models\Authorized;
use App\Models\QuotaSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotaScheduleFactory extends Factory
{
    protected $model = QuotaSchedule::class;

    public function definition(): array
    {
        return [
            'authorized_uuid' => Authorized::factory(),
            'add_quota' => fake()->numberBetween(1, 10),
            'target_date' => now()->toDateString(),
            'status' => 'pending',
        ];
    }
}
