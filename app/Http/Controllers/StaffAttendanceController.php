<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffAttendanceController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Add permission check: $this->middleware('permission:staff_attendance.view');
        $this->setPageTitle(__('attendance.staff_attendance_title'));
    }

     public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = StaffAttendance::with(['staff.user'])
                ->select('staff_attendances.*')
                // FIXED: Explicitly join staff and users tables to allow searching/sorting by the user's name
                ->leftJoin('staff', 'staff_attendances.staff_id', '=', 'staff.id')
                ->leftJoin('users', 'staff.user_id', '=', 'users.id')
                ->latest('staff_attendances.attendance_date');

            if ($institutionId) {
                // FIXED: Added the 'staff_attendances.' prefix to resolve ambiguity
                $data->where('staff_attendances.institution_id', $institutionId);
            }

            if ($request->filled('date')) {
                // FIXED: Prefix added here as well for safety
                $data->whereDate('staff_attendances.attendance_date', $request->date);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('staff_name', function($row){
                    return $row->staff->full_name ?? $row->staff->user->name ?? 'N/A';
                })
                // FIXED: Intercept the DataTables auto-join for 'staff.first_name' and redirect it to 'users.name'
                ->filterColumn('staff.first_name', function($query, $keyword) {
                    $query->where('users.name', 'like', "%{$keyword}%");
                })
                ->orderColumn('staff.first_name', function ($query, $order) {
                    $query->orderBy('users.name', $order);
                })
                // Safety fallback if the frontend uses 'staff_name' as the name parameter
                ->filterColumn('staff_name', function($query, $keyword) {
                    $query->where('users.name', 'like', "%{$keyword}%");
                })
                ->orderColumn('staff_name', function ($query, $order) {
                    $query->orderBy('users.name', $order);
                })
                ->editColumn('status', function($row) {
                    $badges = [
                        'present' => 'badge-success',
                        'absent' => 'badge-danger',
                        'late' => 'badge-warning',
                        'excused' => 'badge-info',
                        'half_day' => 'badge-secondary'
                    ];
                    $class = $badges[$row->status] ?? 'badge-dark';
                    return '<span class="badge '.$class.'">'.ucfirst(str_replace('_', ' ', $row->status)).'</span>';
                })
                ->editColumn('attendance_date', function($row) {
                    return Carbon::parse($row->attendance_date)->format('d M, Y');
                })
                ->editColumn('check_in', function($row){
                    return $row->check_in ? Carbon::parse($row->check_in)->format('h:i A') : '-';
                })
                ->editColumn('check_out', function($row){
                    return $row->check_out ? Carbon::parse($row->check_out)->format('h:i A') : '-';
                })
                ->rawColumns(['status'])
                ->make(true);
        }

        return view('attendance.staff.index');
    }

    public function create(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $date = $request->date ?? date('Y-m-d');

        // Fetch Staff
        $staffMembers = Staff::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->get();

        // Fetch Existing Attendance for Date
        $attendance = StaffAttendance::where('institution_id', $institutionId)
            ->where('attendance_date', $date)
            ->get()
            ->keyBy('staff_id');

        return view('attendance.staff.create', compact('staffMembers', 'attendance', 'date'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|in:present,absent,late,excused,half_day',
            'attendance.*.check_in' => 'nullable', // Time format validation can be strict if needed
            'attendance.*.check_out' => 'nullable',
        ]);

        $institutionId = $this->getInstitutionId();

        DB::transaction(function () use ($request, $institutionId) {
            foreach ($request->attendance as $staffId => $data) {
                
                // Only update if status is set (or default to absent if unchecked logic is used elsewhere)
                StaffAttendance::updateOrCreate(
                    [
                        'institution_id' => $institutionId,
                        'staff_id' => $staffId,
                        'attendance_date' => $request->date,
                    ],
                    [
                        'status' => $data['status'],
                        'check_in' => $data['check_in'] ?? null,
                        'check_out' => $data['check_out'] ?? null,
                        'method' => 'manual',
                        'marked_by' => Auth::id(),
                    ]
                );
            }
        });

        return redirect()->route('staff-attendance.index')->with('success', __('attendance.messages.success_marked'));
    }
}