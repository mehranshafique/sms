<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\ExamRecord;
use App\Models\ClassSection;
use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\AcademicCycleService;

class ExamController extends BaseController
{
    public function __construct(protected AcademicCycleService $cycleService)
    {
        $this->authorizeResource(Exam::class, 'exam');
        $this->setPageTitle(__('exam.page_title'));
    }

    /**
     * Renamed to avoid conflict with BaseController's method.
     */
    protected function getExamAllowedInstitutionIds()
    {
        // FIX: Check for Context Switch (Session) first
        if (session()->has('active_institution_id')) {
            return [session('active_institution_id')];
        }

        $user = Auth::user();
        if ($user->institute_id) {
            return [$user->institute_id];
        }
        if ($user->institutes->isNotEmpty()) {
            return $user->institutes->pluck('id')->toArray();
        }
        return Institution::pluck('id')->toArray();
    }

    public function index(Request $request)
    {
        // Use renamed method
        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();

        if ($request->ajax()) {
            $data = Exam::with(['academicSession', 'institution'])
                ->select('exams.*')
                ->latest('exams.created_at'); // Rule 3: Latest First
                
            if (!empty($allowedInstitutionIds)) {
                $data->whereIn('exams.institution_id', $allowedInstitutionIds);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('delete', $row) || auth()->user()->can('deleteAny', Exam::class)){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('institution', function($row){
                    return $row->institution->name ?? 'N/A';
                })
                ->addColumn('session', function($row){
                    return $row->academicSession->name ?? 'N/A';
                })
                ->addColumn('category', function($row){
                    return $row->category ? ucwords(str_replace('_', ' ', $row->category)) : '-';
                })
                ->editColumn('name', function($row){
                    return dt_link(dt_route('exams.show', $row->id, 'exams.edit'), $row->name);
                })
                ->editColumn('start_date', function($row){
                    return $row->start_date->format('d M, Y');
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'scheduled' => 'badge-info',
                        'ongoing' => 'badge-warning',
                        'completed' => 'badge-primary',
                        'published' => 'badge-success',
                    ];
                    // Rule 1: Use localized status
                    $status = ucfirst($row->status); // Or use localized enum if available
                    if($row->finalized_at) {
                        $status .= ' (' . __('exam.finalized') . ')';
                    }
                    $class = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge '.$class.'">'.$status.'</span>';
                })
                ->addColumn('action', function($row){
                    $user = Auth::user();
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if($user->can('view', $row)){
                        $btn .= '<a href="'.route('exams.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    }

                    $isFinalized = !is_null($row->finalized_at);
                    $canEdit = $user->can('update', $row);
                    $isPrivilegedAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);
                    
                    if($isFinalized && !$isPrivilegedAdmin) {
                        $canEdit = false;
                    }

                    if($canEdit){
                        $btn .= '<a href="'.route('exams.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }

                    $canDelete = $user->can('delete', $row);
                    if ($canDelete && $isFinalized && !$isPrivilegedAdmin) {
                        $canDelete = false;
                    }
                    if($canDelete){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'name', 'status', 'action'])
                ->make(true);
        }

        return view('exams.index', $this->examIndexContext($allowedInstitutionIds));
    }

    protected function examIndexContext(array $allowedInstitutionIds): array
    {
        $institutionId = session('active_institution_id') ?: Auth::user()->institute_id;
        $missingCategories = [];
        if ($institutionId && $institutionId !== 'global') {
            $institution = Institution::find($institutionId);
            $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
            if ($institution && $session) {
                $missingCategories = $this->cycleService->missingExamCategoriesForSession(
                    (int) $institutionId,
                    $session->id,
                    is_object($institution->type) ? $institution->type->value : $institution->type
                );
            }
        }

        return compact('missingCategories');
    }

    protected function allowedCategoriesForInstitution(?int $institutionId): array
    {
        if (!$institutionId) {
            return $this->cycleService->examCategoriesForInstitutionType('mixed');
        }
        $institution = Institution::find($institutionId);
        $type = $institution ? (is_object($institution->type) ? $institution->type->value : $institution->type) : 'mixed';

        return $this->cycleService->examCategoriesForInstitutionType($type);
    }

    public function create()
    {
        // Use renamed method
        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();
        $user = Auth::user();

        // SETTINGS CHECK (Create Block)
        // If regular user (Teacher/Staff), check lock status
        if (!$user->hasRole(['Super Admin', 'Head Officer'])) {
            $institutionId = $user->institute_id;
            if ($institutionId) {
                $isLocked = InstitutionSetting::get($institutionId, 'exams_locked', 0);
                if ($isLocked) {
                    return redirect()->route('exams.index')->with('error', __('settings.admin_blocked_error'));
                }
            }
        }
        
        $sessions = AcademicSession::with('institution')
            ->whereIn('institution_id', $allowedInstitutionIds)
            ->whereIn('status', ['active', 'planned'])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->name . ' (' . $item->institution->name . ')'];
            });

        $institutions = Institution::whereIn('id', $allowedInstitutionIds)->pluck('name', 'id');
        $institutionId = Auth::user()->institute_id ?: ($allowedInstitutionIds[0] ?? null);
        $allowedCategories = $this->allowedCategoriesForInstitution($institutionId);

        return view('exams.create', compact('sessions', 'institutions', 'allowedCategories'));
    }

    public function store(Request $request)
    {
        // Use renamed method
        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();
        $user = Auth::user();

        $session = AcademicSession::find($request->academic_session_id);
        if (!$session || !in_array($session->institution_id, $allowedInstitutionIds)) {
            abort(403, __('exam.messages.unauthorized'));
        }

        // SETTINGS ENFORCEMENT
        if (!$user->hasRole(['Super Admin', 'Head Officer'])) {
            // 1. Check Global Block
            $isBlocked = InstitutionSetting::get($session->institution_id, 'exams_locked', 0);
            if ($isBlocked) {
                return response()->json(['message' => __('settings.admin_blocked_error')], 403);
            }
        }

        $allowedCategories = $this->allowedCategoriesForInstitution($session->institution_id);

        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('exams')->where(function ($query) use ($session) {
                    return $query->where('academic_session_id', $session->id)
                                 ->where('institution_id', $session->institution_id);
                })
            ],
            'category' => ['required', 'string', 'max:50', Rule::in($allowedCategories)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:scheduled,ongoing,completed',
            'description' => 'nullable|string',
        ]);

        $exam = new Exam($request->only([
            'academic_session_id', 'name', 'category', 'start_date', 'end_date', 'status', 'description',
        ]));
        $exam->institution_id = $session->institution_id;
        $exam->save();

        return response()->json(['message' => __('exam.messages.success_create'), 'redirect' => route('exams.index')]);
    }

    public function show(Exam $exam)
    {
        // Use renamed method
        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();
        if (!in_array($exam->institution_id, $allowedInstitutionIds)) {
            abort(403);
        }

        $exam->load(['academicSession', 'institution']);

        // Pass available classes for print modal with Rule 2: Section (Grade)
        $classes = ClassSection::with('gradeLevel') // Eager load
            ->where('institution_id', $exam->institution_id)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($item) {
                 $grade = $item->gradeLevel->name ?? '';
                 return [$item->id => $item->name . ($grade ? ' (' . $grade . ')' : '')];
            });
        
        return view('exams.show', compact('exam', 'classes'));
    }

    public function edit(Exam $exam)
    {
        // Use renamed method
        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();
        $user = Auth::user();

        if (!in_array($exam->institution_id, $allowedInstitutionIds)) {
            abort(403);
        }

        // Finalized check
        if($exam->finalized_at && !$user->hasRole(['Super Admin', 'Head Officer'])) {
            abort(403, __('exam.messages.exam_finalized_error'));
        }

        // SETTINGS CHECK (Edit Block & Grace Period)
        if (!$user->hasRole(['Super Admin', 'Head Officer'])) {
            // 1. Check Global Lock
            $isLocked = InstitutionSetting::get($exam->institution_id, 'exams_locked', 0);
            if ($isLocked) {
                return redirect()->route('exams.index')->with('error', __('settings.admin_blocked_error'));
            }

            // 2. Check Grace Period
            $graceDays = InstitutionSetting::get($exam->institution_id, 'exams_grace_period', 30);
            if ($exam->start_date->diffInDays(now()) > $graceDays) {
                return redirect()->route('exams.index')->with('error', __('settings.grace_period_error', ['days' => $graceDays]));
            }
        }

        $sessions = AcademicSession::with('institution')
            ->whereIn('institution_id', $allowedInstitutionIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->name . ' (' . $item->institution->name . ')'];
            });

        $allowedCategories = $this->allowedCategoriesForInstitution($exam->institution_id);

        return view('exams.edit', compact('exam', 'sessions', 'allowedCategories'));
    }

    public function update(Request $request, Exam $exam)
    {
        $user = Auth::user();

        // Finalized Check
        if($exam->finalized_at && !$user->hasRole(['Super Admin', 'Head Officer'])) {
            return response()->json(['message' => __('exam.messages.exam_finalized')], 403);
        }

        // SETTINGS ENFORCEMENT
        if (!$user->hasRole(['Super Admin', 'Head Officer'])) {
            // 1. Check Global Block
            $isBlocked = InstitutionSetting::get($exam->institution_id, 'exams_locked', 0);
            if ($isBlocked) {
                return response()->json(['message' => __('settings.admin_blocked_error')], 403);
            }

            // 2. Check Grace Period
            $graceDays = InstitutionSetting::get($exam->institution_id, 'exams_grace_period', 30);
            if ($exam->start_date->diffInDays(now()) > $graceDays) {
                return response()->json(['message' => __('settings.grace_period_error', ['days' => $graceDays])], 403);
            }
        }

        $allowedCategories = $this->allowedCategoriesForInstitution($exam->institution_id);

        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('exams')->ignore($exam->id)->where(function ($query) use ($exam) {
                    return $query->where('academic_session_id', $exam->academic_session_id)
                                 ->where('institution_id', $exam->institution_id);
                })
            ],
            'category' => ['required', 'string', 'max:50', Rule::in($allowedCategories)],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:scheduled,ongoing,completed,published',
            'description' => 'nullable|string',
        ]);

        $data = $request->only([
            'academic_session_id', 'name', 'category', 'start_date', 'end_date', 'status', 'description',
        ]);

        if (($data['status'] ?? '') === 'published' && !$exam->finalized_at) {
            unset($data['status']);
        }

        $exam->update($data);

        return response()->json(['message' => __('exam.messages.success_update'), 'redirect' => route('exams.index')]);
    }

    public function destroy(Exam $exam)
    {
        $user = Auth::user();
        $isPrivilegedAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);

        if ($exam->finalized_at && ! $isPrivilegedAdmin) {
            return response()->json(['message' => __('exam.messages.delete_finalized_error')], 403);
        }

        try {
            $exam->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => __('exam.messages.delete_failed') ?: __('exam.something_went_wrong'),
            ], 422);
        }

        return response()->json(['message' => __('exam.messages.success_delete')]);
    }

    public function finalize(Exam $exam)
    {
        $this->authorize('update', $exam);
        
        $exam->update(['finalized_at' => now(), 'status' => 'published']);

        app(\App\Services\InAppNotificationService::class)->notifyExamPublished($exam);

        return back()->with('success', __('exam.messages.finalized_success'));
    }

    public function printClassResult(Request $request, Exam $exam)
    {
        // Use renamed method
        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();
        if (!in_array($exam->institution_id, $allowedInstitutionIds)) {
            abort(403);
        }

        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id'
        ]);

        $classSection = ClassSection::with(['gradeLevel', 'classTeacher.user'])->findOrFail($request->class_section_id);

        $records = ExamRecord::with(['student', 'subject'])
            ->where('exam_id', $exam->id)
            ->where('class_section_id', $classSection->id)
            ->get()
            ->groupBy('student_id');

        $subjects = $exam->records()
            ->where('class_section_id', $classSection->id)
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();

        $totals = $records->map(fn ($studentRecords) => (float) $studentRecords->sum('marks_obtained'))
            ->sortDesc();
        $ranks = [];
        $place = 0;
        $previousTotal = null;
        $index = 0;
        foreach ($totals as $studentId => $total) {
            $index++;
            if ($previousTotal === null || $total < $previousTotal) {
                $place = $index;
                $previousTotal = $total;
            }
            $ranks[$studentId] = $place;
        }

        $exam->loadMissing(['institution', 'academicSession']);
        $data = compact('exam', 'classSection', 'records', 'subjects', 'ranks');

        if ($request->has('download')) {
             $pdf = Pdf::loadView('exams.print_class_result', $data)->setPaper('a4', 'landscape');
             return $pdf->download('Result_'.$exam->name.'_'.$classSection->name.'.pdf');
        }

        return view('exams.print_class_result', $data);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Exam::class);
        $ids = collect($request->ids ?? [])->filter()->values()->all();
        if ($ids === []) {
            return response()->json(['error' => __('exam.something_went_wrong')], 422);
        }

        $user = Auth::user();
        $isPrivilegedAdmin = $user->hasRole(['Super Admin', 'Head Officer', 'School Admin']);
        $query = Exam::whereIn('id', $ids);

        $allowedInstitutionIds = $this->getExamAllowedInstitutionIds();
        if (! empty($allowedInstitutionIds)) {
            $query->whereIn('institution_id', $allowedInstitutionIds);
        }

        if (! $isPrivilegedAdmin) {
            $query->whereNull('finalized_at');
        }

        $deleted = $query->delete();
        if ($deleted < 1) {
            return response()->json(['error' => __('exam.messages.delete_finalized_error')], 403);
        }

        return response()->json(['success' => __('exam.messages.success_delete')]);
    }
}