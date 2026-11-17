<!-- Chatbot Widget -->
<div id="chatbotWidget" class="fixed bottom-5 right-5 z-50">
    <!-- Toggle Button -->
    <button
        type="button"
        id="chatbotToggleBtn"
        aria-label="Ask AI for Slots"
        class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-full shadow-lg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
        <span class="hidden sm:inline">Ask AI for Slots</span>
    </button>

    <!-- Chat Panel -->
    <div
        id="chatbotPanel"
        class="hidden fixed bottom-16 right-4 sm:right-5 w-[calc(100vw-2rem)] sm:w-96 h-[32rem] max-h-[calc(100vh-5rem)] bg-white rounded-lg shadow-xl border border-slate-200 flex-col overflow-hidden z-50">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 leading-tight">AppointEase Assistant</h3>
                <p class="text-xs text-slate-500 leading-tight">Ask AI for slots, lookups &amp; bookings</p>
            </div>
            <div class="flex items-center gap-0.5">
                <button
                    type="button"
                    id="chatbotClearBtn"
                    title="New conversation"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
                <button
                    type="button"
                    id="chatbotCloseBtn"
                    title="Close"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div id="chatbotMessages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50">
            <!-- Welcome -->
            <div class="chat-bot-msg">
                <div class="max-w-[85%] bg-white border border-slate-200 rounded-lg rounded-tl-none px-3 py-2.5 text-sm text-slate-700 leading-relaxed">
                    <p>Hi there! I can help you check slots, look up existing bookings, or schedule a new appointment. Try asking:</p>
                    <div class="flex flex-wrap gap-1.5 mt-2" id="chatbotQuickPills">
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="What slots are available today?">
                            Slots today
                        </button>
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="What slots are available tomorrow?">
                            Slots tomorrow
                        </button>
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="Check my booking reference APT-">
                            Check booking
                        </button>
                        <button type="button" class="quick-pill text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-md border border-slate-200 transition-colors cursor-pointer" data-prompt="I want to book an appointment">
                            Book appointment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="chatbotTyping" class="hidden px-4 py-2 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-400">
            <span class="flex gap-1">
                <span class="w-1 h-1 rounded-full bg-slate-400 animate-bounce"></span>
                <span class="w-1 h-1 rounded-full bg-slate-400 animate-bounce [animation-delay:0.15s]"></span>
                <span class="w-1 h-1 rounded-full bg-slate-400 animate-bounce [animation-delay:0.3s]"></span>
            </span>
        </div>

        <!-- Input -->
        <form id="chatbotForm" class="p-3 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0">
            <input
                type="text"
                id="chatbotInput"
                placeholder="Ask about available slots..."
                autocomplete="off"
                maxlength="500"
                class="flex-1 text-sm bg-slate-50 border border-slate-200 rounded-md px-3 py-2 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 transition-colors">
            <button
                type="submit"
                id="chatbotSendBtn"
                aria-label="Send"
                class="p-2 rounded-md bg-slate-900 hover:bg-slate-800 text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7l7 7-7 7"></path>
                </svg>
            </button>
        </form>
    </div>
</div>
