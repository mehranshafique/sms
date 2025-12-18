<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TimetableController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Timetable::class, 'timetable');
        $this->setPageTitle(__('timetable.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = Timetable::with(['classSection', 'subject', 'teacher.user'])
                ->select('timetables.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            if ($request->has('class_section_id') && $request->class_section_id) {
                $data->where('class_section_id', $request->class_section_id);
            }

            return DataTables::of($data)
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
                    return $row->classSection->name ?? 'N/A';
                })
                ->addColumn('subject', function($row){
                    return $row->subject->name ?? 'N/A';
                })
                ->addColumn('teacher', function($row){
                    return $row->teacher ? $row->teacher->user->name : 'N/A';
                })
                ->editColumn('day', function($row){
                    // Model uses day_of_week
                    return ucfirst($row->day_of_week);
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

        $classSectionsQuery = ClassSection::query();
        if ($institutionId) {
            $classSectionsQuery->where('institution_id', $institutionId);
        }
        $classSections = $classSectionsQuery->pluck('name', 'id');

        return view('timetables.index', compact('classSections'));
    }

    public function create()
    {
        $institutionId = Auth::user()->institute_id;
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        
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

        $classes = $fetchData(ClassSection::class);
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

        return view('timetables.create', compact('classes', 'subjects', 'teachers', 'institutions'));
    }

    public function store(Request $request)
    {
        $userInstituteId = Auth::user()->institute_id;
        $targetInstituteId = $userInstituteId ?? $request->institution_id;

        if (!$targetInstituteId) {
             $class = ClassSection::find($request->class_section_id);
             $targetInstituteId = $class ? $class->institution_id : null;
        }

        $currentSession = AcademicSession::where('institution_id', $targetInstituteId)->where('is_current', true)->first();

        if(!$currentSession) {
            throw ValidationException::withMessages(['general' => __('timetable.no_active_session')]);
        }

        // Form still sends 'day' and 'staff_id'
        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id'       => 'required|exists:subjects,id',
            'staff_id'         => 'nullable|exists:staff,id',
            'day'              => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time'       => 'required',
            'end_time'         => 'required|after:start_time',
            'room_number'      => 'nullable|string|max:50',
        ]);

        if (!empty($validated['staff_id'])) {
            // Check conflict using teacher_id and day_of_week
            $conflict = Timetable::where('teacher_id', $validated['staff_id'])
                ->where('day_of_week', $validated['day'])
                ->where('academic_session_id', $currentSession->id)
                ->where(function($q) use ($validated) {
                    $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
                })
                ->exists();
            
            if ($conflict) {
                throw ValidationException::withMessages(['staff_id' => __('timetable.teacher_busy')]);
            }
        }

        $timetable = new Timetable();
        // Fill safe attributes (start_time, end_time, room_number, etc.)
        $timetable->fill($validated);
        
        $timetable->institution_id = $targetInstituteId;
        $timetable->academic_session_id = $currentSession->id;
        
        // Map form fields to model columns
        $timetable->day_of_week = $validated['day']; 
        $timetable->teacher_id = $validated['staff_id'] ?? null; 
        
        $timetable->save();

        return response()->json(['message' => __('timetable.messages.success_create'), 'redirect' => route('timetables.index')]);
    }

    public function show(Timetable $timetable)
    {
        $timetable->load(['classSection.classTeacher.user', 'subject', 'teacher.user', 'academicSession', 'institution']);

        // Fetch full schedule for the class using day_of_week
        $schedules = Timetable::with(['subject', 'teacher.user'])
            ->where('class_section_id', $timetable->class_section_id)
            ->where('academic_session_id', $timetable->academic_session_id)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week'); // Group by correct column
            
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $weeklySchedule = collect($days)->mapWithKeys(function($day) use ($schedules) {
            return [$day => $schedules->get($day) ?? collect()];
        });

        return view('timetables.show', compact('timetable', 'weeklySchedule'));
    }

    public function edit(Timetable $timetable)
    {
        $institutionId = Auth::user()->institute_id;
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');

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

        $classes = $fetchData(ClassSection::class);
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

        return view('timetables.edit', compact('timetable', 'classes', 'subjects', 'teachers', 'institutions'));
    }

    public function update(Request $request, Timetable $timetable)
    {
        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'subject_id'       => 'required|exists:subjects,id',
            'staff_id'         => 'nullable|exists:staff,id',
            'day'              => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time'       => 'required',
            'end_time'         => 'required|after:start_time',
            'room_number'      => 'nullable|string|max:50',
        ]);

        if (!empty($validated['staff_id'])) {
            $conflict = Timetable::where('teacher_id', $validated['staff_id']) // Use teacher_id
                ->where('id', '!=', $timetable->id)
                ->where('day_of_week', $validated['day']) // Use day_of_week
                ->where('academic_session_id', $timetable->academic_session_id)
                ->where(function($q) use ($validated) {
                    $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']]);
                })
                ->exists();
            
            if ($conflict) {
                throw ValidationException::withMessages(['staff_id' => __('timetable.teacher_busy')]);
            }
        }

        $timetable->fill($validated);
        // Map form fields to model columns
        $timetable->day_of_week = $validated['day']; 
        $timetable->teacher_id = $validated['staff_id'] ?? null; 
        $timetable->save();

        return response()->json(['message' => __('timetable.messages.success_update'), 'redirect' => route('timetables.index')]);
    }

    public function destroy(Timetable $timetable)
    {
        $timetable->delete();
        return response()->json(['message' => __('timetable.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Timetable::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            Timetable::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('timetable.messages.success_delete')]);
        }
        return response()->json(['error' => __('timetable.something_went_wrong')]);
    }
}