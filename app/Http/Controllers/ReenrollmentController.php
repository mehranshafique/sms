<?php

namespace App\Http\Controllers;

use App\Jobs\SendReenrollmentInvitationsJob;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\ReenrollmentCampaign;
use App\Models\ReenrollmentConfirmation;
use App\Services\ReenrollmentService;
use App\Support\MarkdownToHtml;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ReenrollmentController extends BaseController
{
    public function __construct(
        protected ReenrollmentService $reenrollments
    ) {
        // Prefer dedicated reenrollment permissions; fall back to promotion perms for existing schools.
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $canView = $user && (
                $user->can('student_reenrollment.view')
                || $user->can('student_promotion.view')
                || $user->can('student_promotion.create')
            );
            if (! $canView) {
                abort(403);
            }

            return $next($request);
        })->only(['index', 'show', 'downloadManual']);

        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $canCreate = $user && (
                $user->can('student_reenrollment.create')
                || $user->can('student_promotion.create')
            );
            if (! $canCreate) {
                abort(403);
            }

            return $next($request);
        })->only([
            'storeCampaign',
            'recordPhysical',
            'sendInvitations',
            'syncStudents',
            'closeCampaign',
            'reopenCampaign',
            'remind',
        ]);

        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $canUpdate = $user && (
                $user->can('student_reenrollment.update')
                || $user->can('student_promotion.create')
            );
            if (! $canUpdate) {
                abort(403);
            }

            return $next($request);
        })->only(['approve', 'reject', 'keepPending', 'updateProposedClass', 'reopen']);

        $this->setPageTitle(__('reenrollment.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (! $institutionId) {
            abort(403, __('reenrollment.errors.no_institution'));
        }

        $status = $request->input('status', 'queue');
        $campaignId = $request->input('campaign_id');

        $campaigns = ReenrollmentCampaign::with(['fromSession', 'toSession'])
            ->where('institution_id', $institutionId)
            ->latest('id')
            ->get();

        $activeCampaign = $campaignId
            ? $campaigns->firstWhere('id', (int) $campaignId)
            : ($campaigns->first(fn ($c) => $c->status === ReenrollmentCampaign::STATUS_OPEN) ?? $campaigns->first());

        $query = $this->buildQuery($request, $institutionId, $activeCampaign?->id)
            ->with([
                'student',
                'fromClassSection.gradeLevel',
                'proposedClassSection.gradeLevel',
                'campaign',
            ]);

        $confirmations = $query->orderByRaw('parent_confirmed_at IS NULL, parent_confirmed_at DESC')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $stats = [];
        if ($activeCampaign) {
            $base = ReenrollmentConfirmation::where('campaign_id', $activeCampaign->id);
            $stats = [
                'concerned' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', ReenrollmentConfirmation::STATUS_PENDING)->count(),
                'queue' => (clone $base)->whereIn('status', ReenrollmentConfirmation::QUEUE_STATUSES)->count(),
                'partial' => (clone $base)->where('status', ReenrollmentConfirmation::STATUS_PARTIAL)->count(),
                'confirmed' => (clone $base)->where('status', ReenrollmentConfirmation::STATUS_CONFIRMED)->count(),
                'declined' => (clone $base)->where('status', ReenrollmentConfirmation::STATUS_DECLINED)->count(),
                'rejected' => (clone $base)->where('status', ReenrollmentConfirmation::STATUS_REJECTED)->count(),
            ];
        }

        $sessions = AcademicSession::where('institution_id', $institutionId)
            ->orderByDesc('start_date')
            ->pluck('name', 'id');

        $notifications = app(\App\Services\NotificationService::class);
        $messagingReady = $notifications->channelEnabled($institutionId, 'reenrollment_invitation', 'whatsapp')
            || $notifications->channelEnabled($institutionId, 'reenrollment_invitation', 'sms');

        return view('reenrollments.index', compact(
            'campaigns',
            'activeCampaign',
            'confirmations',
            'stats',
            'status',
            'sessions',
            'messagingReady'
        ));
    }

    public function downloadManual()
    {
        $path = base_path('doc/markdown/reenrollment-confirmation-help-manual.md');
        abort_unless(File::exists($path), 404);

        $body = MarkdownToHtml::convert(File::get($path));
        $title = 'Re-enrollment Confirmation Help Manual';
        $generatedAt = now()->format('F j, Y');

        return Pdf::loadView('doc.pdf-layout', compact('title', 'body', 'generatedAt'))
            ->setPaper('a4')
            ->download('Re-enrollment-Confirmation-Help-Manual.pdf');
    }

    public function storeCampaign(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (! $institutionId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'from_academic_session_id' => 'required|exists:academic_sessions,id',
            'to_academic_session_id' => 'required|exists:academic_sessions,id|different:from_academic_session_id',
            'min_fee_amount' => 'nullable|numeric|min:0',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after_or_equal:opens_at',
            'notes' => 'nullable|string|max:2000',
        ]);

        $exists = ReenrollmentCampaign::where('institution_id', $institutionId)
            ->where('to_academic_session_id', $data['to_academic_session_id'])
            ->exists();
        if ($exists) {
            return back()->with('error', __('reenrollment.errors.campaign_exists'));
        }

        $campaign = $this->reenrollments->openCampaign($data, $institutionId);

        return redirect()
            ->route('reenrollments.index', ['campaign_id' => $campaign->id])
            ->with('success', __('reenrollment.messages.campaign_opened'));
    }

    /** Notify parents that re-enrollment is open (or nudge the ones who never answered). */
    public function sendInvitations(Request $request, ReenrollmentCampaign $campaign)
    {
        $this->authorizeInstitution($campaign->institution_id);

        $isReminder = $request->input('mode') === 'reminder';

        $eligible = ReenrollmentConfirmation::where('campaign_id', $campaign->id)
            ->where('status', ReenrollmentConfirmation::STATUS_PENDING)
            ->when(! $isReminder, fn ($q) => $q->whereNull('invitation_sent_at'))
            ->when($isReminder, fn ($q) => $q->where(function ($w) {
                $w->whereNull('last_reminder_at')->orWhere('last_reminder_at', '<', now()->subDay());
            }))
            ->count();

        if ($eligible === 0) {
            return back()->with('error', __('reenrollment.errors.nobody_to_notify'));
        }

        SendReenrollmentInvitationsJob::dispatch($campaign->id, $isReminder);

        return back()->with('success', __(
            $isReminder ? 'reenrollment.messages.reminders_queued' : 'reenrollment.messages.invitations_queued',
            ['count' => $eligible]
        ));
    }

    /** Pull in students enrolled after the campaign was opened. */
    public function syncStudents(ReenrollmentCampaign $campaign)
    {
        $this->authorizeInstitution($campaign->institution_id);

        $created = $this->reenrollments->seedConfirmations($campaign);

        return back()->with('success', __('reenrollment.messages.synced', ['count' => $created]));
    }

    public function closeCampaign(ReenrollmentCampaign $campaign)
    {
        $this->authorizeInstitution($campaign->institution_id);
        $this->reenrollments->closeCampaign($campaign);

        return back()->with('success', __('reenrollment.messages.campaign_closed'));
    }

    public function reopenCampaign(ReenrollmentCampaign $campaign)
    {
        $this->authorizeInstitution($campaign->institution_id);
        $this->reenrollments->reopenCampaign($campaign);

        return back()->with('success', __('reenrollment.messages.campaign_reopened'));
    }

    /** Export the current filtered list for office / physical confirmation use. */
    public function export(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (! $institutionId) {
            abort(403);
        }

        $rows = $this->buildQuery($request, $institutionId)
            ->with(['student', 'fromClassSection.gradeLevel', 'proposedClassSection.gradeLevel', 'campaign.toSession'])
            ->get();

        $filename = 'reenrollments-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('reenrollment.admission_no'),
                __('reenrollment.student'),
                __('reenrollment.current_class'),
                __('reenrollment.proposed_class'),
                __('reenrollment.status'),
                __('reenrollment.channel'),
                __('reenrollment.confirmed_at'),
                __('reenrollment.reenroll_fee_required'),
                __('reenrollment.reenroll_fee_paid'),
                __('reenrollment.reenroll_fee_remaining'),
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->student->admission_number ?? '',
                    $row->student->full_name ?? '',
                    class_section_label($row->fromClassSection),
                    class_section_label($row->proposedClassSection),
                    $row->statusLabel(),
                    $row->parent_confirmation_channel ?? '',
                    optional($row->parent_confirmed_at)->format('Y-m-d H:i') ?? '',
                    number_format((float) $row->amount_required, 2, '.', ''),
                    number_format((float) $row->amount_paid, 2, '.', ''),
                    number_format(max(0, (float) $row->amount_required - (float) $row->amount_paid), 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $summary = $this->reenrollments->buildReviewSummary($confirmation);

        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $confirmation->institution_id)
            ->get()
            ->mapWithKeys(function ($item) {
                $grade = $item->gradeLevel->name ?? '';
                $label = $item->name . ($grade ? ' (' . $grade . ')' : '');

                return [$item->id => $label];
            });

        return view('reenrollments.show', [
            'confirmation' => $confirmation->fresh([
                'student',
                'campaign.fromSession',
                'campaign.toSession',
                'fromClassSection.gradeLevel',
                'proposedClassSection.gradeLevel',
                'approvedClassSection.gradeLevel',
                'reviewedBy',
            ]),
            'summary' => $summary,
            'classes' => $classes,
        ]);
    }

    public function recordPhysical(Request $request, ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $data = $request->validate([
            'action' => 'required|in:confirm,decline',
            'parent_note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reenrollments->recordParentConfirmation(
                $confirmation,
                'physical',
                $request->user(),
                $data['parent_note'] ?? null,
                $data['action'] === 'decline'
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('reenrollment.messages.physical_recorded'));
    }

    public function approve(Request $request, ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $data = $request->validate([
            'approved_class_section_id' => 'required|exists:class_sections,id',
            'admin_note' => 'nullable|string|max:2000',
            'force_without_fee' => 'nullable|boolean',
        ]);

        try {
            $this->reenrollments->approve(
                $confirmation,
                (int) $data['approved_class_section_id'],
                $data['admin_note'] ?? null,
                ! $request->boolean('force_without_fee')
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('reenrollments.index', ['campaign_id' => $confirmation->campaign_id, 'status' => 'queue'])
            ->with('success', __('reenrollment.messages.approved'));
    }

    public function reject(Request $request, ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        try {
            $this->reenrollments->reject($confirmation, $data['admin_note'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('reenrollments.index', ['campaign_id' => $confirmation->campaign_id, 'status' => 'queue'])
            ->with('success', __('reenrollment.messages.rejected'));
    }

    public function keepPending(Request $request, ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        try {
            $this->reenrollments->keepPending($confirmation, $data['admin_note'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('reenrollment.messages.kept_pending'));
    }

    public function remind(ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        if ($confirmation->parent_confirmed_at) {
            return back()->with('error', __('reenrollment.errors.already_answered'));
        }

        $this->reenrollments->sendReminder($confirmation);

        return back()->with('success', __('reenrollment.messages.reminder_sent'));
    }

    public function reopen(Request $request, ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:2000',
        ]);

        try {
            $this->reenrollments->reopenConfirmation($confirmation, $data['admin_note'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('reenrollment.messages.reopened'));
    }

    public function updateProposedClass(Request $request, ReenrollmentConfirmation $confirmation)
    {
        $this->authorizeInstitution($confirmation->institution_id);

        $data = $request->validate([
            'proposed_class_section_id' => 'nullable|exists:class_sections,id',
        ]);

        $confirmation->update([
            'proposed_class_section_id' => $data['proposed_class_section_id'] ?: null,
        ]);

        return back()->with('success', __('reenrollment.messages.proposed_updated'));
    }

    /** Shared filtering for the list screen and the CSV export. */
    protected function buildQuery(Request $request, int $institutionId, ?int $campaignId = null)
    {
        $status = $request->input('status', 'queue');
        $campaignId = $campaignId ?? $request->input('campaign_id');

        $query = ReenrollmentConfirmation::query()->where('institution_id', $institutionId);

        if ($campaignId) {
            $query->where('campaign_id', (int) $campaignId);
        }

        if ($status === 'queue') {
            $query->whereIn('status', ReenrollmentConfirmation::QUEUE_STATUSES);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->whereHas('student', function ($s) use ($q) {
                $s->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('admission_number', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    protected function authorizeInstitution(?int $institutionId): void
    {
        $active = $this->getInstitutionId();
        if ($active && (int) $institutionId !== (int) $active) {
            abort(403);
        }
    }
}
