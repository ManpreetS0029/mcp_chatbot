<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreTimeSlotsRequest;
use App\Http\Requests\StoreTimeSlotRequest;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Display the schedule management page for days and time slots.
     */
    public function index(Request $request): View
    {
        $selectedDay = $request->query('day', 'all');

        // Ensure all 7 days exist in database
        foreach (WorkingDay::DAYS_OF_WEEK as $day) {
            WorkingDay::firstOrCreate(
                ['day_of_week' => $day],
                ['is_open' => ! in_array($day, ['sunday'], true)]
            );
        }

        $workingDays = WorkingDay::all()->keyBy('day_of_week');

        // Fetch recurring slots
        $slotsQuery = TimeSlot::withCount(['appointments' => function ($q) {
            $q->whereDate('appointment_date', '>=', Carbon::today())
                ->where('status', '!=', 'cancelled');
        }])->orderBy('start_time');

        if ($selectedDay !== 'all' && in_array($selectedDay, WorkingDay::DAYS_OF_WEEK, true)) {
            $slotsQuery->where('day_of_week', $selectedDay);
        }

        $recurringSlots = (clone $slotsQuery)->whereNull('specific_date')->get();

        // Specific date slots
        $specificSlots = TimeSlot::withCount(['appointments' => function ($q) {
            $q->whereDate('appointment_date', '>=', Carbon::today())
                ->where('status', '!=', 'cancelled');
        }])
            ->whereNotNull('specific_date')
            ->whereDate('specific_date', '>=', Carbon::today())
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get();

        // Group recurring slots by day of week
        $slotsByDay = [];
        foreach (WorkingDay::DAYS_OF_WEEK as $day) {
            $slotsByDay[$day] = $recurringSlots->where('day_of_week', $day);
        }

        return view('admin.schedule.index', [
            'workingDays' => $workingDays,
            'slotsByDay' => $slotsByDay,
            'specificSlots' => $specificSlots,
            'selectedDay' => $selectedDay,
            'daysList' => WorkingDay::DAYS_OF_WEEK,
            'totalRecurringSlots' => $recurringSlots->count(),
            'totalSpecificSlots' => $specificSlots->count(),
        ]);
    }

    /**
     * Toggle a business day open or closed.
     */
    public function toggleDay(Request $request, WorkingDay $workingDay): RedirectResponse
    {
        $workingDay->update([
            'is_open' => ! $workingDay->is_open,
        ]);

        $status = $workingDay->is_open ? 'open' : 'closed';

        return back()->with('success', ucfirst($workingDay->day_of_week)." is now marked as {$status}.");
    }

    /**
     * Store a newly created time slot.
     */
    public function storeSlot(StoreTimeSlotRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $slotType = $validated['slot_type'];
        $dayOfWeek = $slotType === 'recurring' ? $validated['day_of_week'] : null;
        $specificDate = $slotType === 'specific_date' ? $validated['specific_date'] : null;
        $formattedStartTime = Carbon::parse($validated['start_time'])->format('H:i:s');
        $formattedEndTime = Carbon::parse($validated['end_time'])->format('H:i:s');

        // Check if an identical slot already exists
        $exists = TimeSlot::where('day_of_week', $dayOfWeek)
            ->where('specific_date', $specificDate)
            ->where(function ($q) use ($formattedStartTime, $validated) {
                $q->where('start_time', $formattedStartTime)
                    ->orWhere('start_time', $validated['start_time']);
            })
            ->where(function ($q) use ($formattedEndTime, $validated) {
                $q->where('end_time', $formattedEndTime)
                    ->orWhere('end_time', $validated['end_time']);
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A time slot with these exact hours already exists for this day/date.');
        }

        TimeSlot::create([
            'day_of_week' => $dayOfWeek,
            'specific_date' => $specificDate,
            'start_time' => $formattedStartTime,
            'end_time' => $formattedEndTime,
            'capacity' => $validated['capacity'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Time slot created successfully!');
    }

    /**
     * Bulk generate recurring time slots for selected days.
     */
    public function bulkStoreSlots(BulkStoreTimeSlotsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $days = $validated['days'];
        $startTime = Carbon::createFromFormat('H:i', $validated['start_time']);
        $endTime = Carbon::createFromFormat('H:i', $validated['end_time']);
        $duration = (int) $validated['duration_minutes'];
        $capacity = (int) $validated['capacity'];

        $createdCount = 0;

        foreach ($days as $day) {
            $currentSlotStart = $startTime->copy();

            while ($currentSlotStart->copy()->addMinutes($duration)->lte($endTime)) {
                $currentSlotEnd = $currentSlotStart->copy()->addMinutes($duration);

                $formattedStart = $currentSlotStart->format('H:i:s');
                $formattedEnd = $currentSlotEnd->format('H:i:s');

                // Avoid creating duplicates
                $exists = TimeSlot::where('day_of_week', $day)
                    ->whereNull('specific_date')
                    ->where('start_time', $formattedStart)
                    ->where('end_time', $formattedEnd)
                    ->exists();

                if (! $exists) {
                    TimeSlot::create([
                        'day_of_week' => $day,
                        'specific_date' => null,
                        'start_time' => $formattedStart,
                        'end_time' => $formattedEnd,
                        'capacity' => $capacity,
                        'is_active' => true,
                    ]);
                    $createdCount++;
                }

                $currentSlotStart->addMinutes($duration);
            }
        }

        return back()->with('success', "Bulk generation complete! Created {$createdCount} new time slots.");
    }

    /**
     * Toggle a time slot active or inactive.
     */
    public function toggleSlot(TimeSlot $timeSlot): RedirectResponse
    {
        $timeSlot->update([
            'is_active' => ! $timeSlot->is_active,
        ]);

        $state = $timeSlot->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Time slot has been {$state}.");
    }

    /**
     * Delete a time slot.
     */
    public function destroySlot(TimeSlot $timeSlot): RedirectResponse
    {
        // Check for upcoming appointments
        $hasUpcomingAppointments = $timeSlot->appointments()
            ->whereDate('appointment_date', '>=', Carbon::today())
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasUpcomingAppointments) {
            return back()->with('error', 'Cannot delete this time slot because there are active upcoming appointments booked in it. Deactivate it instead.');
        }

        $timeSlot->delete();

        return back()->with('success', 'Time slot removed successfully.');
    }
}
