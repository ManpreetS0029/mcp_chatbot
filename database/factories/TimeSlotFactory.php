<?php

namespace Database\Factories;

use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->randomElement(WorkingDay::DAYS_OF_WEEK),
            'specific_date' => null,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'capacity' => 1,
            'is_active' => true,
        ];
    }
}
