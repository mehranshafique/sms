<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TimetableController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Timetable::class, 'timetable');
        $this->setPageTitle(__('timetable.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();

        if ($request->ajax()) {
            $query = Timetable::with(['classSection.gradeLevel', 'subject', 'teacher.user'])
                ->select('timetables.*', 'timetables.day_of_week as day')
                ->latest('timetables.created_at');

            if ($institutionId) {
                $query->where('timetables.institution_id', $institutionId);
            }

            if ($user->hasRole('Teacher')) {
                $staff = $user->staff; 
                if ($staff) {
                    $query->where('timetables.teacher_id', $staff->id);
                } else {
                    $query->whereRaw('1 = 0'); 
                }
            } elseif ($user->hasRole('Student')) {
                $student = $user->student; 
                if ($student) {
                    $currentClassId = $student->enrollments()
                        ->where('status', 'active')
                        ->latest('created_at')
                        ->value('class_section_id');
                        
                    if($currentClassId) {
                        $query->where('timetables.class_section_id', $currentClassId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($request->has('class_section_id') && $request->class_section_id) {
                $query->where('timetables.class_section_id', $request->class_section_id);
            }
            // Optional: Filter by Grade if Class is not selected but Grade is
            if ($request->has('grade_level_id') && $request->grade_level_id && !$request->class_section_id) {
                $query->whereHas('classSection', function($q) use ($request) {
                    $q->where('grade_level_id', $request->grade_level_id);
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('delete', $row)){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('class', function($row){
                    $gradeName = $row->classSection->gradeLevel->name ?? '';
                    $sectionName = $row->classSection->name ?? 'N/A';
                    return $sectionName . ($gradeName ? ' (' . $gradeName . ')' : '');
                })
                ->addColumn('subject', function($row){
                    return $row->subject->name ?? 'N/A';
                })
                ->addColumn('teacher', function($row){
                    return $row->teacher ? $row->teacher->user->name : 'N/A';
                })
                ->editColumn('day', function($row){
                    return ucfirst($row->day);
                })
                ->addColumn('time', function($row){
                    return $row->start_time->format('h:i A') . ' - ' . $row->end_time->format('h:i A');
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if(auth()->user()->can('view', $row)){
                        $btn .= '<a href="'.route('timetables.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    }
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('timetables.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
        }

        $classSections = [];
        $gradeLevels = [];

        if (!$user->hasRole(['Student', 'Teacher'])) {
            // Fetch Grades
            $gradeLevelsQuery = GradeLevel::query();
            if ($institutionId) {
                $gradeLevelsQuery->where('institution_id', $institutionId);
            }
            $gradeLevels = $gradeLevelsQuery->orderBy('order_index')->pluck('name', 'id');

            // Initial Classes (All, or empty if we strictly depend on grade)
            // Keeping it empty initially forces user to select grade, or you can load all.
            // Let's load all initially to match previous behavior, but Filter JS will handle it.
            $classSectionsQuery = ClassSection::with('gradeLevel');
            if ($institutionId) {
                $classSectionsQuery->where('institution_id', $institutionId);
            }
            $classSections = $classSectionsQuery->get()->mapWithKeys(function($item) {
                 return [$item->id => $item->name];
            });
        }

        return view('timetables.index', compact('classSections', 'gradeLevels'));
    }

    public function classRoutine(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $user = Auth::user();
        
        $filters = [];
        $selectedClass = null;

        if ($user->hasRole('Teacher')) {
            $staff = $user->staff;
            if ($staff) {
                $filters['teacher_id'] = $staff->id;
                $request->request->remove('class_section_id'); 
                $request->request->remove('room_number');
            }
        } elseif ($user->hasRole('Student')) {
            $student = $user->student;
            if ($student) {
                $currentClassId = $student->enrollments()
                    ->where('status', 'active')
                    ->latest('created_at')
                    ->value('class_section_id');
                
                if ($currentClassId) {
                    $selectedClass = ClassSection::with(['classTeacher.user', 'institution', 'gradeLevel'])->find($currentClassId);
                    $request->merge(['class_section_id' => $currentClassId]); 
                }
                $request->request->remove('teacher_id');
                $request->request->remove('room_number');
            }
        } else {
            if ($request->has('class_section_id') && $request->class_section_id) {
                $selectedClass = ClassSection::with(['classTeacher.user', 'institution', 'gradeLevel'])->find($request->class_section_id);
            }
            if ($request->has('teacher_id') && $request->teacher_id) {
                $filters['teacher_id'] = $request->teacher_id;
            }
            if ($request->has('room_number') && $request->room_number) {
                $filters['room_number'] = $request->room_number;
            }
        }
        
        $classes = collect();
        $gradeLevels = collect();
        $teachers = collect();
        $rooms = collect();

        if (!$user->hasRole(['Student', 'Teacher'])) {
            $gradeLevelsQuery = GradeLevel::query();
            if ($institutionId) {
                $gradeLevelsQuery->where('institution_id', $institutionId);
            }
            $gradeLevels = $gradeLevelsQuery->orderBy('order_index')->pluck('name', 'id');

            // UPDATED: No concatenation
            $classesQuery = ClassSection::with('gradeLevel');
            if ($institutionId) $classesQuery->where('institution_id', $institutionId);
            $classes = $classesQuery->get()->mapWithKeys(function($item) {
                 return [$item->id => $item->name];
            });

            $teachersQuery = Staff::with('user');
            if ($institutionId) $teachersQuery->where('institution_id', $institutionId);
            $teachers = $teachersQuery->get()->mapWithKeys(fn($t)=>[$t->id => $t->user->name]);

            $roomsQuery = Timetable::select('room_number')->distinct()->whereNotNull('room_number');
            if ($institutionId) $roomsQuery->where('institution_id', $institutionId);
            $rooms = $roomsQuery->pluck('room_number', 'room_number');
        }

        if ($selectedClass || !empty($filters)) {
            return $this->generateWeeklyView($selectedClass, true, null, $classes, $teachers, $rooms, $filters, $gradeLevels);
        }

        return view('timetables.viewer', compact('classes', 'teachers', 'rooms', 'gradeLevels'));
    }

    private function generateWeeklyView($classSection = null, $isViewer = false, $timetable = null, $classes = null, $teachers = null, $rooms = null, $filters = [], $gradeLevels = null)
    {
        $data = $this->getScheduleData($classSection, $filters);
        
        $viewName = $isViewer ? 'timetables.viewer' : 'timetables.show';
        $timetable = $timetable ?? ($data['timetable'] ?? null);

        $classes = $classes ?? collect();
        $teachers = $teachers ?? collect();
        $rooms = $rooms ?? collect();
        $gradeLevels = $gradeLevels ?? collect();

        return view($viewName, array_merge($data, compact('classSection', 'classes', 'teachers', 'rooms', 'timetable', 'gradeLevels')));
    }

    private function getScheduleData($classSection = null, $filters = [])
    {
        // ... (No change to logic, just standard code) ...
        $institutionId = $this->getInstitutionId();

        if (!$institutionId) {
            if ($classSection) {
                $institutionId = $classSection->institution_id;
            } elseif (isset($filters['teacher_id'])) {
                $teacher = Staff::find($filters['teacher_id']);
                if ($teacher) $institutionId = $teacher->institution_id;
            }
        }
        
        $session = null;
        if ($institutionId) {
            $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        }

        $query = Timetable::with(['subject', 'teacher.user', 'classSection.gradeLevel', 'academicSession'])
            ->select('timetables.*')
            ->orderBy('timetables.start_time'); 

        if ($session) {
            $query->where('timetables.academic_session_id', $session->id);
        } else {
            $query->whereHas('academicSession', function($q) {
                $q->where('is_current', true);
            });
        }

        if ($classSection) {
            $query->where('timetables.class_section_id', $classSection->id);
        }
        if (isset($filters['teacher_id'])) {
            $query->where('timetables.teacher_id', $filters['teacher_id']);
        }
        if (isset($filters['room_number'])) {
            $query->where('timetables.room_number', $filters['room_number']);
        }

        $rawSchedules = $query->get();

        $schedules = $rawSchedules->groupBy(function($item) {
            return strtolower($item->day_of_week);
        });
            
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $weeklySchedule = collect($days)->mapWithKeys(function($day) use ($schedules) {
            return [$day => $schedules->get($day) ?? collect()];
        });

        $headerTitle = __('timetable.class_routine');
        if ($classSection) {
            $gradeName = $classSection->gradeLevel->name ?? '';
            $headerTitle = $classSection->name . ($gradeName ? ' (' . $gradeName . ')' : '');
        } elseif (isset($filters['teacher_id'])) {
            $t = Staff::with('user')->find($filters['teacher_id']);
            $headerTitle = $t ? __('timetable.teacher') . ": " . $t->user->name : __('timetable.teacher_schedule');
        } elseif (isset($filters['room_number'])) {
            $headerTitle = __('timetable.room') . ": " . $filters['room_number'];
        }

        $institution = $session ? $session->institution : ($rawSchedules->first() ? $rawSchedules->first()->institution : Institution::find($institutionId));
        $timetable = $rawSchedules->first();

        return compact('weeklySchedule', 'headerTitle', 'session', 'institution', 'timetable');
    }
    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        $institutions = [];
        if ($institutionId) {
            $institutions = Institution::where('id', $institutionId)->pluck('name', 'id');
        } elseif (Auth::user()->hasRole('Super Admin')) {
            $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        }

        // 1. Fetch Grade Levels (Dependent Dropdown Start)
        $gradeLevelsQuery = GradeLevel::query();
        if ($institutionId) {
            $gradeLevelsQuery->where('institution_id', $institutionId);
        } else {
            $gradeLevelsQuery->with('institution');
        }
        $gradeLevels = $gradeLevelsQuery->orderBy('order_index')->get()->mapWithKeys(function($item) use ($institutionId) {
             $label = $item->name;
             if (!$institutionId && $item->institution) {
                 $label .= ' (' . $item->institution->name . ')';
             }
             return [$item->id => $label];
        });

        // 2. Fetch Subjects & Teachers
        $fetchData = function($model) use ($institutionId) {
            $query = $model::query();
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
                return $query->pluck('name', 'id');
            } else {
                return $query->with('institution')->get()->mapWithKeys(function($item) {
                    return [$item->id => $item->name . ' (' . ($item->institution->code ?? 'N/A') . ')'];
                });
            }
        };

        $subjects = $fetchData(Subject::class);
        
        $teachersQuery = Staff::with(['user', 'institution']);
        if ($institutionId) {
            $teachersQuery->where('institution_id', $institutionId);
        }
        $teachers = $teachersQuery->get()->mapWithKeys(function($item) use ($institutionId){
            $label = $item->user->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->code . ')';
            }
            return [$item->id => $label];
        });

        return view('timetables.create', compact('gradeLevels', 'subjects', 'teachers', 'institutions', 'institutionId'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;
        $this->validateTimetable($request, null, $institutionId);

        if (!$institutionId) {
             $class = ClassSection::find($request->class_section_id);
             $institutionId = $class ? $class->institution_id : null;
        }

        $currentSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        $timetable = new Timetable();
        $timetable->fill($request->all());
        $timetable->institution_id = $institutionId;
        $timetable->academic_session_id = $currentSession->id;
        $timetable->day_of_week = strtolower($request->day);
        $timetable->teacher_id = $request->staff_id;
        $timetable->save();

        return response()->json(['message' => __('timetable.messages.success_create'), 'redirect' => route('timetables.index')]);
    }

    public function edit(Timetable $timetable)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $timetable->institution_id != $institutionId) abort(403);

        $institutions = Institution::where('id', $timetable->institution_id)->pluck('name', 'id');
        
        // 1. Fetch Grade Levels
        $gradeLevels = GradeLevel::where('institution_id', $timetable->institution_id)
            ->orderBy('order_index')
            ->pluck('name', 'id');

        // 2. Fetch Classes for current Grade (to allow editing within same grade)
        $currentClass = $timetable->classSection;
        $classes = ClassSection::where('grade_level_id', $currentClass->grade_level_id)
            ->where('institution_id', $timetable->institution_id)
            ->pluck('name', 'id');

        $subjects = Subject::where('institution_id', $timetable->institution_id)->pluck('name', 'id');
        $teachers = Staff::with('user')->where('institution_id', $timetable->institution_id)->get()->mapWithKeys(fn($t)=>[$t->id => $t->user->name]);

        return view('timetables.edit', compact('timetable', 'gradeLevels', 'classes', 'subjects', 'teachers', 'institutions', 'institutionId'));
    }

    public function update(Request $request, Timetable $timetable)
    {
        $institutionId = $this->getInstitutionId() ?? $timetable->institution_id;
        $this->validateTimetable($request, $timetable->id, $institutionId);

        $timetable->fill($request->all());
        $timetable->day_of_week = strtolower($request->day);
        $timetable->teacher_id = $request->staff_id;
        $timetable->save();

        return response()->json(['message' => __('timetable.messages.success_update'), 'redirect' => route('timetables.index')]);
    }

    public function destroy(Timetable $timetable)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $timetable->institution_id != $institutionId) abort(403);

        $timetable->delete();
        return response()->json(['message' => __('timetable.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Timetable::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            $query = Timetable::whereIn('id', $ids);
            if ($institutionId) $query->where('institution_id', $institutionId);
            $query->delete();
            return response()->json(['success' => __('timetable.messages.success_delete')]);
        }
        return response()->json(['error' => __('timetable.something_went_wrong')]);
    }

    private function validateTimetable(Request $request, $ignoreId = null, $institutionId = null)
    {
        $targetInstituteId = $institutionId ?? $request->institution_id;
        
        if (!$targetInstituteId && $request->class_section_id) {
            $class = ClassSection::find($request->class_section_id);
            if($class) $targetInstituteId = $class->institution_id;
        }

        $currentSession = AcademicSession::where('institution_id', $targetInstituteId)->where('is_current', true)->first();
        if(!$currentSession) {
            throw ValidationException::withMessages(['general' => __('timetable.no_active_session')]);
        }

        $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id'       => 'required|exists:subjects,id',
            'staff_id'         => 'nullable|exists:staff,id',
            'day'              => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time'       => 'required',
            'end_time'         => 'required',
        ]);

        $day = strtolower($request->day);
        $start = Carbon::parse($request->start_time)->format('H:i:s');
        $end = Carbon::parse($request->end_time)->format('H:i:s');

        if($end <= $start) {
            throw ValidationException::withMessages(['end_time' => 'End time must be after start time.']);
        }

        // Conflict Checks
        if ($request->staff_id) {
            $conflicts = Timetable::where('teacher_id', $request->staff_id)
                ->where('day_of_week', $day)
                ->where('academic_session_id', $currentSession->id)
                ->where('id', '!=', $ignoreId)
                ->get();
                
            foreach($conflicts as $conflict) {
                if ($conflict->start_time->format('H:i:s') < $end && $conflict->end_time->format('H:i:s') > $start) {
                     throw ValidationException::withMessages(['staff_id' => __('timetable.teacher_busy')]);
                }
            }
        }
    }
    
    public function printFiltered(Request $request) {
        $this->classRoutine($request); 
        $data = $this->getScheduleData(null, $request->all()); 
        if($request->class_section_id && !isset($data['timetable'])) {
             $data = $this->getScheduleData(ClassSection::find($request->class_section_id));
        }
        return view('timetables.print', $data);
    }
    
    public function downloadPdf($id) {
         $timetable = Timetable::with(['classSection', 'academicSession', 'institution'])->findOrFail($id);
         $data = $this->getScheduleData($timetable->classSection);
         $data['timetable'] = $timetable;
         if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('timetables.print', $data);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->download('Timetable.pdf');
        }
        return back();
    }
    
    public function print($id) {
        $timetable = Timetable::with(['classSection', 'academicSession', 'institution'])->findOrFail($id);
        $data = $this->getScheduleData($timetable->classSection);
        $data['timetable'] = $timetable; 
        return view('timetables.print', $data);
    }
}