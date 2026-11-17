<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (WorkingDay::DAYS_OF_WEEK as $day) {
            WorkingDay::create([
                'day_of_week' => $day,
                'is_open' => ! in_array($day, ['sunday'], true),
            ]);
        }
    }

    public function test_booking_page_renders_chatbot_widget_and_elements(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('chatbotWidget');
        $response->assertSee('Ask AI for Slots');
        $response->assertSee('AppointEase Assistant');
    }

    public function test_chat_endpoint_validates_required_message(): void
    {
        $response = $this->postJson('/api/chatbot', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_chat_endpoint_validates_history_format(): void
    {
        $response = $this->postJson('/api/chatbot', [
            'message' => 'Hello',
            'history' => [
                [
                    'role' => 'invalid_role_name',
                    'text' => 'Some text',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['history.0.role']);
    }

    public function test_chatbot_can_answer_greeting_without_tool_calls(): void
    {
        $greetingResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Hello! I can help you check available appointment slots. Which date would you like to check?',
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => null,
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 5,
                'totalTokenCount' => 15,
            ],
        ]);

        Gemini::fake([
            $greetingResponse,
        ]);

        $response = $this->postJson('/api/chatbot', [
            'message' => 'Hello there!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'reply' => 'Hello! I can help you check available appointment slots. Which date would you like to check?',
            'date' => null,
            'slots' => [],
        ]);
    }

    public function test_chatbot_queries_slots_via_gemini_function_calling(): void
    {
        $targetDate = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        $slot = TimeSlot::create([
            'day_of_week' => 'wednesday',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 2,
            'is_active' => true,
        ]);

        $functionCallResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => null,
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => [
                                    'name' => 'get_available_slots',
                                    'args' => ['date' => $targetDate],
                                ],
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'totalTokenCount' => 20,
            ],
        ]);

        $secondTurnSummaryResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => "We have 1 available slot on {$targetDate}: 10:00 AM - 11:00 AM.",
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => null,
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 15,
                'totalTokenCount' => 30,
            ],
        ]);

        Gemini::fake([
            $functionCallResponse,
            $secondTurnSummaryResponse,
        ]);

        $response = $this->postJson('/api/chatbot', [
            'message' => "Are there any slots on {$targetDate}?",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'date' => $targetDate,
        ]);
        $response->assertJsonFragment([
            'id' => $slot->id,
            'time_range' => '10:00 AM - 11:00 AM',
            'remaining_capacity' => 2,
        ]);
    }

    public function test_chatbot_gracefully_falls_back_when_gemini_service_fails(): void
    {
        $targetDate = Carbon::tomorrow()->format('Y-m-d');
        $dayOfWeek = strtolower(Carbon::tomorrow()->format('l'));

        $slot = TimeSlot::create([
            'day_of_week' => $dayOfWeek,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        // Don't register any fake responses so Gemini throws an exception or mock fail
        Gemini::fake([]);

        $response = $this->postJson('/api/chatbot', [
            'message' => 'What slots are available tomorrow?',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'date' => $targetDate,
        ]);
        $response->assertJsonFragment([
            'id' => $slot->id,
            'time_range' => '2:00 PM - 3:00 PM',
        ]);
    }

    public function test_chatbot_can_lookup_booking_via_gemini_function_calling(): void
    {
        $targetDate = Carbon::now()->next(Carbon::THURSDAY)->format('Y-m-d');
        $slot = TimeSlot::create([
            'day_of_week' => 'thursday',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'capacity' => 2,
            'is_active' => true,
        ]);

        Appointment::create([
            'booking_reference' => 'APT-TESTLOOKUP',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'appointment_date' => $targetDate,
            'time_slot_id' => $slot->id,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $functionCallResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => null,
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => [
                                    'name' => 'get_booking_details',
                                    'args' => ['booking_reference' => 'APT-TESTLOOKUP'],
                                ],
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'totalTokenCount' => 20,
            ],
        ]);

        $summaryResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Your booking APT-TESTLOOKUP is confirmed for John Doe on Thursday.',
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => null,
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 15,
                'totalTokenCount' => 30,
            ],
        ]);

        Gemini::fake([
            $functionCallResponse,
            $summaryResponse,
        ]);

        $response = $this->postJson('/api/chatbot', [
            'message' => 'Check my booking APT-TESTLOOKUP',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'booking' => [
                'found' => true,
                'booking_reference' => 'APT-TESTLOOKUP',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
            ],
        ]);
    }

    public function test_chatbot_can_make_booking_via_gemini_function_calling(): void
    {
        $targetDate = Carbon::now()->next(Carbon::FRIDAY)->format('Y-m-d');
        $slot = TimeSlot::create([
            'day_of_week' => 'friday',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        $functionCallResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => null,
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => [
                                    'name' => 'book_appointment',
                                    'args' => [
                                        'appointment_date' => $targetDate,
                                        'time_slot_id' => $slot->id,
                                        'customer_name' => 'Jane Smith',
                                        'customer_email' => 'jane@example.com',
                                    ],
                                ],
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 10,
                'totalTokenCount' => 20,
            ],
        ]);

        $confirmationResponse = GenerateContentResponse::from([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => 'Great news! Your appointment has been booked successfully.',
                                'inlineData' => null,
                                'fileData' => null,
                                'functionCall' => null,
                                'functionResponse' => null,
                            ],
                        ],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'safetyRatings' => [],
                    'index' => 0,
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 15,
                'totalTokenCount' => 30,
            ],
        ]);

        Gemini::fake([
            $functionCallResponse,
            $confirmationResponse,
        ]);

        $response = $this->postJson('/api/chatbot', [
            'message' => "Book appointment for {$targetDate} at 2:00 PM for Jane Smith, jane@example.com",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'booking' => [
                'success' => true,
                'customer_name' => 'Jane Smith',
                'customer_email' => 'jane@example.com',
                'appointment_date' => $targetDate,
            ],
        ]);

        $appointment = Appointment::where('customer_email', 'jane@example.com')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('Jane Smith', $appointment->customer_name);
        $this->assertEquals($targetDate, $appointment->appointment_date->format('Y-m-d'));
        $this->assertEquals($slot->id, $appointment->time_slot_id);
        $this->assertEquals(Appointment::STATUS_CONFIRMED, $appointment->status);
    }

    public function test_chatbot_fallback_can_lookup_booking_by_reference(): void
    {
        $targetDate = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $slot = TimeSlot::create([
            'day_of_week' => 'monday',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'capacity' => 1,
            'is_active' => true,
        ]);

        Appointment::create([
            'booking_reference' => 'APT-FALLBACKREF',
            'customer_name' => 'Bob Builder',
            'customer_email' => 'bob@example.com',
            'appointment_date' => $targetDate,
            'time_slot_id' => $slot->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        // Empty fake triggers fallback
        Gemini::fake([]);

        $response = $this->postJson('/api/chatbot', [
            'message' => 'Can you look up my appointment APT-FALLBACKREF?',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'booking' => [
                'found' => true,
                'booking_reference' => 'APT-FALLBACKREF',
                'customer_name' => 'Bob Builder',
            ],
        ]);
        $response->assertSee('APT-FALLBACKREF');
    }

    public function test_chatbot_fallback_can_book_appointment(): void
    {
        $targetDate = Carbon::tomorrow()->format('Y-m-d');
        $dayOfWeek = strtolower(Carbon::tomorrow()->format('l'));

        $slot = TimeSlot::create([
            'day_of_week' => $dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 2,
            'is_active' => true,
        ]);

        // Empty fake triggers fallback
        Gemini::fake([]);

        $response = $this->postJson('/api/chatbot', [
            'message' => 'Please book tomorrow at 10:00 AM for Alice Wonderland, alice@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'booking' => [
                'success' => true,
                'customer_email' => 'alice@example.com',
            ],
        ]);

        $appointment = Appointment::where('customer_email', 'alice@example.com')->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($targetDate, $appointment->appointment_date->format('Y-m-d'));
        $this->assertEquals(Appointment::STATUS_CONFIRMED, $appointment->status);
    }

    public function test_chatbot_fallback_prompts_for_contact_details_when_slot_selected_without_email(): void
    {
        $targetDate = Carbon::tomorrow()->format('Y-m-d');

        // Empty fake triggers fallback
        Gemini::fake([]);

        $response = $this->postJson('/api/chatbot', [
            'message' => "I would like to book the 10:00 AM - 11:00 AM slot on {$targetDate}",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'date' => $targetDate,
            'booking' => null,
        ]);
        $response->assertSee('full name');
        $response->assertSee('email address');
    }
}
