<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use App\Services\ChatbotLogicService;
use Illuminate\Http\Request;

/**
 * Legacy normalized webhook entry (POST /api/v1/chatbot/webhook).
 * Forwards to ChatbotLogicService — same engine as /webhook/{provider}.
 */
class ChatbotInteractionController extends Controller
{
    public function __construct(
        protected ChatbotLogicService $botService
    ) {}

    public function handleWebhook(Request $request)
    {
        $from = $request->input('from');
        $body = trim((string) ($request->input('text') ?? $request->input('body') ?? ''));

        if (!$from || $body === '') {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload: from and text/body required'], 422);
        }

        return $this->botService->processMessage([
            'from' => $from,
            'body' => $body,
        ]);
    }
}
