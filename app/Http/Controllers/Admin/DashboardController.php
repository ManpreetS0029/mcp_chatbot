<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard with appointments list.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $dateFilter = $request->query('date_filter');
        $specificDate = $request->query('date');

        // Query builder
        $query = Appointment::with('timeSlot')->orderByDesc('appointment_date')->orderByDesc('start_time');

        // Search filter
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('booking_reference', 'like', "%{$search}%");
            });
        }

        // Status filter
        if (! empty($status) && in_array($status, Appointment::STATUSES, true)) {
            $query->where('status', $status);
        }

        // Date range filter
        $today = Carbon::today();
        if (! empty($specificDate)) {
            $query->whereDate('appointment_date', $specificDate);
        } elseif ($dateFilter === 'today') {
            $query->whereDate('appointment_date', $today);
        } elseif ($dateFilter === 'tomorrow') {
            $query->whereDate('appointment_date', $today->copy()->addDay());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('appointment_date', [
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek(),
            ]);
        } elseif ($dateFilter === 'upcoming') {
            $query->whereDate('appointment_date', '>=', $today)
                ->where('status', '!=', Appointment::STATUS_CANCELLED);
        }

        $appointments = $query->paginate(15)->withQueryString();

        // Metrics for summary cards
        $metrics = [
            'total' => Appointment::count(),
            'today' => Appointment::whereDate('appointment_date', $today)->count(),
            'upcoming' => Appointment::whereDate('appointment_date', '>=', $today)->where('status', '!=', Appointment::STATUS_CANCELLED)->count(),
            'confirmed' => Appointment::where('status', Appointment::STATUS_CONFIRMED)->count(),
            'pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            'completed' => Appointment::where('status', Appointment::STATUS_COMPLETED)->count(),
            'cancelled' => Appointment::where('status', Appointment::STATUS_CANCELLED)->count(),
        ];

        return view('admin.dashboard', [
            'appointments' => $appointments,
            'metrics' => $metrics,
            'currentSearch' => $search,
            'currentStatus' => $status,
            'currentDateFilter' => $dateFilter,
            'currentSpecificDate' => $specificDate,
        ]);
    }

    /**
     * Get appointments for the calendar view grouped by date.
     */
    public function getCalendarAppointments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2050'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? Carbon::today()->year);
        $month = (int) ($validated['month'] ?? Carbon::today()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $appointments = Appointment::with('timeSlot')
            ->whereBetween('appointment_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        $grouped = [];
        foreach ($appointments as $apt) {
            $dateStr = Carbon::parse($apt->appointment_date)->format('Y-m-d');
            $grouped[$dateStr][] = [
                'id' => $apt->id,
                'reference' => $apt->booking_reference,
                'customer_name' => $apt->customer_name,
                'time_range' => $apt->time_range,
                'status' => $apt->status,
                'status_color' => $apt->status_color,
                'view_url' => route('admin.appointments.show', $apt),
            ];
        }

        return response()->json([
            'year' => $year,
            'month' => $month,
            'month_name' => $startOfMonth->format('F'),
            'appointments' => $grouped,
        ]);
    }

    /**
     * Show single appointment details.
     */
    public function show(Appointment $appointment): View
    {
        $appointment->load('timeSlot');

        return view('admin.appointments.show', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * Update appointment status or admin notes.
     */
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Appointment::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $appointment->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $appointment->admin_notes,
        ]);

        return back()->with('success', 'Appointment status updated to '.ucfirst($validated['status']).'.');
    }

    /**
     * Delete an appointment.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        $ref = $appointment->booking_reference;
        $appointment->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', "Appointment {$ref} deleted successfully.");
    }
}
