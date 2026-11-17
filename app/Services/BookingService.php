<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingService
{
    /**
     * Find an appointment by its booking reference code.
     */
    public function findBookingByReference(string $reference): ?Appointment
    {
        $cleanRef = strtoupper(trim($reference));

        if ($cleanRef === '') {
            return null;
        }

        return Appointment::with('timeSlot')
            ->where('booking_reference', $cleanRef)
            ->first();
    }

    /**
     * Resolve a time slot for a given date by slot ID or time string (e.g. "10:00 AM" or "10:00").
     */
    public function resolveTimeSlot(string $date, int|string|null $slotIdOrTime): ?TimeSlot
    {
        if (empty($slotIdOrTime)) {
            return null;
        }

        if (is_numeric($slotIdOrTime)) {
            return TimeSlot::where('id', (int) $slotIdOrTime)
                ->where('is_active', true)
                ->first();
        }

        $timeString = trim((string) $slotIdOrTime);
        $selectedDate = Carbon::parse($date);
        $dayOfWeek = strtolower($selectedDate->format('l'));

        $specificSlots = TimeSlot::whereDate('specific_date', $selectedDate->format('Y-m-d'))
            ->where('is_active', true)
            ->get();

        $recurringSlots = TimeSlot::where('day_of_week', $dayOfWeek)
            ->whereNull('specific_date')
            ->where('is_active', true)
            ->get();

        $allSlots = $specificSlots->concat($recurringSlots)->sortBy('start_time');

        // Attempt exact parsed start_time match (e.g. "10:00 AM" or "10:00")
        try {
            $parsedTargetTime = Carbon::parse($timeString)->format('H:i');
        } catch (Throwable) {
            $parsedTargetTime = null;
        }

        foreach ($allSlots as $slot) {
            $slotStartTime = Carbon::parse($slot->start_time)->format('H:i');
            if ($parsedTargetTime !== null && $slotStartTime === $parsedTargetTime) {
                return $slot;
            }

            // Fallback match within time range string e.g. "10:00 AM - 11:00 AM"
            if (stripos($slot->time_range, $timeString) !== false) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * Book an appointment with customer details, date, and slot.
     *
     * @param  array{
     *     appointment_date: string,
     *     customer_name: string,
     *     customer_email: string,
     *     time_slot_id?: int|string|null,
     *     time?: string|null,
     *     customer_phone?: string|null,
     *     notes?: string|null
     * }  $data
     * @return array{
     *     success: bool,
     *     message: string,
     *     appointment?: Appointment,
     *     booking_reference?: string,
     *     customer_name?: string,
     *     customer_email?: string,
     *     customer_phone?: ?string,
     *     appointment_date?: string,
     *     formatted_date?: string,
     *     start_time?: string,
     *     end_time?: string,
     *     time_range?: string,
     *     status?: string,
     *     confirmation_url?: string
     * }
     */
    public function bookAppointment(array $data): array
    {
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerEmail = trim((string) ($data['customer_email'] ?? ''));
        $customerPhone = isset($data['customer_phone']) ? trim((string) $data['customer_phone']) : null;
        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;
        $dateStr = trim((string) ($data['appointment_date'] ?? ''));

        if ($customerName === '') {
            return [
                'success' => false,
                'message' => 'Customer name is required to complete the booking.',
            ];
        }

        if ($customerEmail === '' || ! filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'A valid email address is required for appointment confirmation.',
            ];
        }

        if ($dateStr === '') {
            return [
                'success' => false,
                'message' => 'Please provide an appointment date.',
            ];
        }

        try {
            $selectedDate = Carbon::parse($dateStr);
        } catch (Throwable) {
            return [
                'success' => false,
                'message' => 'Invalid appointment date format. Please use YYYY-MM-DD.',
            ];
        }

        if ($selectedDate->lt(Carbon::today())) {
            return [
                'success' => false,
                'message' => 'Please select a date that is today or in the future.',
            ];
        }

        $slotIdentifier = $data['time_slot_id'] ?? $data['time'] ?? null;
        $resolvedSlot = $this->resolveTimeSlot($dateStr, $slotIdentifier);

        if (! $resolvedSlot) {
            return [
                'success' => false,
                'message' => 'The selected time slot was not found or is unavailable for this date.',
            ];
        }

        $dayOfWeek = strtolower($selectedDate->format('l'));
        $workingDay = WorkingDay::where('day_of_week', $dayOfWeek)->first();

        return DB::transaction(function () use ($resolvedSlot, $selectedDate, $workingDay, $customerName, $customerEmail, $customerPhone, $notes) {
            /** @var TimeSlot|null $slot */
            $slot = TimeSlot::where('id', $resolvedSlot->id)
                ->lockForUpdate()
                ->first();

            if (! $slot || ! $slot->is_active) {
                return [
                    'success' => false,
                    'message' => 'The selected time slot is currently unavailable.',
                ];
            }

            // Verify slot belongs to this date or day
            $isDayMatch = ($slot->day_of_week === strtolower($selectedDate->format('l')));
            $isDateMatch = ($slot->specific_date && $slot->specific_date->format('Y-m-d') === $selectedDate->format('Y-m-d'));

            if (! $isDayMatch && ! $isDateMatch) {
                return [
                    'success' => false,
                    'message' => 'The selected time slot does not match the chosen date.',
                ];
            }

            // Check if closed day (unless specific date slot)
            if (! $isDateMatch && $workingDay && ! $workingDay->is_open) {
                return [
                    'success' => false,
                    'message' => 'Bookings are not accepted on this day.',
                ];
            }

            // Check if slot start time has already passed today
            if ($selectedDate->isToday() && $slot->start_time <= Carbon::now()->format('H:i:s')) {
                return [
                    'success' => false,
                    'message' => 'This time slot has already passed for today.',
                ];
            }

            // Count existing bookings for this slot on this date
            $currentBookings = Appointment::where('time_slot_id', $slot->id)
                ->whereDate('appointment_date', $selectedDate->format('Y-m-d'))
                ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
                ->lockForUpdate()
                ->count();

            if ($currentBookings >= $slot->capacity) {
                return [
                    'success' => false,
                    'message' => 'This time slot was just booked and is now full. Please select another time slot.',
                ];
            }

            $appointment = Appointment::create([
                'booking_reference' => Appointment::generateBookingReference(),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone ?: null,
                'appointment_date' => $selectedDate->format('Y-m-d'),
                'time_slot_id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'status' => Appointment::STATUS_CONFIRMED,
                'notes' => $notes ?: null,
            ]);

            return [
                'success' => true,
                'message' => 'Your appointment has been successfully scheduled!',
                'appointment' => $appointment,
                'booking_reference' => $appointment->booking_reference,
                'customer_name' => $appointment->customer_name,
                'customer_email' => $appointment->customer_email,
                'customer_phone' => $appointment->customer_phone,
                'appointment_date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : $selectedDate->format('Y-m-d'),
                'formatted_date' => $appointment->formatted_date,
                'start_time' => Carbon::parse($slot->start_time)->format('g:i A'),
                'end_time' => Carbon::parse($slot->end_time)->format('g:i A'),
                'time_range' => $appointment->time_range,
                'status' => $appointment->status,
                'confirmation_url' => route('booking.confirmation', $appointment->booking_reference),
            ];
        });
    }
}

