<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\InfirmaryVisit;
use App\Models\Student;
use App\Services\Medical\StudentMedicalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MedicalRecordController extends BaseController
{
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function __construct(protected StudentMedicalService $medical)
    {
        $this->middleware('auth');
        $this->setPageTitle(__('medical.page_title'));
    }

    /**
     * Infirmary visit log for the school.
     */
    public function index(Request $request)
    {
        $this->authorizeMedicalAccess('view');
        $institutionId = $this->requireInstitutionId();

        if ($request->ajax()) {
            $query = InfirmaryVisit::with(['student.classSection.gradeLevel', 'recorder'])
                ->where('institution_id', $institutionId)
                ->latest('visited_at');

            if ($request->filled('outcome') && $request->outcome !== 'all') {
                $query->where('outcome', $request->outcome);
            }
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            if ($request->filled('from')) {
                $query->whereDate('visited_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('visited_at', '<=', $request->to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('visited_at', fn ($row) => $row->visited_at->format('d M Y H:i'))
                ->addColumn('student_name', fn ($row) => dt_link(
                    $row->student_id ? route('medical-records.show', $row->student_id) : null,
                    $row->student->full_name ?? 'N/A'
                ))
                ->addColumn('class_name', fn ($row) => e($row->student?->classSection
                    ? class_section_label($row->student->classSection)
                    : '—'))
                ->editColumn('outcome', fn ($row) => '<span class="badge badge-' . $row->outcomeBadgeClass() . '">' . e($row->outcomeLabel()) . '</span>')
                ->addColumn('recorded_by_name', fn ($row) => e($row->recorder->name ?? '—'))
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-end">';
                    $btn .= '<a href="' . route('medical-records.show', $row->student_id) . '" class="btn btn-info btn-sm shadow me-1"><i class="fa fa-eye"></i></a>';
                    if (Auth::user()->can('medical_record.delete')) {
                        $btn .= '<button type="button" class="btn btn-danger btn-sm shadow delete-visit-btn" data-url="' . route('medical-records.visits.destroy', $row->id) . '"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';

                    return $btn;
                })
                ->rawColumns(['student_name', 'outcome', 'action'])
                ->make(true);
        }

        $stats = [
            'today' => InfirmaryVisit::where('institution_id', $institutionId)->whereDate('visited_at', today())->count(),
            'week' => InfirmaryVisit::where('institution_id', $institutionId)->where('visited_at', '>=', now()->subDays(7))->count(),
            'sent_home' => InfirmaryVisit::where('institution_id', $institutionId)
                ->whereIn('outcome', ['sent_home', 'referred_hospital'])
                ->where('visited_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return view('medical.index', compact('stats'));
    }

    /**
     * A student's medical record plus visit history.
     */
    public function show(Student $student)
    {
        $this->authorizeMedicalAccess('view');
        $this->assertInstitutionMatch((int) $student->institution_id);

        $student->load(['parent', 'classSection.gradeLevel', 'gradeLevel']);
        $profile = $this->medical->profileFor($student);
        $visits = $student->infirmaryVisits()->with('recorder')->latest('visited_at')->paginate(15);

        $this->medical->logAccess($student, 'record');

        return view('medical.show', [
            'student' => $student,
            'profile' => $profile,
            'visits' => $visits,
            'emergency' => $profile->emergencyContact(),
            'canUpdate' => Auth::user()->can('medical_record.update') || $this->userIsAdmin(),
            'canCreateVisit' => Auth::user()->can('medical_record.create') || $this->userIsAdmin(),
        ]);
    }

    public function edit(Student $student)
    {
        $this->authorizeMedicalAccess('update');
        $this->assertInstitutionMatch((int) $student->institution_id);

        $student->load('parent');

        return view('medical.edit', [
            'student' => $student,
            'profile' => $this->medical->profileFor($student),
            'bloodGroups' => self::BLOOD_GROUPS,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeMedicalAccess('update');
        $this->assertInstitutionMatch((int) $student->institution_id);

        $validated = $request->validate([
            'blood_group' => 'nullable|in:' . implode(',', self::BLOOD_GROUPS),
            'allergies' => 'nullable|string|max:2000',
            'chronic_conditions' => 'nullable|string|max:2000',
            'current_medication' => 'nullable|string|max:2000',
            'medical_notes' => 'nullable|string|max:2000',
            'doctor_name' => 'nullable|string|max:150',
            'doctor_phone' => 'nullable|string|max:30',
            'insurance_provider' => 'nullable|string|max:150',
            'insurance_number' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_relation' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_alt_phone' => 'nullable|string|max:30',
            'consent_first_aid' => 'nullable|boolean',
            'information_date' => 'nullable|date',
        ]);

        $validated['consent_first_aid'] = $request->boolean('consent_first_aid');

        $this->medical->saveProfile($student, $validated, Auth::user());

        return $this->successResponse(
            __('medical.success_profile_saved'),
            route('medical-records.show', $student->id)
        );
    }

    /**
     * Record a new infirmary visit.
     */
    public function createVisit(Request $request)
    {
        $this->authorizeMedicalAccess('create');
        $institutionId = $this->requireInstitutionId();

        $students = Student::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => $s->full_name . ' (' . ($s->admission_number ?? $s->id) . ')']);

        $selected = $request->integer('student_id') ?: null;
        $profile = null;

        if ($selected) {
            $student = Student::where('institution_id', $institutionId)->find($selected);
            $profile = $student ? $this->medical->profileFor($student) : null;
        }

        return view('medical.visit_create', [
            'students' => $students,
            'selectedStudent' => $selected,
            'profile' => $profile,
            'outcomes' => InfirmaryVisit::OUTCOMES,
        ]);
    }

    public function storeVisit(Request $request)
    {
        $this->authorizeMedicalAccess('create');
        $institutionId = $this->requireInstitutionId();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'visited_at' => 'required|date|before_or_equal:now',
            'reason' => 'required|string|max:255',
            'observation' => 'nullable|string|max:2000',
            'action_taken' => 'nullable|string|max:2000',
            'temperature' => 'nullable|string|max:10',
            'blood_pressure' => 'nullable|string|max:20',
            'outcome' => 'required|in:' . implode(',', InfirmaryVisit::OUTCOMES),
            'parent_informed' => 'nullable|boolean',
        ]);

        $student = Student::where('institution_id', $institutionId)->findOrFail($validated['student_id']);

        $validated['parent_informed'] = $request->boolean('parent_informed');
        $validated['academic_session_id'] = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        $this->medical->recordVisit($student, $validated, Auth::user());

        return $this->successResponse(
            __('medical.success_visit_recorded'),
            route('medical-records.show', $student->id)
        );
    }

    public function destroyVisit(InfirmaryVisit $visit)
    {
        $this->authorizeMedicalAccess('delete');
        $this->assertInstitutionMatch((int) $visit->institution_id);

        $visit->delete();

        return response()->json(['message' => __('medical.success_visit_deleted')]);
    }

    /**
     * Student picker for the infirmary: search by name or admission number.
     */
    public function searchStudents(Request $request)
    {
        $this->authorizeMedicalAccess('view');
        $institutionId = $this->requireInstitutionId();

        $term = trim((string) $request->query('q'));

        $students = Student::with('classSection.gradeLevel')
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->when($term !== '', fn ($query) => $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like' , "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('admission_number', 'like', "%{$term}%");
            }))
            ->orderBy('first_name')
            ->limit(20)
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'class' => $student->classSection ? class_section_label($student->classSection) : null,
                'url' => route('medical-records.show', $student->id),
            ]);

        return response()->json($students);
    }

    private function authorizeMedicalAccess(string $action): void
    {
        $permissionMap = [
            'view' => ['medical_record.view', 'medical_record.viewAny'],
            'create' => ['medical_record.create'],
            'update' => ['medical_record.update'],
            'delete' => ['medical_record.delete'],
        ];

        $this->authorizeAdminOrAnyPermission($permissionMap[$action] ?? ['medical_record.view']);
    }

    private function userIsAdmin(): bool
    {
        return Auth::user()->hasRole(['Super Admin', 'School Admin', 'Head Officer']);
    }

    private function requireInstitutionId(): int
    {
        $institutionId = $this->getInstitutionId();

        if (! $institutionId) {
            abort(403, __('configuration.institution_not_found'));
        }

        return (int) $institutionId;
    }
}
