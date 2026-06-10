<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\Staff;
use App\Models\StudentEnrollment;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimetableApiController extends Controller
{
    public function today(Request $request)
    {
        $user = $request->user();
        $day = strtolower(Carbon::now()->format('l'));
        $items = $this->resolveSchedule($request, $user, $day);

        return response()->json([
            'success' => true,
            'day' => $day,
            'data' => $items,
        ]);
    }

    public function week(Request $request)
    {
        $user = $request->user();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $week = [];

        foreach ($days as $day) {
            $week[$day] = $this->resolveSchedule($request, $user, $day);
        }

        return response()->json([
            'success' => true,
            'data' => $week,
        ]);
    }

    private function resolveSchedule(Request $request, $user, string $day): array
    {
        $institutionId = $user->institute_id;
        $session = $institutionId
            ? AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first()
            : null;

        $query = Timetable::with(['subject', 'teacher.user', 'classSection.gradeLevel'])
            ->whereRaw('LOWER(day_of_week) = ?', [$day])
            ->orderBy('start_time');

        if ($session) {
            $query->where('academic_session_id', $session->id);
        }

        if ($user->hasRole('Student') && $user->student) {
            $enrollment = StudentEnrollment::where('student_id', $user->student->id)
                ->where('status', 'active')
                ->when($session, fn ($q) => $q->where('academic_session_id', $session->id))
                ->first();

            if (!$enrollment) {
                return [];
            }

            $query->where('class_section_id', $enrollment->class_section_id);
        } elseif ($user->staff && $user->hasRole(['Teacher', 'Staff']) && !$user->hasRole(['Super Admin', 'School Admin', 'Head Officer'])) {
            $query->where('teacher_id', $user->staff->id);
        } elseif ($requestClass = $request->query('class_section_id')) {
            $query->where('class_section_id', $requestClass);
        }

        return $query->get()->map(fn ($t) => $this->formatSlot($t))->values()->all();
    }

    private function formatSlot(Timetable $t): array
    {
        $grade = $t->classSection?->gradeLevel?->name;
        $classLabel = ($grade ? $grade . ' ' : '') . ($t->classSection?->name ?? '');

        return [
            'id' => $t->id,
            'day_of_week' => strtolower($t->day_of_week),
            'start_time' => $t->start_time ? Carbon::parse($t->start_time)->format('H:i') : null,
            'end_time' => $t->end_time ? Carbon::parse($t->end_time)->format('H:i') : null,
            'subject' => $t->subject?->name,
            'subject_id' => $t->subject_id,
            'teacher' => $t->teacher?->user?->name,
            'teacher_id' => $t->teacher_id,
            'class_section_id' => $t->class_section_id,
            'class_label' => trim($classLabel),
            'room_number' => $t->room_number,
        ];
    }
}
