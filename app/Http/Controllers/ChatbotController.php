<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotController extends Controller
{
    /**
     * Handle incoming chatbot message.
     */
    public function chat(Request $request, ChatbotService $chatbotService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:5000'],
        ]);

        try {
            $result = $chatbotService->sendMessage(
                message: $validated['message'],
                history: $validated['history'] ?? []
            );

            return response()->json([
                'success' => true,
                'reply' => $result['reply'],
                'date' => $result['date'],
                'slots' => $result['slots'],
                'booking' => $result['booking'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('ChatbotController exception: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'reply' => 'I encountered an unexpected issue while checking the schedule. Please choose a date directly on the calendar or try again in a few moments.',
                'date' => null,
                'slots' => [],
                'booking' => null,
            ], 500);
        }
    }
}
