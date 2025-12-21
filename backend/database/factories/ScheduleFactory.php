<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::instance(fake()->dateTimeBetween('now', '+1 month'));

        return [
            'title' => fake()->sentence(4),
            'date' => $date->toDateString(),
            'time' => $date->format('H:i:s'),
            'description' => fake()->sentence(8),
        ];
    }
}
