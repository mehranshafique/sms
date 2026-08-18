<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Services\ApplicationGradeService;
use App\Services\AssessmentPeriodService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('settings.page_title'));
    }

    public function index()
    {
        $this->authorizeAdminOrAnyPermission(['setting.view', 'setting.manage']);

        $institutionId = $this->getInstitutionId();

        if (!$institutionId) {
            return redirect()->back()->with('error', __('settings.select_institution_first'));
        }

        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->pluck('value', 'key');

        $attendanceLocked = $settings['attendance_locked'] ?? 0;
        $attendanceGracePeriod = $settings['attendance_grace_period'] ?? 7;
        $autoNotifyAbsent = $settings['auto_notify_absent'] ?? 1;

        $examsLocked = $settings['exams_locked'] ?? 0;
        $examsGracePeriod = $settings['exams_grace_period'] ?? 30;

        $lmdThreshold = $settings['lmd_validation_threshold'] ?? 50;
        $gradingScale = isset($settings['grading_scale']) ? json_decode($settings['grading_scale'], true) : [];
        $applicationScale = isset($settings['application_scale'])
            ? json_decode($settings['application_scale'], true)
            : app(ApplicationGradeService::class)->primaryDefaults();
        if (!is_array($applicationScale) || $applicationScale === []) {
            $applicationScale = app(ApplicationGradeService::class)->primaryDefaults();
        }

        $activePeriods = isset($settings['active_periods']) ? json_decode($settings['active_periods'], true) : [];
        $blockReportsOnDebt = isset($settings['block_reports_on_debt']) ? (bool) $settings['block_reports_on_debt'] : false;
        $reportMinPaidAmounts = isset($settings['report_min_paid_amounts'])
            ? (json_decode($settings['report_min_paid_amounts'], true) ?: [])
            : [];
        $reportSealPosition = $settings['report_seal_position'] ?? 'center';
        $reportSealImage = $settings['report_seal_image'] ?? null;
        $resitPassPercentage = $settings['resit_pass_percentage'] ?? 50;

        $institution = Institution::find($institutionId);
        $academicSession = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        $periodStates = [];
        $termCloseStatus = [];
        if ($academicSession) {
            $periodService = app(AssessmentPeriodService::class);
            $periodStates = $periodService->dashboardRows(
                (int) $institutionId,
                (int) $academicSession->id,
                $institution?->type instanceof \BackedEnum
                    ? $institution->type->value
                    : ($institution->type ?? null)
            );
            foreach (['trimester_1', 'trimester_2', 'trimester_3', 'semester_1', 'semester_2'] as $termKey) {
                $termCloseStatus[$termKey] = $periodService->termClosed(
                    (int) $institutionId,
                    (int) $academicSession->id,
                    $termKey
                );
            }
        }

        return view('settings.index', compact(
            'attendanceLocked',
            'attendanceGracePeriod',
            'autoNotifyAbsent',
            'examsLocked',
            'examsGracePeriod',
            'lmdThreshold',
            'gradingScale',
            'applicationScale',
            'activePeriods',
            'blockReportsOnDebt',
            'reportMinPaidAmounts',
            'reportSealPosition',
            'reportSealImage',
            'resitPassPercentage',
            'periodStates',
            'termCloseStatus',
            'academicSession'
        ));
    }

    public function update(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if (!$institutionId) {
            abort(403, 'No active institution context.');
        }

        $user = Auth::user();
        if (!$user->can('setting.manage') && !$this->userIsSchoolAdmin($user)) {
            abort(403);
        }

        $request->validate([
            'attendance_locked' => 'sometimes|boolean',
            'attendance_grace_period' => 'sometimes|integer|min:0|max:365',
            'auto_notify_absent' => 'sometimes|boolean',
            'exams_locked' => 'sometimes|boolean',
            'exams_grace_period' => 'sometimes|integer|min:0|max:365',
            'lmd_validation_threshold' => 'sometimes|numeric|min:0|max:100',
            'grade' => 'sometimes|array',
            'grade_min' => 'sometimes|array',
            'active_periods' => 'sometimes|array',
            'report_seal_position' => 'sometimes|in:left,center,right,none',
            'report_seal_image' => 'sometimes|nullable|image|max:2048',
            'resit_pass_percentage' => 'sometimes|numeric|min:0|max:100',
            'app_grade' => 'sometimes|array',
            'app_min' => 'sometimes|array',
            'report_min_paid' => 'sometimes|array',
            'report_min_paid.*' => 'nullable|numeric|min:0',
        ]);

        $keysToSave = [
            'attendance_locked' => 'attendance',
            'attendance_grace_period' => 'attendance',
            'auto_notify_absent' => 'attendance',
            'exams_locked' => 'exams',
            'exams_grace_period' => 'exams',
            'lmd_validation_threshold' => 'academic',
            'block_reports_on_debt' => 'academic',
        ];

        foreach ($keysToSave as $key => $group) {
            if ($request->has($key)) {
                InstitutionSetting::set($institutionId, $key, $request->input($key), $group);
            }
        }

        $blockReports = $request->has('block_reports_on_debt') ? 1 : 0;
        InstitutionSetting::set($institutionId, 'block_reports_on_debt', $blockReports, 'academic');

        if ($request->has('report_min_paid') || $request->has('block_reports_on_debt') || $request->has('active_periods') || $request->has('lmd_validation_threshold')) {
            $mins = [];
            foreach ((array) $request->input('report_min_paid', []) as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $mins[$key] = (float) $value;
            }
            InstitutionSetting::set($institutionId, 'report_min_paid_amounts', json_encode($mins), 'academic');
        }
        if ($request->has('active_periods')) {
            InstitutionSetting::set($institutionId, 'active_periods', json_encode($request->active_periods), 'academic');
        } elseif ($request->has('lmd_validation_threshold')) {
            $periods = $request->input('active_periods', []);
            InstitutionSetting::set($institutionId, 'active_periods', json_encode($periods), 'academic');
        }

        if ($request->has('grade')) {
            $scale = [];
            $grades = $request->grade;
            $mins = $request->grade_min;
            $remarks = $request->grade_remark;

            foreach ($grades as $index => $g) {
                if (!empty($g)) {
                    $scale[] = [
                        'grade' => $g,
                        'min' => $mins[$index] ?? 0,
                        'remark' => $remarks[$index] ?? '',
                    ];
                }
            }

            usort($scale, fn ($a, $b) => $b['min'] <=> $a['min']);
            InstitutionSetting::set($institutionId, 'grading_scale', json_encode($scale), 'academic');
        }

        if ($request->has('app_grade')) {
            $appScale = [];
            $grades = $request->app_grade;
            $mins = $request->app_min;
            $labels = $request->app_label ?? [];

            foreach ($grades as $index => $g) {
                if (!empty($g)) {
                    $appScale[] = [
                        'grade' => $g,
                        'min' => (float) ($mins[$index] ?? 0),
                        'label' => $labels[$index] ?? '',
                    ];
                }
            }

            usort($appScale, fn ($a, $b) => $b['min'] <=> $a['min']);
            InstitutionSetting::set($institutionId, 'application_scale', json_encode($appScale), 'academic');
        }

        if ($request->filled('report_seal_position')) {
            InstitutionSetting::set($institutionId, 'report_seal_position', $request->report_seal_position, 'academic');
        }

        if ($request->filled('resit_pass_percentage')) {
            InstitutionSetting::set($institutionId, 'resit_pass_percentage', $request->resit_pass_percentage, 'academic');
        }

        if ($request->hasFile('report_seal_image')) {
            $existing = InstitutionSetting::get($institutionId, 'report_seal_image');
            if ($existing && Storage::disk('public')->exists($existing)) {
                Storage::disk('public')->delete($existing);
            }
            $path = $request->file('report_seal_image')->store("institutions/{$institutionId}/seals", 'public');
            InstitutionSetting::set($institutionId, 'report_seal_image', $path, 'academic');
        }

        if ($request->boolean('remove_report_seal_image')) {
            $existing = InstitutionSetting::get($institutionId, 'report_seal_image');
            if ($existing && Storage::disk('public')->exists($existing)) {
                Storage::disk('public')->delete($existing);
            }
            InstitutionSetting::set($institutionId, 'report_seal_image', '', 'academic');
        }

        if ($request->ajax()) {
            return response()->json(['message' => __('settings.messages.update_success')]);
        }

        return back()->with('success', __('settings.messages.update_success'));
    }
}
