<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentParent;
use App\Services\AttendanceAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AttendanceReportController extends BaseController
{
    protected $analyticsService;

    public function __construct(AttendanceAnalyticsService $analyticsService)
    {
        $this->middleware('auth');
        $this->setPageTitle(__('attendance.analytics_title'));
        $this->analyticsService = $analyticsService;
    }

    /**
     * Renders the List of Students for Staff and Parents.
     * Students bypass this and go straight to their analytics.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. If Student, skip the list and go straight to their personal analytics
        if ($user->hasRole('Student')) {
            return redirect()->route('attendance.analytics.show');
        }

        // 2. Authorize Staff/Admins (Parents bypass explicit permission as they own the data)
        if (!$user->hasRole('Guardian') && !$user->can('student_attendance.view')) {
            abort(403, 'Unauthorized access.');
        }

        if ($request->ajax()) {
            $query = Student::with(['classSection'])->select('students.*');

            if ($user->hasRole('Guardian')) {
                // Scope to Parent's children only
                $parent = StudentParent::where('user_id', $user->id)->first();
                $query->where('parent_id', $parent ? $parent->id : 0);
            } else {
                // Scope to Staff's Institution
                $institutionId = $this->getInstitutionId();
                if ($institutionId) {
                    $query->where('institution_id', $institutionId);
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name', function($row) {
                    return $row->first_name . ' ' . $row->last_name;
                })
                ->addColumn('class', function($row) {
                    return $row->classSection ? $row->classSection->name : __('attendance.not_assigned');
                })
                ->addColumn('action', function($row) {
                    $url = route('attendance.analytics.show', $row->id);
                    return '<a href="'.$url.'" class="btn btn-primary shadow btn-xs sharp me-1" title="'.__('attendance.view_analytics').'"><i class="fa fa-chart-line"></i></a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('attendance.reports.index');
    }

    /**
     * Renders the Comparative Analytics Dashboard for a specific student.
     */
    public function studentReport(Request $request, $id = null)
    {
        $user = Auth::user();
        
        // Secure resolution of which student is being queried
        if ($user->hasRole('Student')) {
            // Force lock to the logged-in student's ID
            $studentId = $user->student->id ?? abort(404, 'Student profile not found.');
        } elseif ($user->hasRole('Guardian')) {
            $studentId = $id;
            // Verify ownership
            $parent = StudentParent::where('user_id', $user->id)->first();
            $ownsChild = Student::where('id', $studentId)->where('parent_id', $parent->id ?? 0)->exists();
            if (!$ownsChild) abort(403, 'Unauthorized access to student record.');
        } else {
            // Must be admin/staff. Ensure they have permission to view.
            if (!$user->can('student_attendance.view')) abort(403);
            $studentId = $id;
        }

        $student = Student::findOrFail($studentId);
        
        // Prevent an admin in School A from viewing School B's student analytics
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) {
            abort(403, 'Unauthorized access to student record.');
        }

        $period = $request->get('period', 'week'); // 'week', 'month', 'quarter', 'semester', 'year'
        $stats = $this->analyticsService->getComparativeStats($student->id, $period);

        return view('attendance.reports.student_analytics', compact('student', 'stats', 'period'));
    }
}