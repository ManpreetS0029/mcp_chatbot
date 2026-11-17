<?php

declare(strict_types=1);

namespace App\Services;

use App\Mcp\Tools\BookAppointment;
use App\Mcp\Tools\GetAvailableSlots;
use App\Mcp\Tools\GetBookingDetails;
use Carbon\Carbon;
use Gemini\Data\Content;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\FunctionResponse;
use Gemini\Data\Part;
use Gemini\Data\Schema;
use Gemini\Data\Tool;
use Gemini\Enums\DataType;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\ResponseFactory;
use Throwable;

class ChatbotService
{
    /**
     * Fallback model sequence if primary model encounters temporary limits.
     *
     * @var array<int, string>
     */
    protected array $fallbackModels = [
        'gemini-3.5-flash',
        'gemini-3-flash-preview',
        'gemini-3.1-flash-lite-preview',
    ];

    public function __construct(
        protected GetAvailableSlots $availableSlotsTool,
        protected GetBookingDetails $bookingDetailsTool,
        protected BookAppointment $bookAppointmentTool,
        protected BookingService $bookingService,
    ) {}

    /**
     * Process an incoming chat message and return the conversational reply, slot data, and booking info.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{reply: string, date: ?string, slots: array<int, array<string, mixed>>, booking: ?array<string, mixed>}
     */
    public function sendMessage(string $message, array $history = []): array
    {
        try {
            return $this->processWithGemini($message, $history);
        } catch (Throwable $e) {
            Log::warning('Chatbot primary processing failed, falling back to local handler: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->handleLocalFallback($message);
        }
    }

    /**
     * Process user message through Gemini with function calling tools for slots, lookups, and booking.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{reply: string, date: ?string, slots: array<int, array<string, mixed>>, booking: ?array<string, mixed>}
     */
    protected function processWithGemini(string $message, array $history): array
    {
        $systemInstruction = $this->buildSystemInstruction();
        $tool = $this->buildTool();

        $contents = $this->buildConversationHistory($history, $message);

        $modelsToTry = array_unique(array_filter([
            config('gemini.model', 'gemini-3.5-flash'),
            ...$this->fallbackModels,
        ]));

        $lastException = null;

        foreach ($modelsToTry as $modelName) {
            try {
                $model = Gemini::generativeModel($modelName)
                    ->withSystemInstruction($systemInstruction)
                    ->withTool($tool);

                $conversationContents = $contents;
                $resolvedDate = null;
                $resolvedSlots = [];
                $resolvedBooking = null;

                $maxTurns = 4;
                $turn = 0;

                while ($turn < $maxTurns) {
                    $turn++;
                    $response = $model->generateContent(...$conversationContents);
                    $candidate = $response->candidates[0] ?? null;

                    if (! $candidate) {
                        break;
                    }

                    // Check if model called any tool
                    $functionCall = null;
                    foreach ($candidate->content->parts as $part) {
                        if ($part->functionCall !== null) {
                            $functionCall = $part->functionCall;
                            break;
                        }
                    }

                    if ($functionCall === null) {
                        return [
                            'reply' => $response->text(),
                            'date' => $resolvedDate,
                            'slots' => $resolvedSlots,
                            'booking' => $resolvedBooking,
                        ];
                    }

                    $toolName = str_replace('-', '_', strtolower($functionCall->name));
                    $args = $functionCall->args ?? [];
                    $resultData = [];

                    if ($toolName === 'get_available_slots') {
                        $date = (string) ($args['date'] ?? Carbon::today()->toDateString());
                        $mcpRequest = new McpRequest(['date' => $date]);
                        /** @var ResponseFactory $mcpResponse */
                        $mcpResponse = Container::getInstance()->call(
                            [$this->availableSlotsTool, 'handle'],
                            ['request' => $mcpRequest]
                        );
                        $resultData = $mcpResponse->getStructuredContent();
                        $resolvedDate = (string) ($resultData['date'] ?? $date);
                        $resolvedSlots = (array) ($resultData['available_slots'] ?? []);
                    } elseif ($toolName === 'get_booking_details') {
                        $reference = (string) ($args['booking_reference'] ?? '');
                        $mcpRequest = new McpRequest(['booking_reference' => $reference]);
                        /** @var ResponseFactory $mcpResponse */
                        $mcpResponse = Container::getInstance()->call(
                            [$this->bookingDetailsTool, 'handle'],
                            ['request' => $mcpRequest]
                        );
                        $resultData = $mcpResponse->getStructuredContent();
                        if (($resultData['found'] ?? false) === true) {
                            $resolvedBooking = $resultData;
                            if (! empty($resultData['appointment_date'])) {
                                $resolvedDate = (string) $resultData['appointment_date'];
                            }
                        }
                    } elseif ($toolName === 'book_appointment') {
                        $mcpRequest = new McpRequest($args);
                        /** @var ResponseFactory $mcpResponse */
                        $mcpResponse = Container::getInstance()->call(
                            [$this->bookAppointmentTool, 'handle'],
                            ['request' => $mcpRequest]
                        );
                        $resultData = $mcpResponse->getStructuredContent();
                        if (($resultData['success'] ?? false) === true) {
                            $resolvedBooking = $resultData;
                            if (! empty($resultData['appointment_date'])) {
                                $resolvedDate = (string) $resultData['appointment_date'];
                            }
                        }
                    } else {
                        $resultData = ['error' => "Unknown tool name: {$toolName}"];
                    }

                    $functionResponsePart = new Part(
                        functionResponse: new FunctionResponse(
                            name: $functionCall->name,
                            response: $resultData
                        )
                    );

                    $functionResponseContent = new Content(
                        parts: [$functionResponsePart],
                        role: Role::USER
                    );

                    $conversationContents[] = $candidate->content;
                    $conversationContents[] = $functionResponseContent;
                }

                $replyText = isset($response) ? $response->text() : '';

                return [
                    'reply' => $replyText,
                    'date' => $resolvedDate,
                    'slots' => $resolvedSlots,
                    'booking' => $resolvedBooking,
                ];
            } catch (Throwable $e) {
                $lastException = $e;
                Log::notice("Gemini model [{$modelName}] failed: ".$e->getMessage());

                continue;
            }
        }

        throw $lastException ?? new \RuntimeException('All Gemini models failed to generate content.');
    }

    /**
     * Build the system instruction Content for Gemini.
     */
    protected function buildSystemInstruction(): Content
    {
        $today = Carbon::today()->toDateString();
        $dayOfWeek = Carbon::today()->format('l');
        $currentTime = Carbon::now()->format('g:i A');

        $prompt = <<<EOT
You are the friendly, professional virtual scheduling assistant for "AppointEase" (an appointment booking service).
Today's Date: {$dayOfWeek}, {$today}.
Current Time: {$currentTime}.

Business Operating Schedule:
- Monday through Saturday: 9:00 AM to 5:00 PM.
- Sunday: Closed.

Core Objectives & Capabilities:
1. CHECK APPOINTMENT AVAILABILITY:
   - When the user asks about availability for ANY date (such as "today", "tomorrow", "this Friday", "next Monday", or a specific date like "Nov 15" or "2026-11-04"):
     * ALWAYS compute the exact target date in YYYY-MM-DD format based on today's date ({$today}).
     * Call the `get_available_slots` tool with the computed `date`.
   - If closed on that day (e.g. Sundays), politely inform the user and suggest alternative open days.
   - If available slots exist, list the time ranges in a concise, friendly tone using bullet points.
   - If all slots are full or passed, offer to check upcoming dates.

2. GET BOOKING DETAILS BY REFERENCE NUMBER:
   - When the user asks to check, track, find, or get a booking by providing a reference number (such as "APT-XXXXXXXX" or asks "check my booking APT-12345678"):
     * Extract the reference code.
     * Call the `get_booking_details` tool with `booking_reference`.
     * If found, clearly summarize the booking details:
       - Booking Reference: **APT-XXXXXXXX**
       - Status: Confirmed (or relevant status)
       - Customer: Customer Name
       - Date & Time: Formatted date and time range
       - Inform them they can view confirmation anytime.
     * If not found, kindly let the user know that no appointment was found with that reference code, and offer to help check another code or make a new booking.

3. MAKE A BOOKING DIRECTLY IN CHAT:
   - You can schedule and confirm an appointment directly for the customer in the chat!
   - Required information to complete a booking:
     * Appointment date (YYYY-MM-DD)
     * Time slot (either a slot start time/range like "10:00 AM" or a time_slot_id)
     * Customer full name
     * Customer email address
   - Optional information: customer phone number, notes.
   - Flow:
     * If the user wants to book but hasn't specified date or time, call `get_available_slots` to show available times and ask which one they prefer.
     * If date and time are chosen but name or email is missing, ask for their full name and email address.
     * When date, time, customer name, and customer email are provided, call the `book_appointment` tool.
     * If `book_appointment` returns success:
       - Enthusiastically confirm their appointment!
       - Highlight the assigned Booking Reference (e.g., **APT-XXXXXXXX**).
       - Summarize the booked date, time range, and customer name.
       - Mention that a confirmation email is recorded and they can track their appointment using the reference code.
     * If `book_appointment` returns failure (e.g., slot filled, closed day):
       - Politely explain the issue and suggest alternative open slots or dates.

Tone & Formatting:
- Keep your tone cheerful, concise, and helpful.
- Format dates nicely (e.g. Wednesday, November 4, 2026) and times (e.g. 10:00 AM - 11:00 AM).
- Use markdown bolding and bullet points for clear readability.
EOT;

        return new Content(
            parts: [new Part(text: $prompt)]
        );
    }

    /**
     * Build the Gemini Tool schema using the registered tools.
     */
    protected function buildTool(): Tool
    {
        return new Tool(
            functionDeclarations: [
                new FunctionDeclaration(
                    name: 'get_available_slots',
                    description: 'Get available appointment time slots on a given date (YYYY-MM-DD).',
                    parameters: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'date' => new Schema(
                                type: DataType::STRING,
                                description: 'The date to check in YYYY-MM-DD format.'
                            ),
                        ],
                        required: ['date']
                    )
                ),
                new FunctionDeclaration(
                    name: 'get_booking_details',
                    description: 'Look up existing appointment details by booking reference code (e.g. APT-XXXXXXXX).',
                    parameters: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'booking_reference' => new Schema(
                                type: DataType::STRING,
                                description: 'The unique booking reference code, e.g. APT-XXXXXXXX.'
                            ),
                        ],
                        required: ['booking_reference']
                    )
                ),
                new FunctionDeclaration(
                    name: 'book_appointment',
                    description: 'Book an appointment for a customer once their name, email, date, and preferred time or slot are provided.',
                    parameters: new Schema(
                        type: DataType::OBJECT,
                        properties: [
                            'appointment_date' => new Schema(
                                type: DataType::STRING,
                                description: 'The appointment date in YYYY-MM-DD format.'
                            ),
                            'customer_name' => new Schema(
                                type: DataType::STRING,
                                description: 'The customer full name.'
                            ),
                            'customer_email' => new Schema(
                                type: DataType::STRING,
                                description: 'The customer email address.'
                            ),
                            'time_slot_id' => new Schema(
                                type: DataType::INTEGER,
                                description: 'The ID of the time slot to book (optional if time is provided).'
                            ),
                            'time' => new Schema(
                                type: DataType::STRING,
                                description: 'The preferred start time or time range, e.g. "10:00 AM" or "10:00".'
                            ),
                            'customer_phone' => new Schema(
                                type: DataType::STRING,
                                description: 'The customer phone number (optional).'
                            ),
                            'notes' => new Schema(
                                type: DataType::STRING,
                                description: 'Additional notes or requirements (optional).'
                            ),
                        ],
                        required: ['appointment_date', 'customer_name', 'customer_email']
                    )
                ),
            ]
        );
    }

    /**
     * Build Content objects from previous conversation history and current message.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array<int, Content>
     */
    protected function buildConversationHistory(array $history, string $currentMessage): array
    {
        $contents = [];

        foreach ($history as $item) {
            $roleStr = strtolower($item['role'] ?? 'user');
            $text = trim($item['text'] ?? '');

            if ($text === '') {
                continue;
            }

            $role = match ($roleStr) {
                'model', 'assistant' => Role::MODEL,
                default => Role::USER,
            };

            $contents[] = new Content(
                parts: [new Part(text: $text)],
                role: $role
            );
        }

        // Add current user prompt
        $contents[] = new Content(
            parts: [new Part(text: $currentMessage)],
            role: Role::USER
        );

        return $contents;
    }

    /**
     * Fallback parser using local tools if Gemini API is temporarily unavailable.
     *
     * @return array{reply: string, date: ?string, slots: array<int, array<string, mixed>>, booking: ?array<string, mixed>}
     */
    protected function handleLocalFallback(string $message): array
    {
        $lower = strtolower($message);

        // 1. Check for booking reference lookup (e.g. APT-XXXXXXXX)
        if (preg_match('/\b(APT-[A-Z0-9]{4,16})\b/i', $message, $refMatches)) {
            $ref = strtoupper($refMatches[1]);
            $appointment = $this->bookingService->findBookingByReference($ref);

            if ($appointment) {
                return [
                    'reply' => "Here are the details for booking **{$appointment->booking_reference}**:\n\n"
                        .'- **Status:** '.ucfirst($appointment->status)."\n"
                        ."- **Customer:** {$appointment->customer_name}\n"
                        ."- **Date:** {$appointment->formatted_date}\n"
                        ."- **Time:** {$appointment->time_range}\n"
                        ."- **Email:** {$appointment->customer_email}\n\n"
                        .'You can view your booking details anytime at [Confirmation Page]('.route('booking.confirmation', $appointment->booking_reference).').',
                    'date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : null,
                    'slots' => [],
                    'booking' => [
                        'found' => true,
                        'booking_reference' => $appointment->booking_reference,
                        'customer_name' => $appointment->customer_name,
                        'customer_email' => $appointment->customer_email,
                        'customer_phone' => $appointment->customer_phone,
                        'appointment_date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : null,
                        'formatted_date' => $appointment->formatted_date,
                        'time_range' => $appointment->time_range,
                        'status' => $appointment->status,
                        'status_color' => $appointment->status_color,
                        'confirmation_url' => route('booking.confirmation', $appointment->booking_reference),
                    ],
                ];
            }

            return [
                'reply' => "I could not find an appointment with reference code **{$ref}**. Please verify the reference code and try again.",
                'date' => null,
                'slots' => [],
                'booking' => null,
            ];
        }

        // 2. Check for direct booking intent in message
        $hasBookWord = str_contains($lower, 'book') || str_contains($lower, 'reserve') || str_contains($lower, 'schedule');
        $hasEmail = preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $emailMatches);

        if ($hasBookWord && $hasEmail) {
            $email = $emailMatches[0];
            $targetDate = null;
            if (str_contains($lower, 'today')) {
                $targetDate = Carbon::today();
            } elseif (str_contains($lower, 'tomorrow')) {
                $targetDate = Carbon::tomorrow();
            } elseif (preg_match('/(\d{4}-\d{2}-\d{2})/', $message, $dateMatches)) {
                try {
                    $targetDate = Carbon::parse($dateMatches[1]);
                } catch (Throwable) {
                    $targetDate = null;
                }
            }

            // Extract customer name if "for <Name>"
            $customerName = 'Customer';
            if (preg_match('/for\s+([A-Za-z\s]+?)(?:,|\.|\sat\s|\swith\s|email|\s[a-zA-Z0-9._%+-]+@)/i', $message, $nameMatches)) {
                $customerName = trim($nameMatches[1]);
            }

            // Extract time if provided
            $time = null;
            if (preg_match('/(?:at\s+)?(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i', $message, $timeMatches)) {
                $time = trim($timeMatches[1]);
            }

            if ($targetDate !== null) {
                $bookingResult = $this->bookingService->bookAppointment([
                    'appointment_date' => $targetDate->format('Y-m-d'),
                    'customer_name' => $customerName,
                    'customer_email' => $email,
                    'time' => $time,
                ]);

                if ($bookingResult['success']) {
                    return [
                        'reply' => "🎉 **Booking Confirmed!**\n\nYour appointment has been successfully scheduled:\n\n"
                            ."- **Booking Reference:** **{$bookingResult['booking_reference']}**\n"
                            ."- **Customer:** {$bookingResult['customer_name']}\n"
                            ."- **Date:** {$bookingResult['formatted_date']}\n"
                            ."- **Time:** {$bookingResult['time_range']}\n"
                            ."- **Email:** {$bookingResult['customer_email']}\n\n"
                            .'You can view full confirmation at [Confirmation Page]('.$bookingResult['confirmation_url'].').',
                        'date' => $bookingResult['appointment_date'] ?? $targetDate->format('Y-m-d'),
                        'slots' => [],
                        'booking' => $bookingResult,
                    ];
                }

                return [
                    'reply' => $bookingResult['message'].' You can pick a specific time slot below:',
                    'date' => $targetDate->format('Y-m-d'),
                    'slots' => $this->availableSlotsTool->handle(
                        new McpRequest(['date' => $targetDate->format('Y-m-d')]),
                        Container::getInstance()->make(SlotService::class)
                    )->getStructuredContent()['available_slots'] ?? [],
                    'booking' => null,
                ];
            }
        }

        if ($hasBookWord && ! $hasEmail) {
            $targetDate = null;
            if (str_contains($lower, 'today')) {
                $targetDate = Carbon::today();
            } elseif (str_contains($lower, 'tomorrow')) {
                $targetDate = Carbon::tomorrow();
            } elseif (preg_match('/(\d{4}-\d{2}-\d{2})/', $message, $dateMatches)) {
                try {
                    $targetDate = Carbon::parse($dateMatches[1]);
                } catch (Throwable) {
                    $targetDate = null;
                }
            }

            if ($targetDate !== null) {
                $time = null;
                if (preg_match('/(\d{1,2}(?::\d{2})?\s*(?:am|pm)?(?:\s*-\s*\d{1,2}(?::\d{2})?\s*(?:am|pm)?)?)/i', $message, $timeMatches)) {
                    $time = trim($timeMatches[1]);
                }

                $timeText = $time ? " for **{$time}**" : '';
                $dateText = $targetDate->format('l, F j, Y');

                return [
                    'reply' => "I would be happy to help you book that appointment{$timeText} on **{$dateText}**! Please reply with your **full name** and **email address** (e.g. *Book for Jane Doe, jane@example.com*).",
                    'date' => $targetDate->format('Y-m-d'),
                    'slots' => [],
                    'booking' => null,
                ];
            }
        }

        // 3. Slot availability check
        $targetDate = null;

        if (str_contains($lower, 'today')) {
            $targetDate = Carbon::today();
        } elseif (str_contains($lower, 'tomorrow')) {
            $targetDate = Carbon::tomorrow();
        } elseif (preg_match('/(\d{4}-\d{2}-\d{2})/', $message, $matches)) {
            try {
                $targetDate = Carbon::parse($matches[1]);
            } catch (Throwable) {
                $targetDate = null;
            }
        } else {
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                if (str_contains($lower, $day)) {
                    $targetDate = Carbon::parse("next {$day}");
                    break;
                }
            }
        }

        if ($targetDate === null) {
            return [
                'reply' => "I'm here to help! You can:\n- Ask for available appointment slots (e.g. *What slots are available tomorrow?*)\n- Look up an existing booking (e.g. *Check booking APT-XXXXXXXX*)\n- Book an appointment directly (e.g. *Book tomorrow at 10:00 AM for Jane, jane@example.com*)",
                'date' => null,
                'slots' => [],
                'booking' => null,
            ];
        }

        $dateStr = $targetDate->toDateString();

        // Invoke MCP tool
        $mcpRequest = new McpRequest(['date' => $dateStr]);
        /** @var ResponseFactory $mcpResponse */
        $mcpResponse = Container::getInstance()->call(
            [$this->availableSlotsTool, 'handle'],
            ['request' => $mcpRequest]
        );

        $mcpData = $mcpResponse->getStructuredContent();
        $isClosed = (bool) ($mcpData['is_closed'] ?? false);
        $slots = (array) ($mcpData['available_slots'] ?? []);
        $formattedDate = (string) ($mcpData['formatted_date'] ?? $targetDate->format('l, F j, Y'));

        if ($isClosed) {
            return [
                'reply' => "We are closed on {$targetDate->format('l')}s ({$formattedDate}). Please check another date such as tomorrow or an upcoming weekday.",
                'date' => $dateStr,
                'slots' => [],
                'booking' => null,
            ];
        }

        if (empty($slots)) {
            return [
                'reply' => "There are currently no open slots available for {$formattedDate}. Please select another date from the calendar.",
                'date' => $dateStr,
                'slots' => [],
                'booking' => null,
            ];
        }

        $count = count($slots);
        $slotList = implode(', ', array_map(fn ($s) => $s['time_range'], array_slice($slots, 0, 5)));
        if ($count > 5) {
            $slotList .= ' and more';
        }

        return [
            'reply' => "We have {$count} available slot(s) for {$formattedDate}: {$slotList}. You can click a slot below to select it or ask me to book it for you!",
            'date' => $dateStr,
            'slots' => $slots,
            'booking' => null,
        ];
    }
}
