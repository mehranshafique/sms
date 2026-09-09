<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceScheduleAssignment;
use App\Models\ClassSection;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AttendanceScheduleController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(AttendanceSchedule::class, 'attendance_schedule');
        $this->setPageTitle(__('attendance_schedule.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = AttendanceSchedule::with(['assignments.gradeLevel', 'assignments.classSection'])
                ->select('attendance_schedules.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return dt_link(dt_route('attendance-schedules.edit', $row->id), $row->name);
                })
                ->addColumn('check_in', function ($row) {
                    return $row->checkInTimeLabel();
                })
                ->addColumn('check_out', function ($row) {
                    return $row->checkOutTimeLabel() ?? '—';
                })
                ->addColumn('late_margin', function ($row) {
                    return (int) $row->late_margin_minutes;
                })
                ->addColumn('assignments_summary', function ($row) {
                    $grades = $row->assignments
                        ->filter(fn ($a) => $a->grade_level_id)
                        ->map(fn ($a) => $a->gradeLevel?->name)
                        ->filter()
                        ->unique()
                        ->values();
                    $sections = $row->assignments
                        ->filter(fn ($a) => $a->class_section_id)
                        ->map(function ($a) {
                            $section = $a->classSection;
                            if (! $section) {
                                return null;
                            }

                            return class_section_label($section, 'grade_dash_section');
                        })
                        ->filter()
                        ->unique()
                        ->values();

                    $parts = [];
                    if ($grades->isNotEmpty()) {
                        $parts[] = __('attendance_schedule.grades_label').': '.$grades->implode(', ');
                    }
                    if ($sections->isNotEmpty()) {
                        $parts[] = __('attendance_schedule.sections_label').': '.$sections->implode(', ');
                    }

                    return $parts === [] ? '—' : implode(' · ', $parts);
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge badge-success">'.__('attendance_schedule.active').'</span>'
                        : '<span class="badge badge-danger">'.__('attendance_schedule.inactive').'</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if (auth()->user()->can('update', $row)) {
                        $btn .= '<a href="'.route('attendance-schedules.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if (auth()->user()->can('delete', $row)) {
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';

                    return $btn;
                })
                ->rawColumns(['name', 'is_active', 'action'])
                ->make(true);
        }

        $query = AttendanceSchedule::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $total = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();

        return view('attendance_schedules.index', compact('total', 'active'));
    }

    public function create()
    {
        return view('attendance_schedules.create', $this->formData());
    }

    public function store(Request $request)
    {
        $institutionId = $this->resolveTargetInstitutionId($request);
        $validated = $this->validateSchedule($request, $institutionId);

        DB::transaction(function () use ($validated, $institutionId, $request) {
            $schedule = AttendanceSchedule::create([
                'institution_id' => $institutionId,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'check_in_time' => $validated['check_in_time'],
                'check_out_time' => $validated['check_out_time'] ?? null,
                'late_margin_minutes' => $validated['late_margin_minutes'],
                'is_active' => $validated['is_active'],
            ]);

            $this->syncAssignments(
                $schedule,
                $institutionId,
                $request->input('grade_level_ids', []),
                $request->input('class_section_ids', [])
            );
        });

        return response()->json([
            'message' => __('attendance_schedule.messages.success_create'),
            'redirect' => route('attendance-schedules.index'),
        ]);
    }

    public function edit(AttendanceSchedule $attendance_schedule)
    {
        $attendance_schedule->load('assignments');

        return view('attendance_schedules.edit', array_merge(
            $this->formData($attendance_schedule),
            ['schedule' => $attendance_schedule]
        ));
    }

    public function update(Request $request, AttendanceSchedule $attendance_schedule)
    {
        $institutionId = (int) $attendance_schedule->institution_id;
        $validated = $this->validateSchedule($request, $institutionId, $attendance_schedule);

        DB::transaction(function () use ($attendance_schedule, $validated, $institutionId, $request) {
            $attendance_schedule->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'check_in_time' => $validated['check_in_time'],
                'check_out_time' => $validated['check_out_time'] ?? null,
                'late_margin_minutes' => $validated['late_margin_minutes'],
                'is_active' => $validated['is_active'],
            ]);

            $this->syncAssignments(
                $attendance_schedule,
                $institutionId,
                $request->input('grade_level_ids', []),
                $request->input('class_section_ids', [])
            );
        });

        return response()->json([
            'message' => __('attendance_schedule.messages.success_update'),
            'redirect' => route('attendance-schedules.index'),
        ]);
    }

    public function destroy(AttendanceSchedule $attendance_schedule)
    {
        $attendance_schedule->delete();

        return response()->json(['message' => __('attendance_schedule.messages.success_delete')]);
    }

    /**
     * @return array{institutionId: ?int, institutes: array, grades: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, selectedGradeIds: array, selectedSectionIds: array}
     */
    private function formData(?AttendanceSchedule $schedule = null): array
    {
        $institutionId = $schedule?->institution_id ?? $this->getInstitutionId();
        $institutes = $this->getInstitutesForSelect();

        $grades = collect();
        $sections = collect();

        if ($institutionId) {
            $grades = GradeLevel::where('institution_id', $institutionId)
                ->ordered()
                ->get(['id', 'name']);

            $sections = ClassSection::with('gradeLevel')
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $selectedGradeIds = $schedule
            ? $schedule->assignments->whereNotNull('grade_level_id')->pluck('grade_level_id')->all()
            : [];
        $selectedSectionIds = $schedule
            ? $schedule->assignments->whereNotNull('class_section_id')->pluck('class_section_id')->all()
            : [];

        return compact(
            'institutionId',
            'institutes',
            'grades',
            'sections',
            'selectedGradeIds',
            'selectedSectionIds'
        );
    }

    private function resolveTargetInstitutionId(Request $request): int
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId) {
            return (int) $institutionId;
        }

        $request->validate([
            'institution_id' => 'required|exists:institutions,id',
        ]);

        return (int) $request->institution_id;
    }

    private function validateSchedule(Request $request, int $institutionId, ?AttendanceSchedule $schedule = null): array
    {
        if ($request->input('check_out_time') === '') {
            $request->merge(['check_out_time' => null]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('attendance_schedules', 'name')
                    ->where(fn ($q) => $q->where('institution_id', $institutionId))
                    ->ignore($schedule?->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i', 'after:check_in_time'],
            'late_margin_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
            'grade_level_ids' => ['nullable', 'array'],
            'grade_level_ids.*' => [
                'integer',
                Rule::exists('grade_levels', 'id')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
            'class_section_ids' => ['nullable', 'array'],
            'class_section_ids.*' => [
                'integer',
                Rule::exists('class_sections', 'id')->where(fn ($q) => $q->where('institution_id', $institutionId)),
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    /**
     * @param  list<int|string>  $gradeIds
     * @param  list<int|string>  $sectionIds
     */
    private function syncAssignments(
        AttendanceSchedule $schedule,
        int $institutionId,
        array $gradeIds,
        array $sectionIds
    ): void {
        $gradeIds = array_values(array_unique(array_filter(array_map('intval', $gradeIds))));
        $sectionIds = array_values(array_unique(array_filter(array_map('intval', $sectionIds))));

        // Free targets currently claimed by other schedules
        if ($gradeIds !== []) {
            AttendanceScheduleAssignment::whereIn('grade_level_id', $gradeIds)->delete();
        }
        if ($sectionIds !== []) {
            AttendanceScheduleAssignment::whereIn('class_section_id', $sectionIds)->delete();
        }

        $schedule->assignments()->delete();

        foreach ($gradeIds as $gradeId) {
            AttendanceScheduleAssignment::create([
                'institution_id' => $institutionId,
                'attendance_schedule_id' => $schedule->id,
                'grade_level_id' => $gradeId,
                'class_section_id' => null,
            ]);
        }

        foreach ($sectionIds as $sectionId) {
            AttendanceScheduleAssignment::create([
                'institution_id' => $institutionId,
                'attendance_schedule_id' => $schedule->id,
                'grade_level_id' => null,
                'class_section_id' => $sectionId,
            ]);
        }
    }
}
