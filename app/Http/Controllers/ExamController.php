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

class ExamController extends BaseController
{
    public function __construct()
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
                ->whereIn('institution_id', $allowedInstitutionIds)
                ->select('exams.*')
                ->latest('exams.created_at'); // Rule 3: Latest First

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
                    
                    if($isFinalized && !$user->hasRole(['Super Admin', 'Head Officer'])) {
                        $canEdit = false;
                    }

                    if($canEdit){
                        $btn .= '<a href="'.route('exams.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }

                    if($user->can('delete', $row) && !$isFinalized){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('exams.index');
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

        return view('exams.create', compact('sessions', 'institutions'));
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

        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('exams')->where(function ($query) use ($session) {
                    return $query->where('academic_session_id', $session->id)
                                 ->where('institution_id', $session->institution_id);
                })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:scheduled,ongoing,completed,published',
            'description' => 'nullable|string',
        ]);

        $exam = new Exam($request->all());
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

        return view('exams.edit', compact('exam', 'sessions'));
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

        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('exams')->ignore($exam->id)->where(function ($query) use ($exam) {
                    return $query->where('academic_session_id', $exam->academic_session_id)
                                 ->where('institution_id', $exam->institution_id);
                })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:scheduled,ongoing,completed,published',
            'description' => 'nullable|string',
        ]);

        $exam->update($request->all());

        return response()->json(['message' => __('exam.messages.success_update'), 'redirect' => route('exams.index')]);
    }

    public function destroy(Exam $exam)
    {
        if($exam->finalized_at && !Auth::user()->hasRole(['Super Admin'])) {
            return response()->json(['message' => __('exam.messages.delete_finalized_error')], 403);
        }
        $exam->delete();
        return response()->json(['message' => __('exam.messages.success_delete')]);
    }

    public function finalize(Exam $exam)
    {
        $this->authorize('update', $exam);
        
        $exam->update(['finalized_at' => now(), 'status' => 'published']);
        
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

        $classSection = ClassSection::with('gradeLevel')->findOrFail($request->class_section_id);
        
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
            ->unique('id');

        $data = compact('exam', 'classSection', 'records', 'subjects');

        if ($request->has('download')) {
             $pdf = Pdf::loadView('exams.print_class_result', $data);
             return $pdf->download('Result_'.$exam->name.'_'.$classSection->name.'.pdf');
        }

        return view('exams.print_class_result', $data);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Exam::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            Exam::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('exam.messages.success_delete')]);
        }
        return response()->json(['error' => __('exam.something_went_wrong')]);
    }
}