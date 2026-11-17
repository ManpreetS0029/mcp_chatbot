<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Show the appointment booking page.
     */
    public function index(): View
    {
        $workingDays = WorkingDay::all()->keyBy('day_of_week');

        // Prepare upcoming 30 days availability indicator
        $upcomingDays = [];
        $today = Carbon::today();

        for ($i = 0; $i < 30; $i++) {
            $date = $today->copy()->addDays($i);
            $dayOfWeek = strtolower($date->format('l'));
            $isOpen = $workingDays->get($dayOfWeek)?->is_open ?? false;

            $upcomingDays[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('D'),
                'day_number' => $date->format('j'),
                'month_name' => $date->format('M'),
                'is_open' => $isOpen,
                'is_today' => $i === 0,
            ];
        }

        return view('booking.index', [
            'workingDays' => $workingDays,
            'upcomingDays' => $upcomingDays,
            'minDate' => $today->format('Y-m-d'),
            'maxDate' => $today->copy()->addDays(60)->format('Y-m-d'),
        ]);
    }

    /**
     * Get availability summary across an entire month for the full calendar.
     */
    public function getMonthAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2050'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? Carbon::today()->year);
        $month = (int) ($validated['month'] ?? Carbon::today()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $today = Carbon::today();
        $currentTime = Carbon::now()->format('H:i:s');

        $workingDays = WorkingDay::all()->keyBy('day_of_week');

        // Recurring slots grouped by day_of_week
        $recurringSlots = TimeSlot::whereNull('specific_date')
            ->where('is_active', true)
            ->get()
            ->groupBy('day_of_week');

        // Specific date slots in this month
        $specificSlots = TimeSlot::whereNotNull('specific_date')
            ->whereBetween('specific_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where('is_active', true)
            ->get()
            ->groupBy(fn ($slot) => $slot->specific_date->format('Y-m-d'));

        // Appointments in this month (not cancelled)
        $bookedCounts = Appointment::whereBetween('appointment_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED])
            ->select('appointment_date', 'time_slot_id', DB::raw('count(*) as total'))
            ->groupBy('appointment_date', 'time_slot_id')
            ->get()
            ->groupBy(fn ($item) => Carbon::parse($item->appointment_date)->format('Y-m-d'));

        $days = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            $dateStr = $current->format('Y-m-d');
            $dayOfWeek = strtolower($current->format('l'));
            $isPast = $current->lt($today);
            $isToday = $current->eq($today);

            $workingDay = $workingDays->get($dayOfWeek);
            $daySpecificSlots = $specificSlots->get($dateStr, collect());
            $isOpen = ($workingDay && $workingDay->is_open) || $daySpecificSlots->isNotEmpty();

            $dayRecurring = $recurringSlots->get($dayOfWeek, collect());
            $allSlots = $dayRecurring->concat($daySpecificSlots)->sortBy('start_time');

            $dateBooked = $bookedCounts->get($dateStr, collect())->keyBy('time_slot_id');

            $totalSlots = 0;
            $availableSlots = 0;

            if ($isOpen && ! $isPast) {
                foreach ($allSlots as $slot) {
                    if ($isToday && $slot->start_time <= $currentTime) {
                        continue;
                    }
                    $totalSlots++;
                    $booked = $dateBooked->get($slot->id)?->total ?? 0;
                    if ($slot->capacity > $booked) {
                        $availableSlots++;
                    }
                }
            }

            $status = 'available';
            if ($isPast) {
                $status = 'past';
            } elseif (! $isOpen) {
                $status = 'closed';
            } elseif ($availableSlots === 0) {
                $status = 'full';
            }

            $days[$dateStr] = [
                'date' => $dateStr,
                'day' => (int) $current->format('j'),
                'day_of_week' => (int) $current->format('w'),
                'is_past' => $isPast,
                'is_today' => $isToday,
                'is_open' => $isOpen,
                'total_slots' => $totalSlots,
                'available_slots' => $availableSlots,
                'status' => $status,
            ];

            $current->addDay();
        }

        return response()->json([
            'year' => $year,
            'month' => $month,
            'month_name' => $startOfMonth->format('F'),
            'days' => $days,
        ]);
    }

    /**
     * Fetch available slots for a given date.
     */
    public function getSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $selectedDate = Carbon::parse($validated['date']);
        $dayOfWeek = strtolower($selectedDate->format('l'));

        $workingDay = WorkingDay::where('day_of_week', $dayOfWeek)->first();

        // Specific date slots take precedence or can open a normally closed day
        $specificSlots = TimeSlot::whereDate('specific_date', $selectedDate->format('Y-m-d'))
            ->where('is_active', true)
            ->get();

        $isClosed = ($workingDay && ! $workingDay->is_open) && $specificSlots->isEmpty();

        if ($isClosed) {
            return response()->json([
                'is_open' => false,
                'message' => "We are closed on {$selectedDate->format('l')}s.",
                'slots' => [],
            ]);
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

        $resultSlots = [];
        foreach ($allSlots as $slot) {
            // If today, filter out past slots
            if ($isToday && $slot->start_time <= $currentTime) {
                continue;
            }

            $booked = $bookedCounts->get($slot->id, 0);
            $remaining = max(0, $slot->capacity - $booked);
            $isAvailable = $remaining > 0;

            $resultSlots[] = [
                'id' => $slot->id,
                'start_time' => Carbon::parse($slot->start_time)->format('g:i A'),
                'end_time' => Carbon::parse($slot->end_time)->format('g:i A'),
                'time_range' => $slot->time_range,
                'capacity' => $slot->capacity,
                'remaining' => $remaining,
                'is_available' => $isAvailable,
            ];
        }

        return response()->json([
            'is_open' => true,
            'date' => $selectedDate->format('Y-m-d'),
            'formatted_date' => $selectedDate->format('l, F j, Y'),
            'slots' => $resultSlots,
            'message' => empty($resultSlots) ? 'No available time slots found for this date.' : null,
        ]);
    }

    /**
     * Store a newly booked appointment.
     */
    public function store(BookAppointmentRequest $request, BookingService $bookingService): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $result = $bookingService->bookAppointment($validated);

        if (! $result['success']) {
            return $this->bookingFailedResponse($request, $result['message']);
        }

        /** @var Appointment $appointment */
        $appointment = $result['appointment'];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'reference' => $appointment->booking_reference,
                'redirect_url' => route('booking.confirmation', $appointment->booking_reference),
            ]);
        }

        return redirect()->route('booking.confirmation', $appointment->booking_reference)
            ->with('success', 'Your appointment has been successfully scheduled!');
    }

    /**
     * Display appointment confirmation.
     */
    public function confirmation(string $reference): View
    {
        $appointment = Appointment::with('timeSlot')
            ->where('booking_reference', $reference)
            ->firstOrFail();

        return view('booking.confirmation', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * Customer appointment lookup / tracking.
     */
    public function lookup(Request $request): View|RedirectResponse
    {
        $reference = $request->query('reference');
        $appointment = null;

        if ($reference) {
            $appointment = Appointment::with('timeSlot')
                ->where('booking_reference', trim($reference))
                ->first();

            if (! $appointment) {
                return back()->with('error', 'No appointment found with that reference code.');
            }
        }

        return view('booking.lookup', [
            'appointment' => $appointment,
            'searchedReference' => $reference,
        ]);
    }

    /**
     * Handle booking failure response depending on request type.
     */
    private function bookingFailedResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return back()->withInput()->with('error', $message);
    }
}
