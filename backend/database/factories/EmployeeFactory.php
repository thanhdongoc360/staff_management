<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_code' => 'EMP-' . Str::upper(Str::random(5)),
            'position' => fake()->randomElement(['Senior Developer', 'HR Manager', 'Designer', 'Marketing Lead', 'Accountant']),
            'department' => fake()->randomElement(['IT', 'Nhân sự', 'Design', 'Marketing', 'Kế toán']),
            'phone' => fake()->phoneNumber(),
            'status' => 'Đang làm việc',
        ];
    }
}
