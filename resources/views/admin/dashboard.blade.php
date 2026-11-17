@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-900 tracking-tight">Appointments</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage customer bookings, schedule availability, and view calendar.</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="flex items-center rounded-md border border-slate-200 bg-white p-0.5 text-xs font-medium">
                <button
                    type="button"
                    id="tableViewToggle"
                    class="px-2.5 py-1 rounded bg-slate-900 text-white transition-colors cursor-pointer"
                >
                    Table
                </button>
                <button
                    type="button"
                    id="calendarViewToggle"
                    class="px-2.5 py-1 rounded text-slate-600 hover:text-slate-900 transition-colors cursor-pointer"
                >
                    Calendar
                </button>
            </div>

            <a href="{{ route('admin.schedule.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                Schedule settings
            </a>

            <a href="{{ route('booking.index') }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                <span>+</span> New booking
            </a>
        </div>
    </div>

    <!-- Stat Summary Tiles -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
        <a href="{{ route('admin.dashboard') }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Total</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['total'] }}</span>
            <span class="text-[10px] text-slate-400">All bookings</span>
        </a>

        <a href="{{ route('admin.dashboard', ['date_filter' => 'today']) }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Today</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['today'] }}</span>
            <span class="text-[10px] text-slate-400">Scheduled</span>
        </a>

        <a href="{{ route('admin.dashboard', ['date_filter' => 'upcoming']) }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Upcoming</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['upcoming'] }}</span>
            <span class="text-[10px] text-slate-400">Future slots</span>
        </a>

        <a href="{{ route('admin.dashboard', ['status' => 'confirmed']) }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Confirmed</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['confirmed'] }}</span>
            <span class="text-[10px] text-emerald-600">Active</span>
        </a>

        <a href="{{ route('admin.dashboard', ['status' => 'pending']) }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Pending</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['pending'] }}</span>
            <span class="text-[10px] text-amber-600">Awaiting</span>
        </a>

        <a href="{{ route('admin.dashboard', ['status' => 'completed']) }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Completed</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['completed'] }}</span>
            <span class="text-[10px] text-slate-400">Done</span>
        </a>

        <a href="{{ route('admin.dashboard', ['status' => 'cancelled']) }}" class="p-3 rounded-md bg-white border border-slate-200 hover:border-slate-300 transition-colors">
            <span class="text-[11px] text-slate-500 block">Cancelled</span>
            <span class="text-xl font-semibold text-slate-900 mt-0.5 block">{{ $metrics['cancelled'] }}</span>
            <span class="text-[10px] text-rose-500">Void</span>
        </a>
    </div>

    <!-- VIEW 1: Table View Section -->
    <div id="tableViewSection" class="space-y-4">
        <!-- Filters & Search Bar -->
        <div class="bg-white rounded-lg border border-slate-200 p-3">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                <!-- Search -->
                <div class="sm:col-span-5 relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ $currentSearch }}"
                        placeholder="Search by customer, email, reference..."
                        class="w-full text-xs rounded-md border border-slate-200 px-3 py-1.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors"
                    >
                </div>

                <!-- Date filter -->
                <div class="sm:col-span-3">
                    <select name="date_filter" class="w-full text-xs rounded-md border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                        <option value="">All dates</option>
                        <option value="today" {{ $currentDateFilter === 'today' ? 'selected' : '' }}>Today only</option>
                        <option value="tomorrow" {{ $currentDateFilter === 'tomorrow' ? 'selected' : '' }}>Tomorrow</option>
                        <option value="this_week" {{ $currentDateFilter === 'this_week' ? 'selected' : '' }}>This week</option>
                        <option value="upcoming" {{ $currentDateFilter === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    </select>
                </div>

                <!-- Status filter -->
                <div class="sm:col-span-2">
                    <select name="status" class="w-full text-xs rounded-md border border-slate-200 px-2.5 py-1.5 text-slate-700 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                        <option value="">All statuses</option>
                        <option value="confirmed" {{ $currentStatus === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="sm:col-span-2 flex items-center gap-1.5">
                    <button type="submit" class="w-full py-1.5 px-3 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors cursor-pointer text-center">
                        Filter
                    </button>
                    @if ($currentSearch || $currentStatus || $currentDateFilter || $currentSpecificDate)
                        <a href="{{ route('admin.dashboard') }}" class="p-1.5 text-slate-400 hover:text-slate-600 rounded" title="Clear filters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            @if ($appointments->isEmpty())
                <div class="py-12 text-center">
                    <p class="text-sm font-medium text-slate-800">No appointments found</p>
                    <p class="text-xs text-slate-400 mt-1">Try adjusting your filters or search terms.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-medium text-slate-500 uppercase tracking-wider">
                                <th class="py-2.5 px-4">Reference</th>
                                <th class="py-2.5 px-4">Customer</th>
                                <th class="py-2.5 px-4">Schedule</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4">Notes</th>
                                <th class="py-2.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($appointments as $apt)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4 font-mono font-medium text-slate-900">
                                        <a href="{{ route('admin.appointments.show', $apt) }}" class="hover:underline">
                                            {{ $apt->booking_reference }}
                                        </a>
                                    </td>

                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-900">{{ $apt->customer_name }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $apt->customer_email }}</div>
                                        @if ($apt->customer_phone)
                                            <div class="text-[11px] text-slate-400">{{ $apt->customer_phone }}</div>
                                        @endif
                                    </td>

                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-800">{{ $apt->formatted_date }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $apt->time_range }}</div>
                                    </td>

                                    <td class="py-3 px-4">
                                        <form method="POST" action="{{ route('admin.appointments.status', $apt) }}" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <select
                                                name="status"
                                                onchange="this.form.submit()"
                                                class="text-[11px] font-medium rounded border py-0.5 px-2 cursor-pointer focus:outline-none focus:ring-1 focus:ring-slate-400 {{ $apt->status_color }}"
                                            >
                                                <option value="confirmed" {{ $apt->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                                <option value="pending" {{ $apt->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="completed" {{ $apt->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ $apt->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>

                                    <td class="py-3 px-4 max-w-[180px] truncate text-slate-500">
                                        @if ($apt->notes)
                                            <span title="{{ $apt->notes }}">{{ $apt->notes }}</span>
                                        @else
                                            <span class="text-slate-300">&mdash;</span>
                                        @endif
                                    </td>

                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('admin.appointments.show', $apt) }}"
                                                class="text-xs font-medium text-slate-600 hover:text-slate-900"
                                            >
                                                View
                                            </a>

                                            <form method="POST" action="{{ route('admin.appointments.destroy', $apt) }}" onsubmit="return confirm('Delete this appointment permanently?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="text-slate-400 hover:text-rose-600 p-0.5 transition-colors cursor-pointer"
                                                    title="Delete"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($appointments->hasPages())
                    <div class="p-3 border-t border-slate-200">
                        {{ $appointments->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- VIEW 2: FullCalendar.js View Section -->
    <div id="calendarViewSection" class="hidden space-y-4">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Appointments Calendar</h2>
                    <p class="text-xs text-slate-400">Click any scheduled appointment to view details and manage status</p>
                </div>
            </div>

            <div id="adminFullCalendarEl" class="min-h-[500px]"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<style>
/* Clean FullCalendar Styling for Admin Dashboard */
#adminFullCalendarEl.fc {
    --fc-border-color: #f1f5f9;
    --fc-today-bg-color: #f8fafc;
    --fc-page-bg-color: #ffffff;
    --fc-neutral-bg-color: #f8fafc;
    --fc-highlight-color: rgba(15, 23, 42, 0.04);
    font-family: inherit;
    font-size: 12px;
}
#adminFullCalendarEl .fc-toolbar-title {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #0f172a;
}
#adminFullCalendarEl .fc-button {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #334155 !important;
    box-shadow: none !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    padding: 4px 10px !important;
    text-transform: capitalize !important;
    transition: all 0.15s ease;
}
#adminFullCalendarEl .fc-button:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}
#adminFullCalendarEl .fc-button-active, #adminFullCalendarEl .fc-button:active {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
}
#adminFullCalendarEl .fc-daygrid-day-number {
    font-size: 11px;
    font-weight: 500;
    color: #334155;
    padding: 4px 6px !important;
    text-decoration: none !important;
}
#adminFullCalendarEl .fc-daygrid-day-frame {
    min-height: 85px !important;
}
.admin-fc-event {
    margin: 1px 2px !important;
    padding: 2px 4px !important;
    border-radius: 4px !important;
    font-size: 10px !important;
    font-weight: 500 !important;
    border: 1px solid #e2e8f0 !important;
    background: #f8fafc !important;
    color: #0f172a !important;
    text-decoration: none !important;
    display: block !important;
    transition: all 0.15s ease;
}
.admin-fc-event:hover {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
}
#adminFullCalendarEl .fc-scrollgrid {
    border-radius: 6px;
    border: 1px solid #f1f5f9 !important;
    overflow: hidden;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableViewBtn = document.getElementById('tableViewToggle');
    const calendarViewBtn = document.getElementById('calendarViewToggle');
    const tableViewSection = document.getElementById('tableViewSection');
    const calendarViewSection = document.getElementById('calendarViewSection');
    const adminCalendarEl = document.getElementById('adminFullCalendarEl');

    let adminFullCalendar = null;
    let calendarInitialized = false;

    // View toggler
    tableViewBtn.addEventListener('click', function() {
        tableViewBtn.classList.add('bg-slate-900', 'text-white');
        tableViewBtn.classList.remove('text-slate-600');
        calendarViewBtn.classList.remove('bg-slate-900', 'text-white');
        calendarViewBtn.classList.add('text-slate-600');
        tableViewSection.classList.remove('hidden');
        calendarViewSection.classList.add('hidden');
    });

    calendarViewBtn.addEventListener('click', function() {
        calendarViewBtn.classList.add('bg-slate-900', 'text-white');
        calendarViewBtn.classList.remove('text-slate-600');
        tableViewBtn.classList.remove('bg-slate-900', 'text-white');
        tableViewBtn.classList.add('text-slate-600');
        tableViewSection.classList.add('hidden');
        calendarViewSection.classList.remove('hidden');

        if (!calendarInitialized) {
            initAdminFullCalendar();
            calendarInitialized = true;
        } else if (adminFullCalendar) {
            adminFullCalendar.updateSize();
        }
    });

    function initAdminFullCalendar() {
        if (typeof FullCalendar === 'undefined' || !adminCalendarEl) return;

        adminFullCalendar = new FullCalendar.Calendar(adminCalendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            height: 'auto',
            events: async function(fetchInfo, successCallback, failureCallback) {
                try {
                    const midTime = (fetchInfo.start.getTime() + fetchInfo.end.getTime()) / 2;
                    const midDate = new Date(midTime);
                    const year = midDate.getFullYear();
                    const month = midDate.getMonth() + 1;

                    const res = await fetch(`/admin/calendar-appointments?year=${year}&month=${month}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    const events = [];

                    for (const [dateStr, aptList] of Object.entries(data.appointments || {})) {
                        for (const apt of aptList) {
                            events.push({
                                id: 'apt-' + apt.id,
                                title: `${apt.time_range} · ${apt.customer_name}`,
                                start: dateStr,
                                url: apt.view_url,
                                className: 'admin-fc-event',
                                extendedProps: apt
                            });
                        }
                    }

                    successCallback(events);
                } catch (err) {
                    console.error('Error fetching admin calendar events:', err);
                    failureCallback(err);
                }
            },
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });

        adminFullCalendar.render();
    }
});
</script>
@endpush
