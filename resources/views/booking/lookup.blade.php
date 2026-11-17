@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Find your booking</h1>
        <p class="text-xs text-slate-500 mt-1">
            Enter your booking reference code to view schedule details.
        </p>
    </div>

    <!-- Search Form -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
        <form method="GET" action="{{ route('booking.lookup') }}" class="flex flex-col sm:flex-row items-center gap-2">
            <input
                type="text"
                name="reference"
                required
                value="{{ $searchedReference }}"
                placeholder="APT-XXXXXXXX"
                class="w-full text-xs font-mono uppercase rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
            <button
                type="submit"
                class="w-full sm:w-auto shrink-0 px-4 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer">
                Search
            </button>
        </form>
    </div>

    @if ($appointment)
        <!-- Appointment Result Card -->
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[11px] text-slate-400 block">Reference</span>
                    <span class="font-mono text-sm font-semibold text-slate-900">{{ $appointment->booking_reference }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[11px] font-medium border {{ $appointment->status_color }}">
                    {{ ucfirst($appointment->status) }}
                </span>
            </div>

            <dl class="divide-y divide-slate-100 text-xs py-2">
                <div class="py-2 flex justify-between">
                    <dt class="text-slate-500">Date</dt>
                    <dd class="font-medium text-slate-900">{{ $appointment->formatted_date }}</dd>
                </div>
                <div class="py-2 flex justify-between">
                    <dt class="text-slate-500">Time</dt>
                    <dd class="font-medium text-slate-900">{{ $appointment->time_range }}</dd>
                </div>
                <div class="py-2 flex justify-between">
                    <dt class="text-slate-500">Customer</dt>
                    <dd class="font-medium text-slate-900">{{ $appointment->customer_name }}</dd>
                </div>
                <div class="py-2 flex justify-between">
                    <dt class="text-slate-500">Email</dt>
                    <dd class="text-slate-700">{{ $appointment->customer_email }}</dd>
                </div>
            </dl>

            <div class="pt-3 border-t border-slate-100">
                <a href="{{ route('booking.confirmation', $appointment->booking_reference) }}" class="text-xs font-medium text-slate-900 hover:underline">
                    View full confirmation page &rarr;
                </a>
            </div>
        </div>
    @elseif ($searchedReference)
        <div class="bg-white rounded-lg border border-slate-200 p-6 text-center">
            <p class="text-sm font-medium text-slate-800">No appointment found</p>
            <p class="text-xs text-slate-500 mt-1">Please double-check the reference code and try again.</p>
        </div>
    @endif
</div>
@endsection
