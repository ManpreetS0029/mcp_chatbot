<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlotService
{
    /**
     * Get available time slots for a given date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getAvailableSlots(string $date): Collection
    {
        $selectedDate = Carbon::parse($date);
        $dayOfWeek = strtolower($selectedDate->format('l'));

        $workingDay = WorkingDay::where('day_of_week', $dayOfWeek)->first();

        // Specific date slots take precedence or can open a normally closed day
        $specificSlots = TimeSlot::whereDate('specific_date', $selectedDate->format('Y-m-d'))
            ->where('is_active', true)
            ->get();

        $isClosed = ($workingDay && ! $workingDay->is_open) && $specificSlots->isEmpty();

        if ($isClosed) {
            return collect();
        }

        // Fetch recurring slots for this day of week
        $recurringSlots = TimeSlot::where('day_of_week', $dayOfWeek)
            ->whereNull('specific_date')
            ->where('is_active', true)
            ->get();

        $allSlots = $recurringSlots->concat($specificSlots)->sortBy('start_time');

        // Check if selected date is today, in which case past time slots should be excluded
        $isToday = $selectedDate->isToday();
        $currentTime = Carbon::now()->format('H:i:s');

        // Get booked counts per slot for this specific date
        $bookedCounts = Appointment::whereDate('appointment_date', $selectedDate->format('Y-m-d'))
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->select('time_slot_id', DB::raw('count(*) as total'))
            ->groupBy('time_slot_id')
            ->pluck('total', 'time_slot_id');

        $availableSlots = collect();

        foreach ($allSlots as $slot) {
            if ($isToday && $slot->start_time <= $currentTime) {
                continue;
            }

            $booked = $bookedCounts->get($slot->id, 0);
            $remaining = max(0, $slot->capacity - $booked);

            if ($remaining > 0) {
                $availableSlots->push([
                    'id' => $slot->id,
                    'start_time' => Carbon::parse($slot->start_time)->format('g:i A'),
                    'end_time' => Carbon::parse($slot->end_time)->format('g:i A'),
                    'time_range' => $slot->time_range,
                    'remaining_capacity' => $remaining,
                    'capacity' => $slot->capacity,
                ]);
            }
        }

        return $availableSlots;
    }

    /**
     * Get detailed availability information for a date, including whether closed.
     *
     * @return array{date: string, formatted_date: string, is_open: bool, is_closed: bool, message: ?string, slots: Collection<int, array<string, mixed>>}
     */
    public function getAvailabilityDetails(string $date): array
    {
        $selectedDate = Carbon::parse($date);
        $dayOfWeek = strtolower($selectedDate->format('l'));

        $workingDay = WorkingDay::where('day_of_week', $dayOfWeek)->first();

        // Specific date slots take precedence or can open a normally closed day
        $specificSlots = TimeSlot::whereDate('specific_date', $selectedDate->format('Y-m-d'))
            ->where('is_active', true)
            ->get();

        $isClosed = ($workingDay && ! $workingDay->is_open) && $specificSlots->isEmpty();

        if ($isClosed) {
            return [
                'date' => $selectedDate->format('Y-m-d'),
                'formatted_date' => $selectedDate->format('l, F j, Y'),
                'is_open' => false,
                'is_closed' => true,
                'message' => "We are closed on {$selectedDate->format('l')}s.",
                'slots' => collect(),
            ];
        }

        $slots = $this->getAvailableSlots($date);

        return [
            'date' => $selectedDate->format('Y-m-d'),
            'formatted_date' => $selectedDate->format('l, F j, Y'),
            'is_open' => true,
            'is_closed' => false,
            'message' => $slots->isEmpty() ? 'No available time slots found for this date.' : null,
            'slots' => $slots,
        ];
    }
}
