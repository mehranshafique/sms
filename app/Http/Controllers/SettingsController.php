<?php

namespace App\Http\Controllers;

use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends BaseController
{
    public function __construct()
    {
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

        // Academic / LMD Defaults
        $lmdThreshold = $settings['lmd_validation_threshold'] ?? 50;
        $gradingScale = isset($settings['grading_scale']) ? json_decode($settings['grading_scale'], true) : [];

        // NEW: Active Periods (for Marks Entry Control)
        $activePeriods = isset($settings['active_periods']) ? json_decode($settings['active_periods'], true) : [];

        return view('settings.index', compact(
            'attendanceLocked', 
            'attendanceGracePeriod', 
            'examsLocked', 
            'examsGracePeriod',
            'lmdThreshold',
            'gradingScale',
            'activePeriods' // Pass to view
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

            // Academic
            'lmd_validation_threshold' => 'sometimes|numeric|min:0|max:100',
            'grade' => 'sometimes|array',
            'grade_min' => 'sometimes|array',
            
            // NEW: Active Periods
            'active_periods' => 'sometimes|array'
        ]);

        $keysToSave = [
            'attendance_locked' => 'attendance',
            'attendance_grace_period' => 'attendance',
            'exams_locked' => 'exams',
            'exams_grace_period' => 'exams',
            'lmd_validation_threshold' => 'academic',
        ];

        foreach ($keysToSave as $key => $group) {
            if ($request->has($key)) {
                InstitutionSetting::set($institutionId, $key, $request->input($key), $group);
            }
        }

        // Handle Active Periods (Save as JSON)
        if ($request->has('active_periods')) {
            InstitutionSetting::set($institutionId, 'active_periods', json_encode($request->active_periods), 'academic');
        } else {
            // If empty (all unchecked), save empty array if form was submitted
            // Check a hidden field to confirm form submission type if needed, 
            // but usually 'sometimes' validation handles it. 
            // Better: If we are updating academic settings specifically:
            if ($request->has('lmd_validation_threshold')) { 
                 // Assuming this is the academic form
                 $periods = $request->input('active_periods', []);
                 InstitutionSetting::set($institutionId, 'active_periods', json_encode($periods), 'academic');
            }
        }

        // Handle Grading Scale Logic
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