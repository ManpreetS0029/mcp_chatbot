<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\BookingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('This tool books an appointment for a customer on a given date and time slot.')]
class BookAppointment extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, BookingService $bookingService): Response|ResponseFactory
    {
        $data = [
            'appointment_date' => (string) $request->get('appointment_date'),
            'time_slot_id' => $request->get('time_slot_id') ? (int) $request->get('time_slot_id') : null,
            'time' => $request->get('time') ? (string) $request->get('time') : null,
            'customer_name' => (string) $request->get('customer_name'),
            'customer_email' => (string) $request->get('customer_email'),
            'customer_phone' => $request->get('customer_phone') ? (string) $request->get('customer_phone') : null,
            'notes' => $request->get('notes') ? (string) $request->get('notes') : null,
        ];

        $result = $bookingService->bookAppointment($data);

        // Omit the Eloquent model instance before returning structured content
        unset($result['appointment']);

        return Response::structured($result);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'appointment_date' => $schema->string()
                ->description('The appointment date in YYYY-MM-DD format.')
                ->required(),
            'customer_name' => $schema->string()
                ->description('The full name of the customer.')
                ->required(),
            'customer_email' => $schema->string()
                ->description('The email address of the customer.')
                ->required(),
            'time_slot_id' => $schema->integer()
                ->description('The ID of the time slot to book (optional if time is provided).'),
            'time' => $schema->string()
                ->description('The preferred start time or time range, e.g. "10:00 AM" or "10:00".'),
            'customer_phone' => $schema->string()
                ->description('The customer phone number (optional).'),
            'notes' => $schema->string()
                ->description('Any optional customer notes or reason for appointment.'),
        ];
    }
}

