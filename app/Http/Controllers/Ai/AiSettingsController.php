<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\BaseController;
use App\Models\AiUsageLog;
use App\Models\InstitutionSetting;
use App\Services\Ai\AiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

/**
 * Platform-level AI configuration (Super Admin only).
 */
class AiSettingsController extends BaseController
{
    public function __construct(protected AiManager $ai)
    {
        parent::__construct();
        $this->middleware('auth');
    }

    public function index()
    {
        $this->ensureSuperAdmin();
        $this->setPageTitle(__('ai.settings_title'));

        $hasKey = !empty(InstitutionSetting::get(null, 'ai_api_key')) || !empty(config('ai.api_key'));

        $period = now()->format('Y-m');
        $usage = [
            'requests'   => AiUsageLog::where('period', $period)->where('status', 'success')->count(),
            'tokens'     => (int) AiUsageLog::where('period', $period)->sum('total_tokens'),
            'blocked'    => AiUsageLog::where('period', $period)->where('status', 'blocked')->count(),
            'errors'     => AiUsageLog::where('period', $period)->where('status', 'error')->count(),
        ];

        $topConsumers = AiUsageLog::selectRaw('institution_id, COUNT(*) as total')
            ->where('period', $period)
            ->where('status', 'success')
            ->whereNotNull('institution_id')
            ->groupBy('institution_id')
            ->orderByDesc('total')
            ->with('institution:id,name')
            ->limit(10)
            ->get();

        return view('ai.settings', [
            'enabled'      => config('ai.enabled'),
            'hasKey'       => $hasKey,
            'model'        => InstitutionSetting::get(null, 'ai_model') ?: config('ai.model'),
            'baseUrl'      => InstitutionSetting::get(null, 'ai_base_url') ?: config('ai.base_url'),
            'usage'        => $usage,
            'topConsumers' => $topConsumers,
            'period'       => $period,
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'api_key'  => 'nullable|string|max:300',
            'model'    => 'nullable|string|max:100',
            'base_url' => 'nullable|string|max:255',
        ]);

        if (!empty($data['api_key'])) {
            InstitutionSetting::set(null, 'ai_api_key', Crypt::encryptString($data['api_key']), 'ai');
        }
        if (array_key_exists('model', $data)) {
            InstitutionSetting::set(null, 'ai_model', $data['model'] ?: config('ai.model'), 'ai');
        }
        if (array_key_exists('base_url', $data)) {
            InstitutionSetting::set(null, 'ai_base_url', $data['base_url'] ?: config('ai.base_url'), 'ai');
        }

        return back()->with('success', __('ai.settings_saved'));
    }

    public function clearKey()
    {
        $this->ensureSuperAdmin();
        InstitutionSetting::where('institution_id', null)->where('key', 'ai_api_key')->delete();

        return back()->with('success', __('ai.key_cleared'));
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->hasRole('Super Admin'), 403);
    }
}
