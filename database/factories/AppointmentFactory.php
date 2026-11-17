<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_reference' => Appointment::generateBookingReference(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'appointment_date' => fake()->dateTimeBetween('now', '+14 days')->format('Y-m-d'),
            'time_slot_id' => TimeSlot::factory(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'notes' => fake()->sentence(),
            'admin_notes' => null,
        ];
    }
}
