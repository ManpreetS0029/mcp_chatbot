<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('This tool looks up appointment details by booking reference code (e.g. APT-XXXXXXXX).')]
class GetBookingDetails extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, BookingService $bookingService): Response|ResponseFactory
    {
        $reference = trim((string) $request->get('booking_reference'));
        $appointment = $bookingService->findBookingByReference($reference);

        if (! $appointment) {
            return Response::structured([
                'found' => false,
                'booking_reference' => $reference,
                'message' => "No appointment was found with booking reference '{$reference}'. Please check the reference code.",
            ]);
        }

        return Response::structured([
            'found' => true,
            'booking_reference' => $appointment->booking_reference,
            'customer_name' => $appointment->customer_name,
            'customer_email' => $appointment->customer_email,
            'customer_phone' => $appointment->customer_phone,
            'appointment_date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : null,
            'formatted_date' => $appointment->formatted_date,
            'start_time' => Carbon::parse($appointment->start_time)->format('g:i A'),
            'end_time' => Carbon::parse($appointment->end_time)->format('g:i A'),
            'time_range' => $appointment->time_range,
            'status' => $appointment->status,
            'status_color' => $appointment->status_color,
            'notes' => $appointment->notes,
            'confirmation_url' => route('booking.confirmation', $appointment->booking_reference),
            'message' => 'Appointment details retrieved successfully.',
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'booking_reference' => $schema->string()
                ->description('The unique appointment booking reference code, e.g. APT-XXXXXXXX.')
                ->required(),
        ];
    }
}

