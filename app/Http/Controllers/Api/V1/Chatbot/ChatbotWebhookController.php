<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatbotLogicService;
use Illuminate\Support\Facades\Log;

class ChatbotWebhookController extends Controller
{
    protected $botService;

    public function __construct(ChatbotLogicService $botService)
    {
        $this->botService = $botService;
    }

    public function handle(Request $request, $provider)
    {
        try {
            // 1. LOG INPUT
            Log::info("Webhook Hit [$provider]", [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $data = null;

            switch (strtolower($provider)) {
                case 'infobip':
                    $data = $this->parseInfobip($request);
                    break;
                case 'twilio':
                    $data = $this->parseTwilio($request);
                    break;
                case 'meta':
                    $data = $this->parseMeta($request);
                    break;
                case 'mobishastra':
                    $data = $this->parseMobishastra($request);
                    break;
            }

            if ($data) {
                Log::info("Webhook Parsed & Validated:", $data);
                $response = $this->botService->processMessage($data);
                return $response; 
            }

            Log::warning("Webhook Ignored: Validation failed or empty payload.");
            return response()->json(['status' => 'ignored', 'message' => 'Validation failed: Invalid phone number or empty body.'], 200);

        } catch (\Exception $e) {
            Log::error("Chatbot Webhook Error ($provider): " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to validate and normalize data
     * @param string|null $from The User's phone number (Sender)
     * @param string|null $to   The Bot/System's phone number (Receiver)
     * @param string|null $body The message content
     * @param string $provider
     */
    private function validateAndFormat($from, $to, $body, $provider)
    {
        // 1. Validate Content
        if (empty(trim($body ?? ''))) {
            Log::warning("[$provider] Validation Failed: Empty message body.");
            return null;
        }

        // 2. Validate 'From' (User/Sender) - Mandatory
        $cleanFrom = $this->cleanPhoneNumber($from);
        if (!$cleanFrom) {
            Log::warning("[$provider] Validation Failed: Invalid 'from' number ($from). Must include country code (cannot start with 0).");
            return null;
        }

        // 3. Validate 'To' (Bot/Receiver) - Optional existence
        $cleanTo = null;
        if (!empty($to)) {
            $cleanTo = $this->cleanPhoneNumber($to);
        }

        return [
            'from' => $cleanFrom, // Correctly mapped to User
            'to' => $cleanTo,     // Correctly mapped to System
            'body' => trim($body),
            'provider' => $provider
        ];
    }

    /**
     * Regex validation for International Numbers (E.164-like)
     * Accepts: +1234567890, 1234567890
     * Rejects: 03001234567 (Local format without country code)
     */
    private function cleanPhoneNumber($number)
    {
        if (empty($number)) return null;

        // Remove Twilio prefix if present
        $number = str_replace('whatsapp:', '', $number);
        
        // Remove spaces, dashes, parentheses
        $number = preg_replace('/[^0-9+]/', '', $number);

        // Rule: Optional +, Starts with 1-9 (Country Code), 7-14 following digits (Total 8-15)
        if (preg_match('/^\+?[1-9]\d{7,14}$/', $number)) {
            return $number;
        }
        
        return null;
    }

    private function parseInfobip($request)
    {
        $results = $request->input('results');
        
        // Logic A: Standard Array Structure (Webhook)
        if (!empty($results) && is_array($results) && isset($results[0])) {
            $msg = $results[0];
            
            // FIX: Reverted to standard mapping. 
            // Infobip 'from' is the User. Infobip 'to' is the System.
            return $this->validateAndFormat(
                $msg['from'] ?? null, // User
                $msg['to'] ?? null,   // Bot
                $msg['message']['text'] ?? $msg['cleanText'] ?? null,
                'infobip'
            );
        }
        
        // Logic B: Direct Object
        if ($request->has('message')) {
            return $this->validateAndFormat(
                $request->input('from'), // User
                $request->input('to'),   // Bot
                $request->input('message.text'),
                'infobip'
            );
        }

        return null;
    }

    private function parseTwilio($request)
    {
        // Twilio Standard: From = User, To = Bot
        return $this->validateAndFormat(
            $request->input('From'),
            $request->input('To'),
            $request->input('Body'),
            'twilio'
        );
    }

    private function parseMeta($request)
    {
        $entry = $request->input('entry.0');
        $changes = $entry['changes'][0]['value'] ?? null;
        
        if (isset($changes['messages'][0])) {
            $msg = $changes['messages'][0];
            
            // Meta Standard: From = User, metadata.display_phone_number = Bot
            $to = $changes['metadata']['display_phone_number'] ?? $changes['metadata']['phone_number_id'] ?? null;
            
            return $this->validateAndFormat(
                $msg['from'] ?? null,
                $to,
                $msg['text']['body'] ?? null,
                'meta'
            );
        }
        return null;
    }

    private function parseMobishastra($request)
    {
        // Mobishastra Standard: mobile/from = User, to/receiver = Bot
        $to = $request->input('to') ?? $request->input('receiver') ?? $request->input('shortcode');
        
        return $this->validateAndFormat(
            $request->input('mobile') ?? $request->input('from'),
            $to,
            $request->input('message') ?? $request->input('text'),
            'mobishastra'
        );
    }
}