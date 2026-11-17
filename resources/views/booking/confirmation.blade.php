@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
    <div class="bg-white rounded-lg border border-slate-200 p-6 sm:p-8">
        <!-- Header -->
        <div class="pb-6 border-b border-slate-100">
            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Booking confirmed</h1>
            <p class="text-xs text-slate-500 mt-1">
                Your appointment has been registered. Please save your reference code below.
            </p>
        </div>

        <div class="py-6 space-y-6">
            <!-- Reference Code Banner -->
            <div class="bg-slate-50 border border-slate-200 rounded-md p-3.5 flex items-center justify-between gap-3">
                <div>
                    <span class="text-[11px] text-slate-500 block">Booking reference</span>
                    <span class="text-base font-mono font-semibold text-slate-900" id="refText">
                        {{ $appointment->booking_reference }}
                    </span>
                </div>
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText('{{ $appointment->booking_reference }}'); this.textContent = 'Copied'; setTimeout(() => this.textContent = 'Copy', 2000)"
                    class="px-2.5 py-1 text-xs font-medium rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                    Copy
                </button>
            </div>

            <!-- Details List -->
            <dl class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex justify-between gap-4">
                    <dt class="text-slate-500">Date</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ $appointment->formatted_date }}</dd>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <dt class="text-slate-500">Time</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ $appointment->time_range }}</dd>
                </div>
                <div class="py-2.5 flex justify-between gap-4 items-center">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border {{ $appointment->status_color }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </dd>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <dt class="text-slate-500">Name</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ $appointment->customer_name }}</dd>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ $appointment->customer_email }}</dd>
                </div>
                @if ($appointment->customer_phone)
                <div class="py-2.5 flex justify-between gap-4">
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="font-medium text-slate-900 text-right">{{ $appointment->customer_phone }}</dd>
                </div>
                @endif
                @if ($appointment->notes)
                <div class="py-2.5 flex justify-between gap-4">
                    <dt class="text-slate-500">Notes</dt>
                    <dd class="text-slate-700 text-right max-w-xs">{{ $appointment->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Action buttons -->
        <div class="pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <button
                type="button"
                onclick="window.print()"
                class="w-full sm:w-auto px-3.5 py-2 text-xs font-medium rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer">
                Print summary
            </button>

            <a
                href="{{ route('booking.index') }}"
                class="w-full sm:w-auto px-4 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors text-center">
                Book another appointment
            </a>
        </div>
    </div>
</div>
@endsection
