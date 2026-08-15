<?php

namespace App\Http\Controllers;

use App\Models\InstitutionSetting;
use App\Models\VoiceParentPin;
use App\Models\VoiceSession;
use App\Services\InstitutionModuleAccessService;
use App\Services\Voice\VoiceAiAgentService;
use App\Services\Voice\VoiceIdentityService;
use App\Services\Voice\VoicePinService;
use App\Services\Voice\VoiceTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class VoiceSettingController extends BaseController
{
    public function __construct(
        protected InstitutionModuleAccessService $moduleAccess,
        protected VoicePinService $pins,
        protected VoiceIdentityService $identity,
        protected VoiceAiAgentService $agent
    ) {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':voice_ivr.view|setting.view|setting.manage')
            ->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':voice_ivr.manage|setting.manage')
            ->only(['store', 'storePin', 'destroyPin']);
        $this->setPageTitle(__('voice.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        $moduleEnabled = $institutionId
            ? $this->moduleAccess->isModuleEnabled((int) $institutionId, 'voice_ivr')
            : false;

        $config = [
            'enabled' => InstitutionSetting::get($institutionId, 'voice_ivr_enabled', '1') === '1',
            'locale' => InstitutionSetting::get($institutionId, 'voice_ivr_locale_default', 'fr'),
            'max_turns' => (int) InstitutionSetting::get($institutionId, 'voice_ivr_max_turns', 8),
            'secretary' => InstitutionSetting::get($institutionId, 'voice_ivr_secretary_message', ''),
            'pin_required' => InstitutionSetting::get($institutionId, 'voice_ivr_parent_pin_required', '0') === '1',
            'ai_enabled' => InstitutionSetting::get($institutionId, 'voice_ivr_ai_enabled', '0') === '1',
            'ai_guest_enabled' => InstitutionSetting::get($institutionId, 'voice_ivr_ai_guest_enabled', '0') === '1',
            'ai_max_questions' => (int) InstitutionSetting::get($institutionId, 'voice_ivr_ai_max_questions', 3),
            'transfer_enabled' => InstitutionSetting::get($institutionId, 'voice_ivr_transfer_enabled', '0') === '1',
            'transfer_endpoint_type' => InstitutionSetting::get($institutionId, 'voice_ivr_transfer_endpoint_type', 'whatsapp'),
            'transfer_identity' => InstitutionSetting::get($institutionId, 'voice_ivr_transfer_identity', ''),
            'transfer_hours_start' => InstitutionSetting::get($institutionId, 'voice_ivr_transfer_hours_start', ''),
            'transfer_hours_end' => InstitutionSetting::get($institutionId, 'voice_ivr_transfer_hours_end', ''),
        ];

        $baseUrl = rtrim(config('app.url'), '/');
        $secret = config('services.chatbot.webhook_secret');
        $secretQuery = $secret ? '?secret=' . urlencode($secret) : '';
        $webhookUrls = [
            'inbound' => $baseUrl . '/api/v1/voice/infobip/inbound' . $secretQuery,
            'dtmf' => $baseUrl . '/api/v1/voice/infobip/dtmf' . $secretQuery,
            'recording' => $baseUrl . '/api/v1/voice/infobip/recording' . $secretQuery,
            'transfer' => $baseUrl . '/api/v1/voice/infobip/transfer' . $secretQuery,
            'status' => $baseUrl . '/api/v1/voice/infobip/status' . $secretQuery,
            'health' => $baseUrl . '/api/v1/voice/infobip/health',
        ];

        $sessions = VoiceSession::query()
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->latest('id')
            ->limit(25)
            ->get();

        $parentPins = VoiceParentPin::with('parent')
            ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->latest('updated_at')
            ->limit(50)
            ->get();

        $aiReady = $institutionId ? $this->agent->isEnabled((int) $institutionId) : false;
        $endpointTypes = VoiceTransferService::ALLOWED_ENDPOINTS;

        return view('voice.settings', compact(
            'config',
            'webhookUrls',
            'moduleEnabled',
            'sessions',
            'parentPins',
            'aiReady',
            'endpointTypes'
        ));
    }

    public function store(Request $request)
    {
        $institutionId = $this->requireEnabledInstitution();
        if (! is_int($institutionId)) {
            return $institutionId;
        }

        $request->validate([
            'locale' => 'required|in:en,fr',
            'max_turns' => 'required|integer|min:3|max:30',
            'secretary' => 'nullable|string|max:500',
            'ai_max_questions' => 'required|integer|min:1|max:10',
            'transfer_endpoint_type' => 'required|in:' . implode(',', VoiceTransferService::ALLOWED_ENDPOINTS),
            'transfer_identity' => 'nullable|string|max:120',
            'transfer_hours_start' => 'nullable|date_format:H:i',
            'transfer_hours_end' => 'nullable|date_format:H:i',
        ]);

        $values = [
            'voice_ivr_enabled' => $request->boolean('enabled') ? '1' : '0',
            'voice_ivr_locale_default' => $request->locale,
            'voice_ivr_max_turns' => (string) $request->max_turns,
            'voice_ivr_secretary_message' => (string) $request->input('secretary', ''),
            'voice_ivr_parent_pin_required' => $request->boolean('pin_required') ? '1' : '0',
            'voice_ivr_ai_enabled' => $request->boolean('ai_enabled') ? '1' : '0',
            'voice_ivr_ai_guest_enabled' => $request->boolean('ai_guest_enabled') ? '1' : '0',
            'voice_ivr_ai_max_questions' => (string) $request->ai_max_questions,
            'voice_ivr_transfer_enabled' => $request->boolean('transfer_enabled') ? '1' : '0',
            'voice_ivr_transfer_endpoint_type' => $request->transfer_endpoint_type,
            'voice_ivr_transfer_identity' => (string) $request->input('transfer_identity', ''),
            'voice_ivr_transfer_hours_start' => (string) $request->input('transfer_hours_start', ''),
            'voice_ivr_transfer_hours_end' => (string) $request->input('transfer_hours_end', ''),
        ];

        foreach ($values as $key => $value) {
            InstitutionSetting::set($institutionId, $key, $value, 'voice');
        }

        return back()->with('success', __('voice.settings.saved'));
    }

    public function storePin(Request $request)
    {
        $institutionId = $this->requireEnabledInstitution();
        if (! is_int($institutionId)) {
            return $institutionId;
        }

        $request->validate([
            'phone' => 'required|string|max:32',
            'pin' => 'required|digits:4',
        ]);

        $parent = $this->identity->findParent($institutionId, $request->phone);

        if (! $parent) {
            return back()->with('error', __('voice.settings.pin_parent_missing'));
        }

        $this->pins->setPin($institutionId, $parent, $request->pin, Auth::id());

        $label = $parent->guardian_name ?: ($parent->father_name ?: ($parent->mother_name ?: ('#' . $parent->id)));

        return back()->with('success', __('voice.settings.pin_saved', ['parent' => $label]));
    }

    public function destroyPin(int $parentId)
    {
        $institutionId = $this->requireEnabledInstitution();
        if (! is_int($institutionId)) {
            return $institutionId;
        }

        $this->pins->clearPin($institutionId, $parentId);

        return back()->with('success', __('voice.settings.pin_removed'));
    }

    /**
     * @return int|\Illuminate\Http\RedirectResponse
     */
    protected function requireEnabledInstitution()
    {
        $institutionId = $this->getInstitutionId();

        if (! $institutionId) {
            abort(422, 'Select an institution.');
        }

        if (! $this->moduleAccess->isModuleEnabled((int) $institutionId, 'voice_ivr')) {
            return back()->with('error', __('voice.settings.module_off'));
        }

        return (int) $institutionId;
    }
}
