<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ChatbotBaseController extends Controller
{
    /**
     * Standard success response
     */
    protected function sendResponse($data, $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
            'meta'    => [
                'version' => '1.0',
                'timestamp' => now()->toDateTimeString(),
            ]
        ], $code);
    }

    /**
     * Standard error response.
     * Allows passing a custom HTTP code (e.g. 200 for empty logic vs 404 for missing resource).
     */
    protected function sendError($error, $code = 404, $errorMessages = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}