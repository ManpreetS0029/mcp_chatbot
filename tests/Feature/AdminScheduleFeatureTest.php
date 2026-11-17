<?php

namespace Tests\Feature;

use App\Models\TimeSlot;
use App\Models\User;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScheduleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (WorkingDay::DAYS_OF_WEEK as $day) {
            WorkingDay::create([
                'day_of_week' => $day,
                'is_open' => true,
            ]);
        }
    }

    public function test_admin_can_view_schedule_management_page(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/schedule');

        $response->assertStatus(200);
        $response->assertSee('Schedule & Availability Management');
        $response->assertSee('Working Days of the Week');
    }

    public function test_admin_can_toggle_working_day_status(): void
    {
        $admin = User::factory()->create();
        $monday = WorkingDay::where('day_of_week', 'monday')->first();
        $this->assertTrue($monday->is_open);

        $response = $this->actingAs($admin)->patch("/admin/schedule/days/{$monday->id}/toggle");

        $response->assertSessionHas('success');
        $this->assertFalse($monday->fresh()->is_open);
    }

    public function test_admin_can_add_recurring_time_slot(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/schedule/slots', [
            'slot_type' => 'recurring',
            'day_of_week' => 'tuesday',
            'start_time' => '13:00',
            'end_time' => '14:00',
            'capacity' => 2,
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');
        $slot = TimeSlot::where('day_of_week', 'tuesday')->first();
        $this->assertNotNull($slot);
        $this->assertEquals(2, $slot->capacity);
        $this->assertTrue($slot->is_active);
        $this->assertStringStartsWith('13:00', $slot->start_time);
        $this->assertStringStartsWith('14:00', $slot->end_time);
    }

    public function test_admin_can_add_specific_date_time_slot(): void
    {
        $admin = User::factory()->create();
        $targetDate = Carbon::now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($admin)->post('/admin/schedule/slots', [
            'slot_type' => 'specific_date',
            'specific_date' => $targetDate,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');
        $slot = TimeSlot::whereNull('day_of_week')->first();
        $this->assertNotNull($slot);
        $this->assertEquals($targetDate, $slot->specific_date->format('Y-m-d'));
        $this->assertStringStartsWith('10:00', $slot->start_time);
    }

    public function test_admin_can_bulk_generate_slots(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/schedule/slots/bulk', [
            'days' => ['thursday', 'friday'],
            'start_time' => '09:00',
            'end_time' => '12:00',
            'duration_minutes' => 60,
            'capacity' => 1,
        ]);

        $response->assertSessionHas('success');

        // 3 slots (09-10, 10-11, 11-12) for Thursday and 3 for Friday = 6 slots
        $this->assertEquals(3, TimeSlot::where('day_of_week', 'thursday')->count());
        $this->assertEquals(3, TimeSlot::where('day_of_week', 'friday')->count());
    }

    public function test_admin_can_toggle_slot_active_status(): void
    {
        $admin = User::factory()->create();

        $slot = TimeSlot::create([
            'day_of_week' => 'wednesday',
            'start_time' => '15:00:00',
            'end_time' => '16:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/schedule/slots/{$slot->id}/toggle");

        $response->assertSessionHas('success');
        $this->assertFalse($slot->fresh()->is_active);
    }

    public function test_admin_can_delete_unused_slot(): void
    {
        $admin = User::factory()->create();

        $slot = TimeSlot::create([
            'day_of_week' => 'saturday',
            'start_time' => '16:00:00',
            'end_time' => '17:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/schedule/slots/{$slot->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('time_slots', [
            'id' => $slot->id,
        ]);
    }
}
