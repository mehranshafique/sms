<?php

namespace App\Http\Controllers\Api\V1\Voice;

use App\Http\Controllers\Controller;
use App\Services\ChatbotWebhookVerifier;
use App\Services\Voice\InfobipCmlBuilder;
use App\Services\Voice\VoiceAiAgentService;
use App\Services\Voice\VoiceIdentityService;
use App\Services\Voice\VoiceMenuService;
use App\Services\Voice\VoiceSessionService;
use App\Services\Voice\VoiceSpeechService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InfobipVoiceWebhookController extends Controller
{
    public function __construct(
        protected ChatbotWebhookVerifier $verifier,
        protected VoiceSessionService $sessions,
        protected VoiceIdentityService $identity,
        protected VoiceMenuService $menus,
        protected VoiceSpeechService $speech,
        protected VoiceAiAgentService $agent,
        protected InfobipCmlBuilder $cml
    ) {
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'digitex-voice-ivr',
            'time' => now()->toIso8601String(),
        ]);
    }

    public function inbound(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request, 'infobip')) {
            Log::warning('Voice inbound webhook rejected: verification failed');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $this->normalizePayload($request);
        Log::info('Voice inbound', [
            'call_id' => $payload['call_id'],
            'from' => $payload['from'],
            'to' => $payload['to'],
        ]);

        if ($payload['call_id'] === '' || $payload['from'] === '') {
            return response()->json($this->cml->hangup());
        }

        $institutionId = $this->identity->resolveInstitutionIdFromBotNumber($payload['to']);
        $locale = $institutionId
            ? $this->identity->defaultLocale($institutionId)
            : 'fr';

        $session = $this->sessions->startOrResume(
            $payload['call_id'],
            $payload['from'],
            $payload['to'],
            $institutionId,
            $locale
        );

        if (! $institutionId || ! $this->identity->isVoiceEnabledForInstitution($institutionId)) {
            $this->sessions->end($session, 'module_disabled');
            app()->setLocale($locale);

            return response()->json(
                $this->cml->sayAndHangup(__('voice.disabled', [], $locale), $locale)
            );
        }

        app()->setLocale($session->locale);

        return response()->json($this->menus->welcome($session));
    }

    public function dtmf(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request, 'infobip')) {
            Log::warning('Voice DTMF webhook rejected: verification failed');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $this->normalizePayload($request);
        $digit = $payload['digit'];

        Log::info('Voice DTMF', [
            'call_id' => $payload['call_id'],
            'digit' => $digit,
        ]);

        $session = $payload['call_id'] !== ''
            ? $this->sessions->findByCallId($payload['call_id'])
            : null;

        if (! $session) {
            return response()->json(
                $this->cml->sayAndHangup(__('voice.session_missing', [], 'fr'), 'fr')
            );
        }

        if ($session->isEnded()) {
            return response()->json($this->cml->hangup());
        }

        if (! $this->identity->isVoiceEnabledForInstitution($session->institution_id)) {
            $this->sessions->end($session, 'module_disabled');
            $locale = $session->locale ?: 'fr';

            return response()->json(
                $this->cml->sayAndHangup(__('voice.disabled', [], $locale), $locale)
            );
        }

        app()->setLocale($session->locale);

        return response()->json($this->menus->handleDigit($session, $digit));
    }

    /**
     * Phase 3: callback of the CML `record` action. The caller's spoken question
     * is transcribed and answered, then the keypad menu resumes.
     */
    public function recording(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request, 'infobip')) {
            Log::warning('Voice recording webhook rejected: verification failed');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $this->normalizePayload($request);
        $session = $payload['call_id'] !== ''
            ? $this->sessions->findByCallId($payload['call_id'])
            : null;

        if (! $session) {
            return response()->json(
                $this->cml->sayAndHangup(__('voice.session_missing', [], 'fr'), 'fr')
            );
        }

        app()->setLocale($session->locale);

        if (! $this->identity->isVoiceEnabledForInstitution($session->institution_id)) {
            $this->sessions->end($session, 'module_disabled');

            return response()->json(
                $this->cml->sayAndHangup(__('voice.disabled', [], $session->locale), $session->locale)
            );
        }

        Log::info('Voice recording received', [
            'call_id' => $session->call_id,
            'file_id' => $payload['file_id'],
        ]);

        $transcript = null;
        if ($this->agent->isAvailableFor($session)) {
            $result = $this->speech->transcribeRecording(
                $session->institution_id,
                $payload['file_id'] ?: null,
                $payload['file_url'] ?: null,
                $session->locale
            );
            $transcript = $result['ok'] ? $result['text'] : null;
        }

        return response()->json($this->menus->handleTranscribedQuestion($session, $transcript));
    }

    /**
     * Phase 4: callback of the CML `dial` action once the agent leg finished.
     */
    public function transfer(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request, 'infobip')) {
            Log::warning('Voice transfer webhook rejected: verification failed');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $this->normalizePayload($request);
        $session = $payload['call_id'] !== ''
            ? $this->sessions->findByCallId($payload['call_id'])
            : null;

        Log::info('Voice transfer finished', [
            'call_id' => $payload['call_id'],
            'event' => $payload['event'],
        ]);

        if (! $session) {
            return response()->json($this->cml->hangup());
        }

        app()->setLocale($session->locale);
        $this->sessions->end($session, 'transfer_completed');

        return response()->json(
            $this->cml->sayAndHangup(__('voice.goodbye', [], $session->locale), $session->locale)
        );
    }

    public function status(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request, 'infobip')) {
            Log::warning('Voice status webhook rejected: verification failed');

            return response()->json(['status' => 'unauthorized'], 401);
        }

        $payload = $this->normalizePayload($request);
        Log::info('Voice status', [
            'call_id' => $payload['call_id'],
            'event' => $payload['event'],
        ]);

        if ($payload['call_id'] !== '') {
            $session = $this->sessions->findByCallId($payload['call_id']);
            if ($session && ! $session->isEnded()) {
                $this->sessions->end($session, $payload['event'] ?: 'status');
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array{call_id: string, from: string, to: string, digit: string, event: string, file_id: string, file_url: string}
     */
    protected function normalizePayload(Request $request): array
    {
        $data = $request->all();

        $callId = (string) (
            $data['callId']
            ?? $data['call_id']
            ?? data_get($data, 'call.id')
            ?? data_get($data, 'results.0.callId')
            ?? ''
        );

        $from = (string) (
            $data['from']
            ?? data_get($data, 'endpoint.phoneNumber')
            ?? data_get($data, 'from.phoneNumber')
            ?? data_get($data, 'results.0.from')
            ?? ''
        );

        $to = (string) (
            $data['to']
            ?? data_get($data, 'to.phoneNumber')
            ?? data_get($data, 'results.0.to')
            ?? ''
        );

        $digit = (string) (
            $data['digit']
            ?? $data['digits']
            ?? $data['dtmf']
            ?? data_get($data, 'capturedDtmf')
            ?? data_get($data, 'dtmf.digit')
            ?? ''
        );

        if ($digit === '' && $request->boolean('timedOut')) {
            $digit = 'timeout';
        }

        $event = (string) (
            $data['event']
            ?? $data['status']
            ?? data_get($data, 'errorCode.name')
            ?? ''
        );

        $fileId = (string) (
            $data['fileId']
            ?? $data['file_id']
            ?? data_get($data, 'recording.fileId')
            ?? data_get($data, 'recording.files.0.fileId')
            ?? ''
        );

        $fileUrl = (string) (
            $data['fileUrl']
            ?? $data['recordingUrl']
            ?? data_get($data, 'recording.fileUrl')
            ?? data_get($data, 'recording.files.0.fileUrl')
            ?? ''
        );

        return [
            'call_id' => $callId,
            'from' => $this->identity->digits($from) ?: $from,
            'to' => $this->identity->digits($to) ?: $to,
            'digit' => trim($digit),
            'event' => $event,
            'file_id' => $fileId,
            'file_url' => $fileUrl,
        ];
    }
}
