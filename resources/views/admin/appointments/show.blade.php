@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Top Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-500 hover:text-slate-900 transition-colors">
            &larr; Back to appointments
        </a>

        <form method="POST" action="{{ route('admin.appointments.destroy', $appointment) }}" onsubmit="return confirm('Delete this appointment permanently?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 transition-colors cursor-pointer">
                Delete appointment
            </button>
        </form>
    </div>

    <!-- Appointment Header Card -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-slate-100">
            <div>
                <span class="text-[11px] text-slate-400 block">Booking reference</span>
                <h1 class="text-xl font-mono font-semibold text-slate-900 mt-0.5">{{ $appointment->booking_reference }}</h1>
                <span class="text-xs text-slate-400 mt-0.5 block">Booked on {{ $appointment->created_at->format('M j, Y \a\t g:i A') }}</span>
            </div>

            <div>
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium border {{ $appointment->status_color }}">
                    {{ ucfirst($appointment->status) }}
                </span>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-5">
            <!-- Schedule details -->
            <div>
                <h2 class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Schedule</h2>
                <dl class="bg-slate-50 rounded-md border border-slate-100 p-3.5 space-y-2 text-xs">
                    <div>
                        <dt class="text-slate-400">Date</dt>
                        <dd class="font-medium text-slate-900">{{ $appointment->formatted_date }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Time slot</dt>
                        <dd class="font-medium text-slate-900">{{ $appointment->time_range }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Customer details -->
            <div>
                <h2 class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Customer</h2>
                <dl class="bg-slate-50 rounded-md border border-slate-100 p-3.5 space-y-2 text-xs">
                    <div>
                        <dt class="text-slate-400">Full name</dt>
                        <dd class="font-medium text-slate-900">{{ $appointment->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Email address</dt>
                        <dd>
                            <a href="mailto:{{ $appointment->customer_email }}" class="text-slate-900 hover:underline">
                                {{ $appointment->customer_email }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400">Phone</dt>
                        <dd class="text-slate-700">{{ $appointment->customer_phone ?: 'Not provided' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if ($appointment->notes)
            <div class="mt-5 pt-5 border-t border-slate-100">
                <h2 class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1.5">Customer notes</h2>
                <div class="p-3 bg-slate-50 rounded-md border border-slate-100 text-xs text-slate-700 whitespace-pre-line">
                    {{ $appointment->notes }}
                </div>
            </div>
        @endif
    </div>

    <!-- Status & Admin Notes Update Form -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Update status &amp; internal notes</h2>

        <form method="POST" action="{{ route('admin.appointments.status', $appointment) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="status" class="block text-xs font-medium text-slate-700 mb-1">Status</label>
                <select name="status" id="status" class="w-full sm:w-56 text-xs rounded-md border border-slate-200 p-2 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                    <option value="confirmed" {{ $appointment->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="pending" {{ $appointment->status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ $appointment->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $appointment->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div>
                <label for="admin_notes" class="block text-xs font-medium text-slate-700 mb-1">Internal notes</label>
                <textarea
                    name="admin_notes"
                    id="admin_notes"
                    rows="3"
                    placeholder="Notes for staff regarding this appointment..."
                    class="w-full text-xs rounded-md border border-slate-200 p-2.5 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors"
                >{{ old('admin_notes', $appointment->admin_notes) }}</textarea>
            </div>

            <button
                type="submit"
                class="px-4 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer"
            >
                Save changes
            </button>
        </form>
    </div>
</div>
@endsection
