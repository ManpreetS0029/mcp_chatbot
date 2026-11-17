<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_view_dashboard_and_appointments(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $slot = TimeSlot::create([
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'booking_reference' => 'APT-ADMINTEST1',
            'customer_name' => 'Diana Prince',
            'customer_email' => 'diana@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('APT-ADMINTEST1');
        $response->assertSee('Diana Prince');
    }

    public function test_admin_can_filter_appointments_by_status(): void
    {
        $admin = User::factory()->create();

        $slot = TimeSlot::create([
            'day_of_week' => 'monday',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $confirmed = Appointment::create([
            'booking_reference' => 'APT-CONFIRMED',
            'customer_name' => 'Active Customer',
            'customer_email' => 'active@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $cancelled = Appointment::create([
            'booking_reference' => 'APT-CANCELLED',
            'customer_name' => 'Cancelled Customer',
            'customer_email' => 'cancelled@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($admin)->get('/admin?status=cancelled');

        $response->assertStatus(200);
        $response->assertSee('APT-CANCELLED');
        $response->assertDontSee('APT-CONFIRMED');
    }

    public function test_admin_can_update_appointment_status_and_notes(): void
    {
        $admin = User::factory()->create();

        $slot = TimeSlot::create([
            'day_of_week' => 'tuesday',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'booking_reference' => 'APT-STATUSUPD',
            'customer_name' => 'Edward Cullen',
            'customer_email' => 'edward@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->patch("/admin/appointments/{$appointment->id}/status", [
            'status' => Appointment::STATUS_COMPLETED,
            'admin_notes' => 'Customer attended and completed consultation.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => Appointment::STATUS_COMPLETED,
            'admin_notes' => 'Customer attended and completed consultation.',
        ]);
    }

    public function test_admin_can_delete_appointment(): void
    {
        $admin = User::factory()->create();

        $slot = TimeSlot::create([
            'day_of_week' => 'wednesday',
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'booking_reference' => 'APT-DELETE1',
            'customer_name' => 'Fiona Gallagher',
            'customer_email' => 'fiona@example.com',
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => Appointment::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/appointments/{$appointment->id}");

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
        ]);
    }
}
