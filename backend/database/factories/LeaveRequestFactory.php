<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::instance(fake()->dateTimeBetween('+1 days', '+1 month'));
        $end = (clone $start)->addDays(fake()->numberBetween(1, 5));

        return [
            'employee_id' => Employee::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'reason' => fake()->sentence(8),
            'status' => fake()->randomElement(['Chờ duyệt', 'Đã duyệt', 'Từ chối']),
            'type' => fake()->randomElement(['Nghỉ phép năm', 'Việc gia đình', 'Khám bệnh']),
        ];
    }
}
