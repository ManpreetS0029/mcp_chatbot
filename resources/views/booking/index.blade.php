@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <!-- Header -->
    <div class="max-w-xl mb-8">
        <h1 class="text-2xl sm:text-3xl font-semibold text-slate-900 tracking-tight">
            Book Your Appointment
        </h1>
        <p class="mt-1.5 text-sm text-slate-500">
            Browse dates on the calendar, select an available time slot, and confirm your booking.
        </p>
    </div>

    <!-- Main Grid: Calendar & Slots on Left (7 cols), Contact Form on Right (5 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-7 space-y-6">
            <!-- FullCalendar Container -->
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">1. Select date</h2>
                        <p class="text-xs text-slate-500">Click an available date to view time slots</p>
                    </div>

                    <div class="flex items-center gap-3 text-[11px] text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Available
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-slate-300"></span> Closed
                        </span>
                    </div>
                </div>

                <!-- FullCalendar Mounting Element -->
                <div id="fullCalendarEl" class="min-h-[340px]"></div>
            </div>

            <!-- Time Slots Section -->
            <div class="bg-white rounded-lg border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3.5">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">2. Select time slot</h2>
                        <span id="slotSelectedDateDisplay" class="text-xs text-slate-500"></span>
                    </div>
                </div>

                <!-- Slots Container -->
                <div id="slotsContainer" class="min-h-[140px] flex flex-col justify-center">
                    <!-- Loading Spinner -->
                    <div id="slotsLoading" class="py-8 flex flex-col items-center justify-center text-slate-400 gap-2">
                        <svg class="animate-spin h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs">Loading available slots...</span>
                    </div>

                    <!-- Slots Grid -->
                    <div id="slotsGrid" class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 hidden"></div>

                    <!-- Empty / Closed State message -->
                    <div id="slotsEmpty" class="py-8 text-center hidden">
                        <p id="slotsEmptyTitle" class="text-sm font-medium text-slate-800">No slots available</p>
                        <p id="slotsEmptyText" class="text-xs text-slate-500 mt-1">Please select another date on the calendar above.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Contact Form (5 cols) -->
        <div class="lg:col-span-5">
            <div class="bg-white rounded-lg border border-slate-200 p-5 sticky top-20">
                <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">
                    3. Your details
                </h2>

                <form id="appointmentForm" method="POST" action="{{ route('booking.store') }}">
                    @csrf
                    <!-- Hidden booking inputs -->
                    <input type="hidden" name="appointment_date" id="selectedDateInput" value="{{ old('appointment_date', $minDate) }}" required>
                    <input type="hidden" name="time_slot_id" id="selectedSlotInput" value="{{ old('time_slot_id') }}" required>

                    <!-- Selected Schedule Summary Pill -->
                    <div id="selectionSummary" class="mb-4 p-3 rounded-md bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
                        <div>
                            <span class="block font-medium text-slate-900" id="summaryDateLabel">Select a date</span>
                            <span class="text-slate-500" id="summaryTimeLabel">No slot selected yet</span>
                        </div>
                        <span id="summaryStatusBadge" class="text-[11px] text-slate-400 font-medium">Pending selection</span>
                    </div>

                    <div class="space-y-3.5">
                        <div>
                            <label for="customer_name" class="block text-xs font-medium text-slate-700 mb-1">
                                Full name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="customer_name"
                                id="customer_name"
                                value="{{ old('customer_name') }}"
                                required
                                placeholder="Jane Doe"
                                class="w-full text-sm rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                        </div>

                        <div>
                            <label for="customer_email" class="block text-xs font-medium text-slate-700 mb-1">
                                Email address <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="email"
                                name="customer_email"
                                id="customer_email"
                                value="{{ old('customer_email') }}"
                                required
                                placeholder="jane@example.com"
                                class="w-full text-sm rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                        </div>

                        <div>
                            <label for="customer_phone" class="block text-xs font-medium text-slate-700 mb-1">
                                Phone number
                            </label>
                            <input
                                type="tel"
                                name="customer_phone"
                                id="customer_phone"
                                value="{{ old('customer_phone') }}"
                                placeholder="+1 (555) 000-0000"
                                class="w-full text-sm rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-medium text-slate-700 mb-1">
                                Notes (optional)
                            </label>
                            <textarea
                                name="notes"
                                id="notes"
                                rows="2"
                                placeholder="Any details for your appointment..."
                                class="w-full text-sm rounded-md border border-slate-200 px-3 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <button
                        type="submit"
                        id="submitBookingBtn"
                        disabled
                        class="w-full mt-5 py-2.5 px-4 rounded-md text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer">
                        Confirm booking
                    </button>

                    <p class="text-xs text-slate-400 text-center mt-2.5">
                        You will receive an instant reference code.
                    </p>
                </form>
            </div>
        </div>
    </div>

    @include('booking.partials.chatbot')
</div>
@endsection

@push('scripts')
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<style>
/* Clean Minimal FullCalendar Overrides */
.fc {
    --fc-border-color: #f1f5f9;
    --fc-today-bg-color: #f8fafc;
    --fc-page-bg-color: #ffffff;
    --fc-neutral-bg-color: #f8fafc;
    --fc-highlight-color: rgba(15, 23, 42, 0.04);
    font-family: inherit;
    font-size: 12px;
}
.fc .fc-toolbar {
    margin-bottom: 12px !important;
    gap: 8px;
    align-items: center;
}
.fc .fc-toolbar-title {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #0f172a;
}
.fc .fc-button {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #334155 !important;
    box-shadow: none !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    padding: 3px 8px !important;
    text-transform: capitalize !important;
    height: auto !important;
    transition: all 0.15s ease;
}
.fc .fc-button:hover {
    background: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}
.fc .fc-button-active, .fc .fc-button:active {
    background: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
}
.fc .fc-button:disabled {
    opacity: 0.35 !important;
}
.fc th.fc-col-header-cell {
    padding: 6px 0 !important;
    background: transparent;
    border: none !important;
    border-bottom: 1px solid #f1f5f9 !important;
}
.fc .fc-col-header-cell-cushion {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    text-transform: uppercase;
    text-decoration: none !important;
}
.fc .fc-daygrid-day-number {
    font-size: 11px;
    font-weight: 500;
    color: #334155;
    padding: 4px 6px !important;
    text-decoration: none !important;
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    font-weight: 700;
    color: #0f172a;
    text-decoration: underline;
}
.fc .fc-daygrid-day.fc-day-other .fc-daygrid-day-number {
    color: #cbd5e1;
}
.fc .fc-daygrid-day-frame {
    min-height: 58px !important;
    cursor: pointer;
    transition: background 0.15s ease;
}
.fc .fc-daygrid-day:hover .fc-daygrid-day-frame {
    background: #f8fafc;
}
.fc .fc-daygrid-day.selected-day {
    background: #0f172a !important;
}
.fc .fc-daygrid-day.selected-day .fc-daygrid-day-frame {
    background: #0f172a !important;
}
.fc .fc-daygrid-day.selected-day .fc-daygrid-day-number {
    color: #ffffff !important;
}
.fc .fc-daygrid-day.selected-day .fc-avail-open {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    border-color: transparent !important;
}
.fc .fc-daygrid-day.selected-day .fc-avail-closed {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #cbd5e1 !important;
}
.fc .fc-daygrid-day.fc-day-past .fc-daygrid-day-frame {
    opacity: 0.4;
    cursor: not-allowed;
    background: #fafbfc;
}
.fc-avail-badge {
    margin: 1px 4px 3px !important;
    padding: 1px 4px !important;
    border-radius: 4px !important;
    font-size: 10px !important;
    font-weight: 500 !important;
    line-height: 1.2 !important;
    border: 1px solid transparent !important;
    display: block;
    cursor: pointer;
}
.fc-avail-open {
    background: #f0fdf4 !important;
    color: #15803d !important;
    border-color: #dcfce7 !important;
}
.fc-avail-closed {
    background: #f8fafc !important;
    color: #94a3b8 !important;
}
.fc-avail-full {
    background: #fef2f2 !important;
    color: #b91c1c !important;
    border-color: #fee2e2 !important;
}
.fc .fc-scrollgrid {
    border-radius: 6px;
    border: 1px solid #f1f5f9 !important;
    overflow: hidden;
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('selectedDateInput');
        const slotInput = document.getElementById('selectedSlotInput');
        const slotsLoading = document.getElementById('slotsLoading');
        const slotsGrid = document.getElementById('slotsGrid');
        const slotsEmpty = document.getElementById('slotsEmpty');
        const slotsEmptyTitle = document.getElementById('slotsEmptyTitle');
        const slotsEmptyText = document.getElementById('slotsEmptyText');
        const submitBtn = document.getElementById('submitBookingBtn');
        const summaryDateLabel = document.getElementById('summaryDateLabel');
        const summaryTimeLabel = document.getElementById('summaryTimeLabel');
        const summaryStatusBadge = document.getElementById('summaryStatusBadge');
        const slotSelectedDateDisplay = document.getElementById('slotSelectedDateDisplay');
        const calendarEl = document.getElementById('fullCalendarEl');

        let currentDate = dateInput.value || (new Date()).toISOString().split('T')[0];
        let selectedSlotId = slotInput.value || null;
        let selectedSlotTime = null;
        let fullCalendarInstance = null;

        function formatDateForDisplay(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            return d.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function updateSummary() {
            summaryDateLabel.textContent = formatDateForDisplay(currentDate);
            if (selectedSlotTime) {
                summaryTimeLabel.textContent = selectedSlotTime;
                summaryStatusBadge.textContent = 'Ready';
                summaryStatusBadge.className = 'text-[11px] text-emerald-700 font-medium';
                submitBtn.removeAttribute('disabled');
            } else {
                summaryTimeLabel.textContent = 'Please choose a slot';
                summaryStatusBadge.textContent = 'Slot required';
                summaryStatusBadge.className = 'text-[11px] text-slate-400 font-medium';
                submitBtn.setAttribute('disabled', 'disabled');
            }
        }

        function highlightDateInCalendar(dateStr) {
            document.querySelectorAll('.fc-daygrid-day').forEach(el => {
                if (el.dataset.date === dateStr) {
                    el.classList.add('selected-day');
                } else {
                    el.classList.remove('selected-day');
                }
            });
        }

        // ==========================================
        // FullCalendar.js Initialization
        // ==========================================
        if (typeof FullCalendar !== 'undefined' && calendarEl) {
            fullCalendarInstance = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: currentDate,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                height: 'auto',
                contentHeight: 'auto',
                selectable: false,
                dateClick: function(info) {
                    const todayStr = (new Date()).toISOString().split('T')[0];
                    if (info.dateStr < todayStr) {
                        return; // Past date
                    }
                    currentDate = info.dateStr;
                    dateInput.value = info.dateStr;
                    highlightDateInCalendar(info.dateStr);
                    fetchSlots(info.dateStr);
                },
                eventClick: function(info) {
                    const dateStr = info.event.startStr;
                    const todayStr = (new Date()).toISOString().split('T')[0];
                    if (dateStr < todayStr) return;

                    currentDate = dateStr;
                    dateInput.value = dateStr;
                    highlightDateInCalendar(dateStr);
                    fetchSlots(dateStr);
                },
                events: async function(fetchInfo, successCallback, failureCallback) {
                    try {
                        const midTime = (fetchInfo.start.getTime() + fetchInfo.end.getTime()) / 2;
                        const midDate = new Date(midTime);
                        const year = midDate.getFullYear();
                        const month = midDate.getMonth() + 1;

                        const res = await fetch(`/api/month-availability?year=${year}&month=${month}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();

                        const events = [];
                        for (const [dateStr, info] of Object.entries(data.days || {})) {
                            if (info.is_past) continue;

                            let title = '';
                            let className = '';

                            if (!info.is_open) {
                                title = 'Closed';
                                className = 'fc-avail-badge fc-avail-closed';
                            } else if (info.status === 'full') {
                                title = 'Full';
                                className = 'fc-avail-badge fc-avail-full';
                            } else if (info.available_slots > 0) {
                                title = `${info.available_slots} ${info.available_slots === 1 ? 'slot' : 'slots'}`;
                                className = 'fc-avail-badge fc-avail-open';
                            }

                            if (title) {
                                events.push({
                                    id: 'avail-' + dateStr,
                                    title: title,
                                    start: dateStr,
                                    allDay: true,
                                    className: className,
                                    extendedProps: info
                                });
                            }
                        }

                        successCallback(events);
                        setTimeout(() => highlightDateInCalendar(currentDate), 50);
                    } catch (err) {
                        console.error('FullCalendar events fetch error:', err);
                        failureCallback(err);
                    }
                }
            });

            fullCalendarInstance.render();
            setTimeout(() => highlightDateInCalendar(currentDate), 100);
        }

        // ==========================================
        // Slots Fetcher
        // ==========================================
        async function fetchSlots(date) {
            slotsLoading.classList.remove('hidden');
            slotsGrid.classList.add('hidden');
            slotsEmpty.classList.add('hidden');
            slotsGrid.innerHTML = '';
            selectedSlotId = null;
            selectedSlotTime = null;
            slotInput.value = '';
            updateSummary();

            slotSelectedDateDisplay.textContent = formatDateForDisplay(date);

            try {
                const response = await fetch(`/api/available-slots?date=${encodeURIComponent(date)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                slotsLoading.classList.add('hidden');

                if (!data.is_open) {
                    slotsEmptyTitle.textContent = 'Closed on this day';
                    slotsEmptyText.textContent = data.message || 'We do not operate on this day. Please select another date on the calendar.';
                    slotsEmpty.classList.remove('hidden');
                    return;
                }

                if (!data.slots || data.slots.length === 0) {
                    slotsEmptyTitle.textContent = 'No slots available';
                    slotsEmptyText.textContent = data.message || 'All slots for this day are either booked or in the past.';
                    slotsEmpty.classList.remove('hidden');
                    return;
                }

                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `slot-item p-2.5 rounded-md border text-left transition-colors flex items-center justify-between text-xs ${
                        slot.is_available
                            ? 'border-slate-200 bg-white hover:border-slate-400 text-slate-800 cursor-pointer'
                            : 'border-slate-100 bg-slate-50 text-slate-400 opacity-40 cursor-not-allowed'
                    }`;

                    btn.dataset.id = slot.id;
                    btn.dataset.range = slot.time_range;
                    btn.disabled = !slot.is_available;

                    btn.innerHTML = `
                        <span class="font-medium">${slot.start_time} &ndash; ${slot.end_time}</span>
                        <span class="text-[10px] ${slot.is_available ? 'text-slate-500' : 'text-slate-300'}">
                            ${slot.is_available ? `${slot.remaining} left` : 'Full'}
                        </span>
                    `;

                    if (slot.is_available) {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.slot-item').forEach(el => {
                                el.classList.remove('border-slate-900', 'bg-slate-900', 'text-white');
                                el.classList.add('border-slate-200', 'bg-white', 'text-slate-800');
                                const hint = el.querySelector('span:last-child');
                                if (hint) hint.classList.remove('text-slate-300');
                            });
                            btn.classList.remove('border-slate-200', 'bg-white', 'text-slate-800');
                            btn.classList.add('border-slate-900', 'bg-slate-900', 'text-white');
                            const hint = btn.querySelector('span:last-child');
                            if (hint) hint.classList.add('text-slate-300');

                            selectedSlotId = slot.id;
                            selectedSlotTime = slot.time_range;
                            slotInput.value = slot.id;
                            updateSummary();
                        });
                    }

                    slotsGrid.appendChild(btn);
                });

                slotsGrid.classList.remove('hidden');

            } catch (error) {
                console.error('Error fetching slots:', error);
                slotsLoading.classList.add('hidden');
                slotsEmptyTitle.textContent = 'Error loading slots';
                slotsEmptyText.textContent = 'Could not load time slots. Please try again.';
                slotsEmpty.classList.remove('hidden');
            }
        }

        // Fetch initial slots
        fetchSlots(currentDate);

        // ==========================================
        // Chatbot Widget Logic
        // ==========================================
        const chatbotToggleBtn = document.getElementById('chatbotToggleBtn');
        const chatbotPanel = document.getElementById('chatbotPanel');
        const chatbotCloseBtn = document.getElementById('chatbotCloseBtn');
        const chatbotClearBtn = document.getElementById('chatbotClearBtn');
        const chatbotMessages = document.getElementById('chatbotMessages');
        const chatbotTyping = document.getElementById('chatbotTyping');
        const chatbotForm = document.getElementById('chatbotForm');
        const chatbotInput = document.getElementById('chatbotInput');
        const chatbotSendBtn = document.getElementById('chatbotSendBtn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        let chatHistory = [];

        const welcomeHtml = `
            <div class="chat-bot-msg">
                <div class="max-w-[85%] bg-white border border-slate-200 rounded-lg rounded-tl-none px-3 py-2.5 text-sm text-slate-700 leading-relaxed">
                    <p>Hi there! I can help you check slots, look up existing bookings, or schedule a new appointment. Try asking:</p>
                    <div class="flex flex-wrap gap-1.5 mt-2" id="chatbotQuickPills">
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="What slots are available today?">Slots today</button>
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="What slots are available tomorrow?">Slots tomorrow</button>
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="Check booking reference APT-">Check booking</button>
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="I want to book an appointment">Book appointment</button>
                    </div>
                </div>
            </div>
        `;

        function toggleChatbot(show) {
            if (!chatbotPanel) return;
            const isHidden = chatbotPanel.classList.contains('hidden');
            const shouldOpen = (typeof show === 'boolean') ? show : isHidden;

            if (shouldOpen) {
                chatbotPanel.classList.remove('hidden');
                chatbotPanel.classList.add('flex');
                setTimeout(() => {
                    if (chatbotInput) chatbotInput.focus();
                }, 100);
                scrollChatToBottom();
            } else {
                chatbotPanel.classList.add('hidden');
                chatbotPanel.classList.remove('flex');
            }
        }

        if (chatbotToggleBtn) chatbotToggleBtn.addEventListener('click', () => toggleChatbot());
        if (chatbotCloseBtn) chatbotCloseBtn.addEventListener('click', () => toggleChatbot(false));

        function scrollChatToBottom() {
            if (chatbotMessages) {
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
        }

        function attachQuickPillListeners() {
            document.querySelectorAll('.quick-pill').forEach(pill => {
                pill.addEventListener('click', function() {
                    const prompt = this.dataset.prompt;
                    if (prompt) {
                        if (prompt.endsWith('APT-')) {
                            if (chatbotInput) {
                                chatbotInput.value = prompt;
                                chatbotInput.focus();
                            }
                            return;
                        }
                        if (chatbotInput) chatbotInput.value = prompt;
                        sendUserMessage(prompt);
                    }
                });
            });
        }
        attachQuickPillListeners();

        if (chatbotClearBtn) {
            chatbotClearBtn.addEventListener('click', function() {
                chatHistory = [];
                chatbotMessages.innerHTML = welcomeHtml;
                attachQuickPillListeners();
            });
        }

        function appendUserMessage(text) {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'flex justify-end';
            msgDiv.innerHTML = `
                <div class="max-w-[85%] bg-slate-900 text-white rounded-lg rounded-tr-none px-3 py-2 text-sm leading-relaxed">
                    ${escapeHtml(text)}
                </div>
            `;
            chatbotMessages.appendChild(msgDiv);
            scrollChatToBottom();
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function formatMarkdown(text) {
            if (!text) return '';
            let escaped = escapeHtml(text);
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-medium text-slate-900">$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
            escaped = escaped.replace(/(?:^|\n)[*-]\s+(.+)/g, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-slate-400">&middot;</span><span>$1</span></div>');
            escaped = escaped.replace(/\n\n/g, '<div class="h-1.5"></div>');
            escaped = escaped.replace(/\n/g, '<br>');
            return escaped;
        }

        function appendBotMessage(reply, date = null, slots = [], booking = null) {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'chat-bot-msg';

            let slotsHtml = '';
            if (slots && slots.length > 0 && date) {
                const formattedDate = formatDateForDisplay(date);
                slotsHtml = `
                    <div class="mt-2.5 pt-2 border-t border-slate-100">
                        <span class="text-xs text-slate-500 block mb-1.5">Available for ${formattedDate}:</span>
                        <div class="flex flex-wrap gap-1.5">
                            ${slots.map(s => `
                                <button
                                    type="button"
                                    class="chatbot-slot-btn text-xs bg-slate-100 hover:bg-slate-900 hover:text-white text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer"
                                    data-date="${date}"
                                    data-id="${s.id}"
                                    data-range="${s.time_range}"
                                >${s.start_time} &ndash; ${s.end_time}</button>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            let bookingHtml = '';
            if (booking && (booking.booking_reference || booking.reference)) {
                const ref = escapeHtml(booking.booking_reference || booking.reference);
                const status = escapeHtml((booking.status || 'confirmed').toUpperCase());
                const dateDisplay = escapeHtml(booking.formatted_date || booking.appointment_date || '');
                const timeRange = escapeHtml(booking.time_range || (booking.start_time ? `${booking.start_time} - ${booking.end_time}` : ''));
                const customerName = escapeHtml(booking.customer_name || '');
                const confirmationUrl = booking.confirmation_url || `/booking/confirmation/${ref}`;

                bookingHtml = `
                    <div class="mt-2.5 pt-2.5 border-t border-slate-100">
                        <div class="bg-slate-50 border border-slate-200 rounded-md p-2.5 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-semibold text-slate-900">${ref}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    ${status}
                                </span>
                            </div>
                            <div class="text-slate-600 space-y-0.5 pt-1">
                                ${customerName ? `<div><span class="text-slate-400">Customer:</span> <span class="font-medium text-slate-800">${customerName}</span></div>` : ''}
                                ${dateDisplay ? `<div><span class="text-slate-400">Date:</span> <span class="font-medium text-slate-800">${dateDisplay}</span></div>` : ''}
                                ${timeRange ? `<div><span class="text-slate-400">Time:</span> <span class="font-medium text-slate-800">${timeRange}</span></div>` : ''}
                            </div>
                            <div class="pt-1.5 border-t border-slate-200">
                                <a href="${confirmationUrl}" class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-900 hover:underline">
                                    View confirmation details &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }

            msgDiv.innerHTML = `
                <div class="max-w-[85%] bg-white border border-slate-200 rounded-lg rounded-tl-none px-3 py-2.5 text-sm text-slate-700 leading-relaxed">
                    <div>${formatMarkdown(reply)}</div>
                    ${slotsHtml}
                    ${bookingHtml}
                </div>
            `;

            chatbotMessages.appendChild(msgDiv);

            msgDiv.querySelectorAll('.chatbot-slot-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (chatbotSendBtn && chatbotSendBtn.disabled) return;
                    const targetDate = this.dataset.date;
                    const targetRange = this.dataset.range || this.textContent.trim();
                    sendUserMessage(`I would like to book the ${targetRange} slot on ${targetDate}`);
                });
            });

            scrollChatToBottom();
        }

        async function sendUserMessage(text) {
            if (!text || !text.trim()) return;

            appendUserMessage(text);
            if (chatbotInput) chatbotInput.value = '';
            if (chatbotInput) chatbotInput.disabled = true;
            if (chatbotSendBtn) chatbotSendBtn.disabled = true;
            if (chatbotTyping) chatbotTyping.classList.remove('hidden');
            scrollChatToBottom();

            try {
                const response = await fetch('/api/chatbot', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        message: text,
                        history: chatHistory
                    })
                });

                const data = await response.json();
                if (chatbotTyping) chatbotTyping.classList.add('hidden');

                if (data.reply) {
                    appendBotMessage(data.reply, data.date, data.slots, data.booking);
                    chatHistory.push({ role: 'user', text: text });
                    chatHistory.push({ role: 'model', text: data.reply });

                    // If appointment was booked via chat, refresh calendar / slots so availability updates
                    if (data.booking && (data.booking.success || data.booking.booking_reference) && typeof fetchSlots === 'function') {
                        fetchSlots(currentDate);
                    }
                } else {
                    appendBotMessage('Sorry, I couldn\'t retrieve slot information right now. Please select a date on the calendar.');
                }
            } catch (error) {
                console.error('Chatbot error:', error);
                if (chatbotTyping) chatbotTyping.classList.add('hidden');
                appendBotMessage('Something went wrong. Please try again or pick a date from the calendar.');
            } finally {
                if (chatbotInput) {
                    chatbotInput.disabled = false;
                    chatbotInput.focus();
                }
                if (chatbotSendBtn) chatbotSendBtn.disabled = false;
                scrollChatToBottom();
            }
        }

        if (chatbotForm) {
            chatbotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (chatbotInput) sendUserMessage(chatbotInput.value);
            });
        }
    });
</script>
@endpush
