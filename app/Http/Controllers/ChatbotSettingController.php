<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstitutionSetting;
use App\Models\ChatbotKeyword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChatbotSettingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Ensure user has permission to manage settings
        $this->setPageTitle(__('chatbot.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        
        // 1. Get Keywords
        $keywords = ChatbotKeyword::where('institution_id', $institutionId)
            ->latest()
            ->get();

        // 2. Get Settings
        $keys = [
            'chatbot_session_timeout', 
            'chatbot_enable_whatsapp', 
            'chatbot_enable_sms', 
            'chatbot_enable_telegram'
        ];
        
        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->whereIn('key', $keys)
            ->pluck('value', 'key');
            
        $config = [
            'timeout'  => $settings['chatbot_session_timeout'] ?? 15,
            'whatsapp' => $settings['chatbot_enable_whatsapp'] ?? 0,
            'sms'      => $settings['chatbot_enable_sms'] ?? 0,
            'telegram' => $settings['chatbot_enable_telegram'] ?? 0,
        ];

        return view('chatbot.settings', compact('keywords', 'config'));
    }

    public function updateConfig(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $request->validate([
            'chatbot_session_timeout' => 'required|integer|min:1|max:1440',
            'chatbot_enable_whatsapp' => 'nullable|boolean',
            'chatbot_enable_sms' => 'nullable|boolean',
            'chatbot_enable_telegram' => 'nullable|boolean',
        ]);

        $settings = [
            'chatbot_session_timeout' => $request->chatbot_session_timeout,
            'chatbot_enable_whatsapp' => $request->has('chatbot_enable_whatsapp') ? 1 : 0,
            'chatbot_enable_sms' => $request->has('chatbot_enable_sms') ? 1 : 0,
            'chatbot_enable_telegram' => $request->has('chatbot_enable_telegram') ? 1 : 0,
        ];

        foreach ($settings as $key => $value) {
            InstitutionSetting::updateOrCreate(
                ['institution_id' => $institutionId, 'key' => $key],
                ['value' => $value, 'group' => 'chatbot']
            );
        }

        return back()->with('success', __('chatbot.config_updated'));
    }

    public function storeKeyword(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'keyword' => [
                'required', 'string', 'max:50',
                Rule::unique('chatbot_keywords')->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId);
                })
            ],
            'language' => 'required|in:en,fr',
            'welcome_message' => 'required|string|max:1000',
        ]);

        ChatbotKeyword::create([
            'institution_id' => $institutionId,
            'keyword' => strtolower(trim($request->keyword)),
            'language' => $request->language,
            'welcome_message' => $request->welcome_message
        ]);

        return back()->with('success', __('chatbot.keyword_created'));
    }

    public function updateKeyword(Request $request, $id)
    {
        $institutionId = $this->getInstitutionId();
        $keyword = ChatbotKeyword::where('institution_id', $institutionId)->findOrFail($id);

        $request->validate([
            'keyword' => [
                'required', 'string', 'max:50',
                Rule::unique('chatbot_keywords')->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId);
                })->ignore($id)
            ],
            'language' => 'required|in:en,fr',
            'welcome_message' => 'required|string|max:1000',
        ]);

        $keyword->update([
            'keyword' => strtolower(trim($request->keyword)),
            'language' => $request->language,
            'welcome_message' => $request->welcome_message
        ]);

        return back()->with('success', __('chatbot.keyword_updated'));
    }

    public function destroyKeyword($id)
    {
        $institutionId = $this->getInstitutionId();
        $keyword = ChatbotKeyword::where('institution_id', $institutionId)->findOrFail($id);
        $keyword->delete();

        return back()->with('success', __('chatbot.keyword_deleted'));
    }
}