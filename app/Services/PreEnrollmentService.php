<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\Institution;
use App\Models\PreEnrollment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentParent;
use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PreEnrollmentService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function register(array $data, int $institutionId, string $source = 'admin', ?int $userId = null): PreEnrollment
    {
        $sessionId = $data['academic_session_id']
            ?? AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->value('id');

        // Parents often re-send the WhatsApp flow; reuse the open candidate file
        // instead of creating a second temporary ID for the same child.
        if ($source !== 'admin') {
            $existing = $this->findDuplicate($institutionId, $data);
            if ($existing) {
                return $existing;
            }
        }

        $pre = PreEnrollment::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $sessionId,
            'temporary_id' => $this->nextTemporaryId($institutionId),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'post_name' => $data['post_name'] ?? null,
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'place_of_birth' => $data['place_of_birth'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'parent_phone' => $data['parent_phone'] ?? null,
            'parent_email' => $data['parent_email'] ?? null,
            'student_parent_id' => $data['student_parent_id'] ?? null,
            'requested_grade_level_id' => $data['requested_grade_level_id'] ?? null,
            'requested_class_section_id' => $data['requested_class_section_id'] ?? null,
            'requested_option' => $data['requested_option'] ?? null,
            'status' => PreEnrollment::STATUS_PRE_ENROLLED,
            'source' => $source,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId ?? Auth::id(),
        ]);

        $this->notify($pre->load('institution', 'requestedGradeLevel', 'requestedClassSection'), 'pre_enrollment_received');

        return $pre;
    }

    /**
     * An open candidate file for the same child (same names + parent phone).
     *
     * @param  array<string, mixed>  $data
     */
    public function findDuplicate(int $institutionId, array $data, ?int $ignoreId = null): ?PreEnrollment
    {
        if (empty($data['first_name']) || empty($data['last_name'])) {
            return null;
        }

        return PreEnrollment::where('institution_id', $institutionId)
            ->where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->when(! empty($data['parent_phone']), fn ($q) => $q->where('parent_phone', $data['parent_phone']))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereNotIn('status', [PreEnrollment::STATUS_FINALIZED, PreEnrollment::STATUS_NOT_ADMITTED])
            ->latest('id')
            ->first();
    }

    /** Correct candidate details before the file is converted into a student. */
    public function updateCandidate(PreEnrollment $pre, array $data): PreEnrollment
    {
        if ($pre->status === PreEnrollment::STATUS_FINALIZED) {
            throw new \RuntimeException(__('pre_enrollment.errors.finalized_locked'));
        }

        $pre->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'post_name' => $data['post_name'] ?? null,
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'place_of_birth' => $data['place_of_birth'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'parent_phone' => $data['parent_phone'] ?? null,
            'parent_email' => $data['parent_email'] ?? null,
            'requested_grade_level_id' => $data['requested_grade_level_id'] ?? null,
            'requested_class_section_id' => $data['requested_class_section_id'] ?? null,
            'requested_option' => $data['requested_option'] ?? null,
            'academic_session_id' => $data['academic_session_id'] ?? $pre->academic_session_id,
            'notes' => $data['notes'] ?? null,
        ]);

        return $pre->fresh();
    }

    public function deleteCandidate(PreEnrollment $pre): void
    {
        if ($pre->status === PreEnrollment::STATUS_FINALIZED || $pre->converted_student_id) {
            throw new \RuntimeException(__('pre_enrollment.errors.finalized_locked'));
        }

        $pre->delete();
    }

    public function inviteForTest(PreEnrollment $pre, array $data): PreEnrollment
    {
        if ($pre->status === PreEnrollment::STATUS_FINALIZED) {
            throw new \RuntimeException(__('pre_enrollment.errors.finalized_locked'));
        }

        $pre->update([
            'status' => PreEnrollment::STATUS_INVITED,
            'test_at' => $data['test_at'] ?? null,
            'test_location' => $data['test_location'] ?? null,
            'test_notes' => $data['test_notes'] ?? null,
        ]);

        $fresh = $pre->fresh(['institution', 'requestedGradeLevel', 'requestedClassSection']);
        $this->notify($fresh, 'pre_enrollment_test_invite');

        return $fresh;
    }

    public function sendTestReminder(PreEnrollment $pre): PreEnrollment
    {
        if (! $pre->test_at) {
            throw new \RuntimeException(__('pre_enrollment.errors.no_test_date'));
        }

        $this->notify($pre->loadMissing(['institution', 'requestedGradeLevel', 'requestedClassSection']), 'pre_enrollment_test_reminder');

        return $pre;
    }

    public function recordTestResult(PreEnrollment $pre, array $data): PreEnrollment
    {
        if ($pre->status === PreEnrollment::STATUS_FINALIZED) {
            throw new \RuntimeException(__('pre_enrollment.errors.finalized_locked'));
        }

        $result = $data['test_result']; // pass|fail
        $status = $result === 'pass'
            ? PreEnrollment::STATUS_ADMITTED
            : PreEnrollment::STATUS_NOT_ADMITTED;

        $pre->update([
            'status' => $status,
            'test_score' => $data['test_score'] ?? null,
            'test_result' => $result,
            'test_notes' => $data['test_notes'] ?? $pre->test_notes,
        ]);

        $fresh = $pre->fresh(['institution', 'requestedGradeLevel', 'requestedClassSection']);
        $event = $result === 'pass' ? 'pre_enrollment_admitted' : 'pre_enrollment_not_admitted';
        $this->notify($fresh, $event);

        if ($result === 'pass') {
            $this->notify($fresh, 'pre_enrollment_finalize_invite');
        }

        return $fresh;
    }

    public function finalizeEnrollment(PreEnrollment $pre, array $data): Student
    {
        if ($pre->status === PreEnrollment::STATUS_FINALIZED && $pre->converted_student_id) {
            return Student::findOrFail($pre->converted_student_id);
        }

        if ($pre->status !== PreEnrollment::STATUS_ADMITTED && ($data['force'] ?? false) !== true) {
            throw new \RuntimeException(__('pre_enrollment.errors.not_admitted'));
        }

        return DB::transaction(function () use ($pre, $data) {
            $institution = Institution::findOrFail($pre->institution_id);
            $session = AcademicSession::find(
                $data['academic_session_id']
                    ?? $pre->academic_session_id
                    ?? AcademicSession::where('institution_id', $pre->institution_id)->where('is_current', true)->value('id')
            );

            if (! $session) {
                throw new \RuntimeException(__('pre_enrollment.errors.no_session'));
            }

            $classSectionId = $data['class_section_id'] ?? $pre->requested_class_section_id;
            $gradeLevelId = $data['grade_level_id'] ?? $pre->requested_grade_level_id;

            if ($classSectionId) {
                $section = ClassSection::findOrFail($classSectionId);
                $gradeLevelId = $gradeLevelId ?: $section->grade_level_id;
            }

            if (! $classSectionId || ! $gradeLevelId) {
                throw new \RuntimeException(__('pre_enrollment.errors.class_required'));
            }

            // Student records require gender and date of birth; WhatsApp candidates
            // may not have supplied them, so they can be completed at finalization.
            $gender = $data['gender'] ?? $pre->gender;
            $dob = $data['dob'] ?? optional($pre->dob)->toDateString();

            if (! $gender || ! $dob) {
                throw new \RuntimeException(__('pre_enrollment.errors.identity_required'));
            }

            if ($gender !== $pre->gender || $dob !== optional($pre->dob)->toDateString()) {
                $pre->update(['gender' => $gender, 'dob' => $dob]);
                $pre->refresh();
            }

            $parent = $pre->student_parent_id
                ? StudentParent::where('institution_id', $pre->institution_id)->find($pre->student_parent_id)
                : null;

            if (! $parent && $pre->parent_phone) {
                $phoneDigits = preg_replace('/\D+/', '', (string) $pre->parent_phone);
                $parent = StudentParent::where('institution_id', $pre->institution_id)
                    ->where(function ($q) use ($pre, $phoneDigits) {
                        $q->where('father_phone', $pre->parent_phone)
                            ->orWhere('mother_phone', $pre->parent_phone)
                            ->orWhere('guardian_phone', $pre->parent_phone);
                        if (strlen($phoneDigits) >= 8) {
                            $q->orWhere('father_phone', 'like', "%{$phoneDigits}%")
                                ->orWhere('mother_phone', 'like', "%{$phoneDigits}%")
                                ->orWhere('guardian_phone', 'like', "%{$phoneDigits}%");
                        }
                    })
                    ->first();
            }

            if (! $parent && ($pre->parent_name || $pre->parent_phone)) {
                $parent = StudentParent::create([
                    'institution_id' => $pre->institution_id,
                    'father_name' => $pre->parent_name,
                    'father_phone' => $pre->parent_phone,
                    'guardian_name' => $pre->parent_name,
                    'guardian_phone' => $pre->parent_phone,
                    'guardian_email' => $pre->parent_email,
                ]);
            }

            $admissionNumber = IdGeneratorService::generateStudentId($institution, $session);
            $plainPassword = $data['password'] ?? ('Student' . random_int(1000, 9999) . '!');
            $email = $pre->parent_email ?: ($admissionNumber . '@school.com');

            if (User::where('email', $email)->exists()) {
                $email = $admissionNumber . '+' . time() . '@school.com';
            }

            $user = User::create([
                'name' => trim($pre->first_name . ' ' . $pre->last_name),
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'phone' => $pre->parent_phone,
                'shortcode' => $admissionNumber,
                'username' => $admissionNumber,
            ]);
            $user->forceFill([
                'institute_id' => $pre->institution_id,
                'is_active' => true,
            ])->save();

            try {
                app(\App\Services\RoleAssignmentService::class)
                    ->assign($user, RoleEnum::STUDENT->value, (int) $pre->institution_id);
            } catch (\Throwable $e) {
                try {
                    $user->assignRole(RoleEnum::STUDENT->value);
                } catch (\Throwable $e2) {
                    report($e2);
                }
            }

            $student = Student::create([
                'institution_id' => $pre->institution_id,
                'parent_id' => $parent?->id,
                'user_id' => $user->id,
                'admission_number' => $admissionNumber,
                'admission_date' => now()->toDateString(),
                'first_name' => $pre->first_name,
                'last_name' => $pre->last_name,
                'post_name' => $pre->post_name,
                'gender' => $gender,
                'dob' => $dob,
                'place_of_birth' => $pre->place_of_birth,
                'grade_level_id' => $gradeLevelId,
                'class_section_id' => $classSectionId,
                'mobile_number' => $pre->parent_phone,
                'email' => $email,
                'status' => 'active',
            ]);

            StudentEnrollment::create([
                'institution_id' => $pre->institution_id,
                'academic_session_id' => $session->id,
                'student_id' => $student->id,
                'grade_level_id' => $gradeLevelId,
                'class_section_id' => $classSectionId,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);

            $pre->update([
                'status' => PreEnrollment::STATUS_FINALIZED,
                'converted_student_id' => $student->id,
                'requested_class_section_id' => $classSectionId,
                'requested_grade_level_id' => $gradeLevelId,
                'finalized_at' => now(),
            ]);

            try {
                $this->notifications->sendUserCredentials(
                    $user,
                    $plainPassword,
                    RoleEnum::STUDENT->value,
                    $student,
                    $parent
                );
            } catch (\Throwable $e) {
                report($e);
            }

            $this->notify($pre->fresh(['institution', 'convertedStudent']), 'pre_enrollment_finalized');

            return $student;
        });
    }

    public function dashboardStats(int $institutionId, ?int $sessionId = null): array
    {
        $q = PreEnrollment::where('institution_id', $institutionId);
        if ($sessionId) {
            $q->where('academic_session_id', $sessionId);
        }

        return [
            'candidates' => (clone $q)->count(),
            'invited' => (clone $q)->where('status', PreEnrollment::STATUS_INVITED)->count(),
            'test_completed' => (clone $q)->whereNotNull('test_result')->count(),
            'admitted' => (clone $q)->whereIn('status', [
                PreEnrollment::STATUS_ADMITTED,
                PreEnrollment::STATUS_FINALIZED,
            ])->count(),
            'not_admitted' => (clone $q)->where('status', PreEnrollment::STATUS_NOT_ADMITTED)->count(),
            'finalized' => (clone $q)->where('status', PreEnrollment::STATUS_FINALIZED)->count(),
        ];
    }

    protected function nextTemporaryId(int $institutionId): string
    {
        $year = date('Y');
        $prefix = 'PRE-' . $year . '-';
        $last = PreEnrollment::where('institution_id', $institutionId)
            ->where('temporary_id', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('temporary_id');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    protected function notify(PreEnrollment $pre, string $eventKey): void
    {
        try {
            $phone = $pre->parent_phone;
            if (! $phone) {
                return;
            }

            $data = [
                'ParentName' => $pre->parent_name ?: 'Parent',
                'StudentName' => $pre->fullName(),
                'TemporaryId' => $pre->temporary_id,
                'Status' => $pre->statusLabel(),
                'TestDate' => optional($pre->test_at)->format('d/m/Y H:i') ?? '',
                'TestLocation' => $pre->test_location ?? '',
                'TestScore' => $pre->test_score !== null ? (string) $pre->test_score : '',
                'TestResult' => $pre->test_result ?? '',
                'Class' => optional($pre->requestedClassSection)->name
                    ?? optional($pre->requestedGradeLevel)->name
                    ?? ($pre->requested_option ?? ''),
                'SchoolName' => optional($pre->institution)->name ?? config('app.name'),
                'Option' => $pre->requested_option ?? '',
                'AdmissionNumber' => optional($pre->convertedStudent)->admission_number ?? '',
            ];

            $this->notifications->sendEventToPhone($eventKey, $phone, $data, $pre->institution_id);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
