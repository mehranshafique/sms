<?php

namespace App\Http\Controllers;

use App\Jobs\SendSchoolEventInvitationsJob;
use App\Models\ChatSession;
use App\Models\ClassSection;
use App\Models\SchoolEvent;
use App\Models\SchoolEventInvitation;
use App\Models\StudentEnrollment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SchoolEventController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('school_event.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        $baseQuery = SchoolEvent::where('institution_id', $institutionId);
        $events = (clone $baseQuery)->latest()->paginate(15);
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
        ];

        return view('school_events.index', compact('events', 'stats'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $sections = ClassSection::where('institution_id', $institutionId)->with('gradeLevel')->get();

        return view('school_events.create', compact('sections'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'name' => 'required|string|max:200',
            'event_date' => 'required|date',
            'event_time' => 'nullable',
            'venue' => 'nullable|string|max:200',
            'audience' => 'required|in:parents,students,staff,class',
            'class_section_ids' => 'nullable|array',
        ]);

        $event = SchoolEvent::create([
            'institution_id' => $institutionId,
            'name' => $request->name,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'event_time' => $request->event_time,
            'venue' => $request->venue,
            'contact' => $request->contact,
            'audience' => $request->audience,
            'class_section_ids' => $request->class_section_ids,
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        return $this->successResponse(__('school_event.created'), route('school-events.show', $event));
    }

    public function show(SchoolEvent $schoolEvent)
    {
        if ($schoolEvent->institution_id != $this->getInstitutionId()) {
            abort(403);
        }

        $schoolEvent->load('invitations');

        return view('school_events.show', compact('schoolEvent'));
    }

    public function buildInvitations(SchoolEvent $schoolEvent)
    {
        if ($schoolEvent->institution_id != $this->getInstitutionId()) {
            abort(403);
        }

        $query = StudentEnrollment::with(['student.parent', 'classSection.gradeLevel'])
            ->where('status', 'active')
            ->whereHas('student', fn ($q) => $q->where('institution_id', $schoolEvent->institution_id));

        if ($schoolEvent->audience === 'class' && !empty($schoolEvent->class_section_ids)) {
            $query->whereIn('class_section_id', $schoolEvent->class_section_ids);
        }

        $count = 0;
        foreach ($query->get() as $enrollment) {
            $student = $enrollment->student;
            $parent = $student?->parent;
            if (!$student) {
                continue;
            }

            SchoolEventInvitation::updateOrCreate(
                ['school_event_id' => $schoolEvent->id, 'student_id' => $student->id],
                [
                    'recipient_name' => $parent?->full_name ?? $parent?->father_name ?? $student->full_name,
                    'recipient_phone' => $parent?->father_phone ?? $parent?->mother_phone ?? $student->mobile_number,
                    'recipient_email' => $parent?->email ?? $student->email,
                    'recipient_telegram_chat_id' => $this->resolveTelegramChatId($parent?->user_id),
                    'delivery_status' => 'pending',
                ]
            );
            $count++;
        }

        return back()->with('success', __('school_event.invitations_built', ['count' => $count]));
    }

    public function send(SchoolEvent $schoolEvent)
    {
        if ($schoolEvent->institution_id != $this->getInstitutionId()) {
            abort(403);
        }

        if ($schoolEvent->invitations()->count() === 0) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('school_event.no_invitations'),
                ], 422);
            }

            return back()->with('error', __('school_event.no_invitations'));
        }

        if ($schoolEvent->status === 'sending') {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'info',
                    'message' => __('school_event.already_sending'),
                ]);
            }

            return back()->with('info', __('school_event.already_sending'));
        }

        $schoolEvent->update(['status' => 'sending']);

        SendSchoolEventInvitationsJob::dispatchAfterResponse(
            $schoolEvent->id,
            (int) Auth::id()
        );

        $message = __('school_event.job_queued');

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'event_status' => 'sending',
            ]);
        }

        return back()->with('success', $message);
    }

    public function sendStatus(SchoolEvent $schoolEvent)
    {
        if ($schoolEvent->institution_id != $this->getInstitutionId()) {
            abort(403);
        }

        return response()->json([
            'status' => $schoolEvent->status,
            'invitation_stats' => [
                'total' => $schoolEvent->invitations()->count(),
                'sent' => $schoolEvent->invitations()->where('delivery_status', 'sent')->count(),
                'partial' => $schoolEvent->invitations()->where('delivery_status', 'partial')->count(),
                'failed' => $schoolEvent->invitations()->where('delivery_status', 'failed')->count(),
                'pending' => $schoolEvent->invitations()->where('delivery_status', 'pending')->count(),
            ],
        ]);
    }

    public function preview(SchoolEvent $schoolEvent, NotificationService $notifications)
    {
        if ($schoolEvent->institution_id != $this->getInstitutionId()) {
            abort(403);
        }

        $invitation = $schoolEvent->invitations()->with('student.enrollments.classSection.gradeLevel')->first();
        if (!$invitation) {
            return response()->json(['message' => __('school_event.no_invitations')], 422);
        }

        $payload = $this->invitationPayload($schoolEvent, $invitation);
        $template = \App\Models\SmsTemplate::forEvent('event_invitation', $schoolEvent->institution_id)->first();
        $preview = $template
            ? apply_sms_template_tags($template->body, $payload)
            : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response()->json(['preview' => $preview]);
    }

    private function invitationPayload(SchoolEvent $schoolEvent, SchoolEventInvitation $invitation): array
    {
        $student = $invitation->student;
        $enrollment = $student?->enrollments?->where('status', 'active')->first();
        $ticket = 'EVT-' . strtoupper(Str::random(6));

        return [
            'ParentName' => $invitation->recipient_name,
            'StudentName' => $student?->full_name ?? '',
            'ClassName' => class_section_label($enrollment?->classSection),
            'EventName' => $schoolEvent->name,
            'EventDate' => localized_date($schoolEvent->event_date, 'd M Y'),
            'EventTime' => $schoolEvent->event_time ? substr((string) $schoolEvent->event_time, 0, 5) : '',
            'Venue' => $schoolEvent->venue ?? '',
            'TicketNumber' => $ticket,
            'SchoolName' => $schoolEvent->institution?->name ?? config('app.name'),
        ];
    }

    private function resolveTelegramChatId(?int $userId): ?string
    {
        if (!$userId) {
            return null;
        }

        return ChatSession::where('user_id', $userId)
            ->latest('last_interaction_at')
            ->value('phone_number');
    }
}
