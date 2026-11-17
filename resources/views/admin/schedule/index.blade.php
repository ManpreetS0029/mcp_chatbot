@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Schedule &amp; Availability Management</h1>
            <p class="text-xs text-slate-500 mt-0.5">Configure working days, recurring slots, or date-specific availability.</p>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                onclick="document.getElementById('bulkModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
            >
                Bulk generate
            </button>

            <button
                type="button"
                onclick="document.getElementById('addSlotModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer"
            >
                <span>+</span> Add slot
            </button>
        </div>
    </div>

    <!-- Section 1: Working Days -->
    <div>
        <div class="mb-3">
            <h2 class="text-sm font-semibold text-slate-900">Working Days of the Week</h2>
            <p class="text-xs text-slate-500">Enable or disable booking for each day of the week.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
            @foreach ($daysList as $day)
                @php
                    $wDay = $workingDays->get($day);
                    $isOpen = $wDay ? $wDay->is_open : false;
                    $daySlotsCount = isset($slotsByDay[$day]) ? $slotsByDay[$day]->count() : 0;
                @endphp
                <div class="bg-white rounded-lg border p-3 flex flex-col justify-between transition-colors {{ $isOpen ? 'border-slate-200' : 'border-slate-200 bg-slate-50/70 opacity-70' }}">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-slate-900">
                                {{ ucfirst($day) }}
                            </span>
                            <span class="w-1.5 h-1.5 rounded-full {{ $isOpen ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        </div>

                        <span class="text-[11px] {{ $isOpen ? 'text-emerald-700' : 'text-slate-400' }} block">
                            {{ $isOpen ? 'Open' : 'Closed' }}
                        </span>

                        <div class="mt-1 text-[11px] text-slate-500">
                            {{ $daySlotsCount }} {{ Str::plural('slot', $daySlotsCount) }}
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-slate-100">
                        @if ($wDay)
                            <form method="POST" action="{{ route('admin.schedule.days.toggle', $wDay) }}">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    class="w-full py-1 px-2 rounded text-[11px] font-medium border transition-colors cursor-pointer {{ $isOpen ? 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }}"
                                >
                                    {{ $isOpen ? 'Close day' : 'Open day' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section 2: Time Slots -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Time slots</h2>
                <p class="text-xs text-slate-500">Manage time windows and booking capacity.</p>
            </div>

            <!-- Day Tabs Filter -->
            <div class="flex items-center gap-1 overflow-x-auto pb-1 text-xs">
                <a
                    href="{{ route('admin.schedule.index', ['day' => 'all']) }}"
                    class="px-2.5 py-1 rounded-md transition-colors shrink-0 {{ $selectedDay === 'all' ? 'bg-slate-900 text-white font-medium' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
                >
                    All days ({{ $totalRecurringSlots }})
                </a>
                @foreach ($daysList as $day)
                    <a
                        href="{{ route('admin.schedule.index', ['day' => $day]) }}"
                        class="px-2 py-1 rounded-md transition-colors shrink-0 {{ $selectedDay === $day ? 'bg-slate-900 text-white font-medium' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
                    >
                        {{ ucfirst(substr($day, 0, 3)) }} ({{ isset($slotsByDay[$day]) ? $slotsByDay[$day]->count() : 0 }})
                    </a>
                @endforeach
            </div>
        </div>

        @php
            $displayDays = ($selectedDay === 'all') ? $daysList : [$selectedDay];
        @endphp

        <div class="space-y-4">
            @foreach ($displayDays as $day)
                @php
                    $daySlots = $slotsByDay[$day] ?? collect();
                    $wDay = $workingDays->get($day);
                @endphp

                <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900 text-xs">{{ ucfirst($day) }}</span>
                            <span class="text-xs text-slate-400">({{ $daySlots->count() }})</span>
                            @if ($wDay && ! $wDay->is_open)
                                <span class="text-[10px] text-rose-600 border border-rose-200 bg-rose-50 px-1.5 py-0.5 rounded">Closed</span>
                            @endif
                        </div>

                        <button
                            type="button"
                            onclick="openAddSlotForDay('{{ $day }}')"
                            class="text-xs font-medium text-slate-900 hover:underline cursor-pointer"
                        >
                            + Add slot
                        </button>
                    </div>

                    @if ($daySlots->isEmpty())
                        <div class="py-6 text-center text-xs text-slate-400">
                            No time slots for {{ ucfirst($day) }}.
                            <button type="button" onclick="openAddSlotForDay('{{ $day }}')" class="text-slate-900 underline ml-1 cursor-pointer">Add one</button>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 text-xs">
                            @foreach ($daySlots as $slot)
                                <div class="px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="w-32 font-medium text-slate-900">
                                            {{ $slot->time_range }}
                                        </span>

                                        <span class="text-slate-500 text-[11px]">
                                            Cap: <span class="text-slate-800 font-medium">{{ $slot->capacity }}</span>
                                        </span>

                                        <span class="text-[10px] px-1.5 py-0.5 rounded border {{ $slot->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                            {{ $slot->is_active ? 'Active' : 'Disabled' }}
                                        </span>

                                        @if ($slot->appointments_count > 0)
                                            <span class="text-[10px] text-slate-500">
                                                {{ $slot->appointments_count }} booked
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Slot Actions -->
                                    <div class="flex items-center gap-2 self-end sm:self-center">
                                        <form method="POST" action="{{ route('admin.schedule.slots.toggle', $slot) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="px-2 py-0.5 text-[11px] font-medium rounded border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
                                            >
                                                {{ $slot->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.schedule.slots.destroy', $slot) }}" onsubmit="return confirm('Remove this time slot?');">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-slate-400 hover:text-rose-600 p-1 transition-colors cursor-pointer"
                                                title="Delete"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section 3: Specific Date Slots -->
    @if ($specificSlots->isNotEmpty())
        <div class="bg-white rounded-lg border border-slate-200 p-4 space-y-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Date-specific overrides</h2>
                <p class="text-xs text-slate-500">One-off slots assigned to a specific date.</p>
            </div>

            <div class="divide-y divide-slate-100 text-xs">
                @foreach ($specificSlots as $slot)
                    <div class="py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-slate-900">
                                {{ $slot->specific_date ? $slot->specific_date->format('M j, Y (D)') : '' }}
                            </span>
                            <span class="text-slate-700">{{ $slot->time_range }}</span>
                            <span class="text-slate-400">Cap: {{ $slot->capacity }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded border {{ $slot->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
                                {{ $slot->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.schedule.slots.toggle', $slot) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 text-[11px] font-medium rounded border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                                    {{ $slot->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.schedule.slots.destroy', $slot) }}" onsubmit="return confirm('Delete this specific date slot?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-600 p-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Modal 1: Add Single Time Slot -->
<div id="addSlotModal" class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-semibold text-slate-900">Add time slot</h3>
            <button type="button" onclick="document.getElementById('addSlotModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.schedule.slots.store') }}" class="space-y-3.5 text-xs">
            @csrf

            <!-- Type Selector -->
            <div>
                <label class="block font-medium text-slate-700 mb-1">Frequency</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2 rounded-md border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs">
                        <input type="radio" name="slot_type" value="recurring" checked onchange="toggleSlotType('recurring')">
                        <span>Weekly recurring</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-md border border-slate-200 cursor-pointer hover:bg-slate-50 text-xs">
                        <input type="radio" name="slot_type" value="specific_date" onchange="toggleSlotType('specific_date')">
                        <span>Specific date</span>
                    </label>
                </div>
            </div>

            <!-- Day of Week selector -->
            <div id="dayOfWeekField">
                <label for="day_of_week" class="block font-medium text-slate-700 mb-1">Day of week</label>
                <select name="day_of_week" id="day_of_week" class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach ($daysList as $d)
                        <option value="{{ $d }}">{{ ucfirst($d) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Specific Date picker -->
            <div id="specificDateField" class="hidden">
                <label for="specific_date" class="block font-medium text-slate-700 mb-1">Target date</label>
                <input
                    type="date"
                    name="specific_date"
                    id="specific_date"
                    min="{{ date('Y-m-d') }}"
                    class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                >
            </div>

            <!-- Times -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="start_time" class="block font-medium text-slate-700 mb-1">Start time</label>
                    <input
                        type="time"
                        name="start_time"
                        id="start_time"
                        required
                        value="09:00"
                        class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                    >
                </div>
                <div>
                    <label for="end_time" class="block font-medium text-slate-700 mb-1">End time</label>
                    <input
                        type="time"
                        name="end_time"
                        id="end_time"
                        required
                        value="10:00"
                        class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                    >
                </div>
            </div>

            <!-- Capacity -->
            <div>
                <label for="capacity" class="block font-medium text-slate-700 mb-1">Max bookings (capacity)</label>
                <input
                    type="number"
                    name="capacity"
                    id="capacity"
                    min="1"
                    max="50"
                    value="1"
                    required
                    class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900"
                >
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('addSlotModal').classList.add('hidden')" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer">
                    Save slot
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Bulk Generate Time Slots -->
<div id="bulkModal" class="fixed inset-0 z-50 bg-slate-900/40 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-semibold text-slate-900">Bulk generate slots</h3>
            <button type="button" onclick="document.getElementById('bulkModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.schedule.slots.bulk') }}" class="space-y-3.5 text-xs">
            @csrf

            <!-- Days Checkboxes -->
            <div>
                <label class="block font-medium text-slate-700 mb-1.5">Apply to days</label>
                <div class="grid grid-cols-4 gap-1.5">
                    @foreach ($daysList as $d)
                        <label class="flex items-center gap-1.5 p-1.5 rounded border border-slate-200 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="days[]" value="{{ $d }}" {{ ! in_array($d, ['sunday'], true) ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            <span class="capitalize text-slate-700">{{ substr($d, 0, 3) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Hours Window -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="bulk_start_time" class="block font-medium text-slate-700 mb-1">Start of day</label>
                    <input type="time" name="start_time" id="bulk_start_time" value="09:00" required class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800">
                </div>
                <div>
                    <label for="bulk_end_time" class="block font-medium text-slate-700 mb-1">End of day</label>
                    <input type="time" name="end_time" id="bulk_end_time" value="17:00" required class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800">
                </div>
            </div>

            <!-- Slot Duration -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="duration_minutes" class="block font-medium text-slate-700 mb-1">Slot duration</label>
                    <select name="duration_minutes" id="duration_minutes" class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800">
                        <option value="15">15 minutes</option>
                        <option value="30">30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60" selected>60 minutes</option>
                        <option value="90">90 minutes</option>
                        <option value="120">120 minutes</option>
                    </select>
                </div>

                <div>
                    <label for="bulk_capacity" class="block font-medium text-slate-700 mb-1">Capacity per slot</label>
                    <input type="number" name="capacity" id="bulk_capacity" min="1" max="50" value="1" required class="w-full text-xs rounded-md border border-slate-200 p-2 text-slate-800">
                </div>
            </div>

            <p class="text-[11px] text-slate-400">
                Existing matching slots will be skipped to prevent duplicates.
            </p>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('bulkModal').classList.add('hidden')" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer">
                    Generate slots
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleSlotType(type) {
    const dayField = document.getElementById('dayOfWeekField');
    const dateField = document.getElementById('specificDateField');

    if (type === 'specific_date') {
        dayField.classList.add('hidden');
        dateField.classList.remove('hidden');
    } else {
        dayField.classList.remove('hidden');
        dateField.classList.add('hidden');
    }
}

function openAddSlotForDay(day) {
    const modal = document.getElementById('addSlotModal');
    const daySelect = document.getElementById('day_of_week');
    if (daySelect) daySelect.value = day;
    const recurringRadio = document.querySelector('input[name="slot_type"][value="recurring"]');
    if (recurringRadio) recurringRadio.checked = true;
    toggleSlotType('recurring');
    modal.classList.remove('hidden');
}
</script>
@endpush
