<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Services\AssessmentPeriodService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AssessmentPeriodController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function close(Request $request, AssessmentPeriodService $periods)
    {
        $this->authorizeManage();
        $context = $this->context();
        $key = $this->validatedKey($request);

        $periods->close($context['institution_id'], $context['session_id'], $key);

        return $this->ok($request, __('settings.period_closed', ['period' => $periods->label($key)]));
    }

    public function reopen(Request $request, AssessmentPeriodService $periods)
    {
        $this->authorizeManage();
        $context = $this->context();
        $key = $this->validatedKey($request);
        $reason = trim((string) $request->input('reopen_reason', ''));

        if ($reason === '') {
            $msg = __('settings.reopen_reason_required');
            if ($request->ajax()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $periods->reopen($context['institution_id'], $context['session_id'], $key, $reason);

        return $this->ok($request, __('settings.period_reopened', ['period' => $periods->label($key)]));
    }

    public function schedule(Request $request, AssessmentPeriodService $periods)
    {
        $this->authorizeManage();
        $context = $this->context();
        $key = $this->validatedKey($request);

        $closesAt = $request->filled('closes_at')
            ? Carbon::parse($request->input('closes_at'))
            : null;

        $periods->scheduleClose($context['institution_id'], $context['session_id'], $key, $closesAt);

        return $this->ok($request, __('settings.period_schedule_saved', ['period' => $periods->label($key)]));
    }

    protected function authorizeManage(): void
    {
        $this->authorizeAdminOrPermission('setting.manage');
    }

    /**
     * @return array{institution_id: int, session_id: int}
     */
    protected function context(): array
    {
        $institutionId = (int) $this->getInstitutionId();
        if (! $institutionId) {
            abort(403, __('settings.select_institution_first'));
        }

        $sessionId = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        if (! $sessionId) {
            abort(422, __('settings.no_current_session'));
        }

        return ['institution_id' => $institutionId, 'session_id' => (int) $sessionId];
    }

    protected function validatedKey(Request $request): string
    {
        $data = $request->validate([
            'period_key' => 'required|string|in:' . implode(',', AssessmentPeriodService::PERIOD_KEYS),
        ]);

        return $data['period_key'];
    }

    protected function ok(Request $request, string $message)
    {
        if ($request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
