<?php

namespace App\Http\Controllers;

use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends BaseController
{
    public function __construct()
    {
        // Assuming 'settings.manage' permission exists or role check
        // $this->middleware('permission:settings.manage'); 
        $this->setPageTitle(__('settings.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        
        if (!$institutionId) {
            return redirect()->back()->with('error', __('settings.select_institution_first'));
        }

        // Fetch all settings for this institution
        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->pluck('value', 'key');

        // Attendance Defaults
        $attendanceLocked = $settings['attendance_locked'] ?? 0;
        $attendanceGracePeriod = $settings['attendance_grace_period'] ?? 7;

        // Exam Defaults
        $examsLocked = $settings['exams_locked'] ?? 0;
        $examsGracePeriod = $settings['exams_grace_period'] ?? 30; // Default 30 days for exams

        return view('settings.index', compact('attendanceLocked', 'attendanceGracePeriod', 'examsLocked', 'examsGracePeriod'));
    }

    public function update(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if (!$institutionId) {
            abort(403, 'No active institution context.');
        }

        $request->validate([
            // Attendance
            'attendance_locked' => 'sometimes|boolean',
            'attendance_grace_period' => 'sometimes|integer|min:0|max:365',
            
            // Exams
            'exams_locked' => 'sometimes|boolean',
            'exams_grace_period' => 'sometimes|integer|min:0|max:365',
        ]);

        $keysToSave = [
            'attendance_locked' => 'attendance',
            'attendance_grace_period' => 'attendance',
            'exams_locked' => 'exams',
            'exams_grace_period' => 'exams',
        ];

        foreach ($keysToSave as $key => $group) {
            if ($request->has($key)) {
                InstitutionSetting::set($institutionId, $key, $request->input($key), $group);
            }
        }

        return back()->with('success', __('settings.messages.update_success'));
    }
}