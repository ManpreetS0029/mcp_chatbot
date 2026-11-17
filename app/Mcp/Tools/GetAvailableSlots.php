<?php

namespace App\Mcp\Tools;

use App\Services\SlotService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('This tool helps to get available slots on a given date.')]
class GetAvailableSlots extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, SlotService $slotService): Response|ResponseFactory
    {
        $date = (string) $request->get('date');

        $availability = $slotService->getAvailabilityDetails($date);

        return Response::structured([
            'date' => $availability['date'],
            'formatted_date' => $availability['formatted_date'],
            'is_open' => $availability['is_open'],
            'is_closed' => $availability['is_closed'],
            'message' => $availability['message'],
            'available_slots' => $availability['slots']->toArray(),
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
            'date' => $schema->string()
                ->description('The date to check in YYYY-MM-DD Format.')
                ->required(),
        ];
    }
}
