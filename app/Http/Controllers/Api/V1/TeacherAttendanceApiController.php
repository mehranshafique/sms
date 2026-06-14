<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\InstitutionSetting;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Timetable;
use App\Services\Mobile\MobileContextService;
use App\Services\TeacherAttendanceAuthorization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceApiController extends Controller
{
    public function __construct(
        protected MobileContextService $contextService
    ) {}

    public function classes(Request $request)
    {
        $user = $request->user();
        if ($denied = TeacherAttendanceAuthorization::denyUnlessAllowed($user)) {
            return $denied;
        }

        $institutionId = $user->institute_id;
        $isAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);

        $query = ClassSection::with(['gradeLevel', 'institution']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (!$isAdmin && $user->staff) {
            $staffId = $user->staff->id;
            $query->where(function ($q) use ($staffId) {
                $q->where('staff_id', $staffId)
                    ->orWhereHas('timetables', fn ($t) => $t->where('teacher_id', $staffId))
                    ->orWhereHas('classSubjects', fn ($c) => $c->where('teacher_id', $staffId));
            });
        }

        $classes = $query->get()->map(function ($item) use ($institutionId) {
            $grade = $item->gradeLevel->name ?? '';
            $label = trim(($grade ? $grade . ' ' : '') . $item->name);
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }

            return [
                'id' => $item->id,
                'label' => $label,
                'institution_id' => $item->institution_id,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'is_subject_wise' => $this->contextService->isSubjectWise($institutionId),
            'data' => $classes,
        ]);
    }

    public function subjects(Request $request, int $classSectionId)
    {
        $user = $request->user();
        if ($denied = TeacherAttendanceAuthorization::denyUnlessAllowed($user)) {
            return $denied;
        }

        $classSection = ClassSection::findOrFail($classSectionId);
        if ($denied = TeacherAttendanceAuthorization::denyUnlessClassAccess($user, $classSection)) {
            return $denied;
        }

        $institutionId = $classSection->institution_id;
        $isSubjectWise = $this->contextService->isSubjectWise($institutionId);

        if (!$isSubjectWise) {
            return response()->json([
                'success' => true,
                'is_subject_wise' => false,
                'data' => [],
            ]);
        }

        $query = ClassSubject::with('subject')
            ->where('class_section_id', $classSectionId);

        if (!$user->hasRole(['Super Admin', 'Head Officer', 'School Admin']) && $user->staff) {
            $staffId = $user->staff->id;
            $query->where(function ($q) use ($staffId, $classSectionId) {
                $q->where('teacher_id', $staffId)
                    ->orWhereHas('classSection.timetables', function ($t) use ($staffId, $classSectionId) {
                        $t->where('teacher_id', $staffId)->where('class_section_id', $classSectionId);
                    });
            });
        }

        $subjects = $query->get()->map(fn ($cs) => [
            'id' => $cs->subject_id,
            'name' => $cs->subject?->name,
        ])->unique('id')->values();

        return response()->json([
            'success' => true,
            'is_subject_wise' => true,
            'data' => $subjects,
        ]);
    }

    public function roster(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'date' => 'required|date',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $user = $request->user();
        $classSection = ClassSection::findOrFail($request->class_section_id);

        if ($denied = TeacherAttendanceAuthorization::denyUnlessClassAccess(
            $user,
            $classSection,
            $request->filled('subject_id') ? (int) $request->subject_id : null
        )) {
            return $denied;
        }

        $institutionId = $classSection->institution_id;
        $isSubjectWise = $this->contextService->isSubjectWise($institutionId);

        if ($isSubjectWise && !$request->filled('subject_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is required for this institution type.',
            ], 422);
        }

        $session = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active academic session.',
            ], 422);
        }

        $students = StudentEnrollment::with('student')
            ->where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($enrollment) use ($request, $isSubjectWise) {
                $student = $enrollment->student;
                $attendanceQuery = StudentAttendance::where('student_id', $student->id)
                    ->where('class_section_id', $request->class_section_id)
                    ->where('attendance_date', $request->date);

                if ($isSubjectWise) {
                    $attendanceQuery->where('subject_id', $request->subject_id);
                } else {
                    $attendanceQuery->whereNull('subject_id');
                }

                $record = $attendanceQuery->first();

                return [
                    'student_id' => $student->id,
                    'name' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'status' => $record?->status,
                    'marked' => $record !== null,
                ];
            })->values();

        $lock = $this->resolveLockState($user, $classSection, Carbon::parse($request->date));

        return response()->json([
            'success' => true,
            'is_subject_wise' => $isSubjectWise,
            'is_locked' => $lock['locked'],
            'lock_reason' => $lock['reason'],
            'data' => $students,
        ]);
    }

    public function mark(Request $request)
    {
        $user = $request->user();
        if ($denied = TeacherAttendanceAuthorization::denyUnlessAllowed($user)) {
            return $denied;
        }

        $rules = [
            'class_section_id' => 'required|exists:class_sections,id',
            'attendance_date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'in:present,absent,late,excused,half_day',
        ];

        $classSection = ClassSection::findOrFail($request->class_section_id);
        $targetInstituteId = $classSection->institution_id;
        $isSubjectWise = $this->contextService->isSubjectWise($targetInstituteId);

        if ($isSubjectWise) {
            $rules['subject_id'] = 'required|exists:subjects,id';
        }

        $request->validate($rules);

        if ($denied = TeacherAttendanceAuthorization::denyUnlessClassAccess(
            $user,
            $classSection,
            $request->filled('subject_id') ? (int) $request->subject_id : null
        )) {
            return $denied;
        }

        if (!$user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            $lock = $this->resolveLockState($user, $classSection, Carbon::parse($request->attendance_date));
            if ($lock['locked']) {
                return response()->json(['success' => false, 'message' => $lock['reason']], 403);
            }
        }

        $session = AcademicSession::where('institution_id', $targetInstituteId)
            ->where('is_current', true)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'No active academic session.'], 422);
        }

        $enrolledStudentIds = StudentEnrollment::where('class_section_id', $request->class_section_id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->pluck('student_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        DB::transaction(function () use ($request, $targetInstituteId, $session, $isSubjectWise, $user, $enrolledStudentIds) {
            foreach ($request->attendance as $studentId => $status) {
                if (!in_array((string) $studentId, $enrolledStudentIds, true)) {
                    continue;
                }

                $match = [
                    'student_id' => $studentId,
                    'attendance_date' => $request->attendance_date,
                    'class_section_id' => $request->class_section_id,
                ];

                if ($isSubjectWise) {
                    $match['subject_id'] = $request->subject_id;
                } else {
                    $match['subject_id'] = null;
                }

                StudentAttendance::updateOrCreate(
                    $match,
                    [
                        'institution_id' => $targetInstituteId,
                        'academic_session_id' => $session->id,
                        'status' => $status,
                        'marked_by' => $user->id,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved successfully.',
        ]);
    }

    private function resolveLockState($user, ClassSection $classSection, Carbon $targetDate): array
    {
        if ($user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            return ['locked' => false, 'reason' => null];
        }

        $targetInstituteId = $classSection->institution_id;

        if (InstitutionSetting::get($targetInstituteId, 'attendance_locked', 0)) {
            return ['locked' => true, 'reason' => 'Attendance marking is locked by administration.'];
        }

        $graceDays = InstitutionSetting::get($targetInstituteId, 'attendance_grace_period', 7);
        if ($targetDate->lt(now()->subDays($graceDays)->startOfDay())) {
            return ['locked' => true, 'reason' => "Grace period of {$graceDays} days exceeded."];
        }

        return ['locked' => false, 'reason' => null];
    }
}
