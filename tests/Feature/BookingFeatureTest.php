<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (WorkingDay::DAYS_OF_WEEK as $day) {
            WorkingDay::create([
                'day_of_week' => $day,
                'is_open' => ! in_array($day, ['sunday'], true),
            ]);
        }
    }

    public function test_customer_can_view_booking_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Book Your Appointment');
    }

    public function test_customer_can_fetch_available_slots_for_open_day(): void
    {
        $nextMonday = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');

        $slot = TimeSlot::create([
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/available-slots?date='.$nextMonday);

        $response->assertStatus(200);
        $response->assertJson([
            'is_open' => true,
        ]);
        $response->assertJsonFragment([
            'id' => $slot->id,
            'is_available' => true,
        ]);
    }

    public function test_closed_day_returns_closed_status(): void
    {
        $nextSunday = Carbon::now()->next(Carbon::SUNDAY)->format('Y-m-d');

        $response = $this->getJson('/api/available-slots?date='.$nextSunday);

        $response->assertStatus(200);
        $response->assertJson([
            'is_open' => false,
        ]);
    }

    public function test_customer_can_successfully_book_appointment(): void
    {
        $targetDate = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

        $slot = TimeSlot::create([
            'day_of_week' => 'tuesday',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $response = $this->post('/book', [
            'customer_name' => 'Alice Johnson',
            'customer_email' => 'alice@example.com',
            'customer_phone' => '1234567890',
            'appointment_date' => $targetDate,
            'time_slot_id' => $slot->id,
            'notes' => 'Product discussion',
        ]);

        $appointment = Appointment::where('customer_email', 'alice@example.com')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Alice Johnson', $appointment->customer_name);
        $this->assertEquals($targetDate, $appointment->appointment_date->format('Y-m-d'));
        $this->assertEquals($slot->id, $appointment->time_slot_id);
        $this->assertEquals(Appointment::STATUS_CONFIRMED, $appointment->status);

        $response->assertRedirect(route('booking.confirmation', $appointment->booking_reference));
    }

    public function test_cannot_book_when_slot_is_at_full_capacity(): void
    {
        $targetDate = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        $slot = TimeSlot::create([
            'day_of_week' => 'wednesday',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        // First customer books
        Appointment::create([
            'booking_reference' => 'APT-EXISTING1',
            'customer_name' => 'First Customer',
            'customer_email' => 'first@example.com',
            'appointment_date' => $targetDate,
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        // Second customer tries to book same slot on same date
        $response = $this->post('/book', [
            'customer_name' => 'Second Customer',
            'customer_email' => 'second@example.com',
            'appointment_date' => $targetDate,
            'time_slot_id' => $slot->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Appointment::where('time_slot_id', $slot->id)->count());
    }

    public function test_customer_can_view_confirmation_page(): void
    {
        $slot = TimeSlot::create([
            'day_of_week' => 'thursday',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'booking_reference' => 'APT-CONFIRM123',
            'customer_name' => 'Bob Marley',
            'customer_email' => 'bob@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->get(route('booking.confirmation', $appointment->booking_reference));

        $response->assertStatus(200);
        $response->assertSee('APT-CONFIRM123');
        $response->assertSee('Bob Marley');
    }

    public function test_customer_can_lookup_appointment_by_reference(): void
    {
        $slot = TimeSlot::create([
            'day_of_week' => 'friday',
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'booking_reference' => 'APT-SEARCH999',
            'customer_name' => 'Charlie Brown',
            'customer_email' => 'charlie@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->get('/booking/lookup?reference=APT-SEARCH999');

        $response->assertStatus(200);
        $response->assertSee('Charlie Brown');
        $response->assertSee('APT-SEARCH999');
    }
}
