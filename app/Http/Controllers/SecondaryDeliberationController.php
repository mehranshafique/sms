<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\SecondaryDeliberation;
use App\Services\SecondaryDeliberationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecondaryDeliberationController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('secondary_deliberation.page_title'));
    }

    public function index()
    {
        $this->authorizeAdminOrPermission('academic_report.view');
        $institutionId = $this->getInstitutionId();
        $session = AcademicSession::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->where('is_current', true)
            ->first();

        $deliberations = collect();
        if ($session) {
            $deliberations = SecondaryDeliberation::with(['student', 'classSection.gradeLevel', 'decidedBy'])
                ->where('institution_id', $institutionId)
                ->where('academic_session_id', $session->id)
                ->orderBy('id')
                ->get();
        }

        return view('secondary_deliberations.index', compact('deliberations', 'session'));
    }

    public function generate(SecondaryDeliberationService $service)
    {
        $this->authorizeAdminOrPermission('academic_report.view');
        $context = $this->context();
        $count = $service->generate($context['institution_id'], $context['session_id']);

        return back()->with('success', __('secondary_deliberation.generated', ['count' => $count]));
    }

    public function saveDecisions(Request $request, SecondaryDeliberationService $service)
    {
        $this->authorizeAdminOrPermission('academic_report.view');
        $context = $this->context();

        $payload = $request->validate([
            'decisions' => 'required|array',
            'decisions.*.id' => 'required|integer',
            'decisions.*.decision' => 'required|in:pending,admitted,repechage,adjourned',
            'decisions.*.notes' => 'nullable|string|max:500',
        ]);

        $ids = collect($payload['decisions'])->pluck('id');
        $owned = SecondaryDeliberation::where('institution_id', $context['institution_id'])
            ->where('academic_session_id', $context['session_id'])
            ->whereIn('id', $ids)
            ->pluck('id');

        $rows = collect($payload['decisions'])
            ->filter(fn ($row) => $owned->contains((int) $row['id']))
            ->all();

        $updated = $service->saveDecisions($rows, (int) Auth::id());

        return back()->with('success', __('secondary_deliberation.decisions_saved', ['count' => $updated]));
    }

    public function confirmAndNotify(SecondaryDeliberationService $service)
    {
        $this->authorizeAdminOrPermission('academic_report.view');
        $context = $this->context();
        $count = $service->confirmAndNotify($context['institution_id'], $context['session_id']);

        if ($count === 0) {
            return back()->with('info', __('secondary_deliberation.nothing_to_notify'));
        }

        return back()->with('success', __('secondary_deliberation.notified', ['count' => $count]));
    }

    /**
     * @return array{institution_id: int, session_id: int}
     */
    protected function context(): array
    {
        $institutionId = (int) $this->getInstitutionId();
        if (! $institutionId) {
            abort(403);
        }

        $sessionId = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        if (! $sessionId) {
            abort(422, __('settings.no_current_session'));
        }

        return ['institution_id' => $institutionId, 'session_id' => (int) $sessionId];
    }
}
