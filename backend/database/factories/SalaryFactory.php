<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Salary>
 */
class SalaryFactory extends Factory
{
    public function definition(): array
    {
        $base = fake()->numberBetween(12000000, 20000000);
        $bonus = fake()->numberBetween(0, 3000000);

        return [
            'employee_id' => Employee::factory(),
            'base_salary' => $base,
            'bonus' => $bonus,
            'total' => $base + $bonus,
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2024, 2025),
        ];
    }
}
