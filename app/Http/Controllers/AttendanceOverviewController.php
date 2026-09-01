<?php

namespace App\Http\Controllers;

use App\Services\AttendanceOverviewService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class AttendanceOverviewController extends BaseController
{
    public function __construct(protected AttendanceOverviewService $overview)
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class.':student_attendance.view');
        $this->setPageTitle(__('attendance.overview_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        abort_unless($institutionId, 403);

        $date = $this->resolveDate($request->input('date'));
        $data = $this->overview->forInstitution((int) $institutionId, $date);
        $canViewStaff = $this->canViewStaffAttendance();

        if (! $canViewStaff) {
            $data['staff'] = [
                'expected' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'not_checked_in' => 0,
                'marked' => 0,
                'rate' => 0,
            ];
        }

        return view('attendance.overview', [
            'overview' => $data,
            'date' => $date->toDateString(),
            'canViewStaff' => $canViewStaff,
        ]);
    }

    public function details(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        abort_unless($institutionId, 403);

        $request->validate([
            'audience' => 'required|in:students,staff',
            'bucket' => 'required|in:expected,present,absent,late,not_checked_in',
            'date' => 'nullable|date',
            'class_section_id' => 'nullable|integer',
        ]);

        $audience = $request->input('audience');
        if ($audience === 'staff' && ! $this->canViewStaffAttendance()) {
            abort(403);
        }

        $date = $this->resolveDate($request->input('date'));
        $rows = $this->overview->details(
            (int) $institutionId,
            $audience,
            $request->input('bucket'),
            $date,
            $request->filled('class_section_id') ? (int) $request->input('class_section_id') : null
        );

        return response()->json([
            'date' => $date->toDateString(),
            'audience' => $audience,
            'bucket' => $request->input('bucket'),
            'count' => count($rows),
            'rows' => $rows,
        ]);
    }

    protected function resolveDate(?string $date): Carbon
    {
        if ($date) {
            try {
                return Carbon::parse($date)->startOfDay();
            } catch (\Throwable) {
                // fall through
            }
        }

        return Carbon::today()->startOfDay();
    }

    protected function canViewStaffAttendance(): bool
    {
        $user = Auth::user();
        if ($user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            return true;
        }

        try {
            return $user->can('staff_attendance.view');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }
    }
}
