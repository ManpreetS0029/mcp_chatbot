<?php

namespace Database\Factories;

use App\Models\WorkingDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingDay>
 */
class WorkingDayFactory extends Factory
{
    protected $model = WorkingDay::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->randomElement(WorkingDay::DAYS_OF_WEEK),
            'is_open' => true,
        ];
    }
}
