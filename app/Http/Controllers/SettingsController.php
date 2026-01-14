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
        $examsGracePeriod = $settings['exams_grace_period'] ?? 30; 

        // Academic / LMD Defaults (FIXED: Added missing variables)
        $lmdThreshold = $settings['lmd_validation_threshold'] ?? 50;
        $gradingScale = isset($settings['grading_scale']) ? json_decode($settings['grading_scale'], true) : [];

        return view('settings.index', compact(
            'attendanceLocked', 
            'attendanceGracePeriod', 
            'examsLocked', 
            'examsGracePeriod',
            'lmdThreshold',
            'gradingScale'
        ));
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

            // Academic (FIXED: Added validation)
            'lmd_validation_threshold' => 'sometimes|numeric|min:0|max:100',
            'grade' => 'sometimes|array',
            'grade_min' => 'sometimes|array',
        ]);

        $keysToSave = [
            'attendance_locked' => 'attendance',
            'attendance_grace_period' => 'attendance',
            'exams_locked' => 'exams',
            'exams_grace_period' => 'exams',
            // Academic Keys
            'lmd_validation_threshold' => 'academic',
        ];

        foreach ($keysToSave as $key => $group) {
            if ($request->has($key)) {
                InstitutionSetting::set($institutionId, $key, $request->input($key), $group);
            }
        }

        // FIXED: Handle Grading Scale Logic (Convert Arrays to JSON)
        if ($request->has('grade')) {
            $scale = [];
            $grades = $request->grade;
            $mins = $request->grade_min;
            $remarks = $request->grade_remark;

            foreach ($grades as $index => $g) {
                if(!empty($g)) {
                    $scale[] = [
                        'grade' => $g,
                        'min' => $mins[$index] ?? 0,
                        'remark' => $remarks[$index] ?? ''
                    ];
                }
            }
            
            // Sort by min percentage descending
            usort($scale, fn($a, $b) => $b['min'] <=> $a['min']);
            
            InstitutionSetting::set($institutionId, 'grading_scale', json_encode($scale), 'academic');
        }

        if ($request->ajax()) {
            return response()->json(['message' => __('settings.messages.update_success')]);
        }

        return back()->with('success', __('settings.messages.update_success'));
    }
}