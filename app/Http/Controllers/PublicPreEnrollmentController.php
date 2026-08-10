<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ClassSection;
use App\Models\GradeLevel;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\PreEnrollment;
use App\Services\PreEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicPreEnrollmentController extends Controller
{
    public function __construct(
        protected PreEnrollmentService $preEnrollments
    ) {}

    public function create(string $code)
    {
        $institution = $this->resolveInstitution($code);

        if (! $this->isPublicEnabled($institution->id)) {
            return view('pre_enrollments.public.closed', [
                'institution' => $institution,
            ]);
        }

        return view('pre_enrollments.public.create', $this->formPayload($institution));
    }

    public function store(Request $request, string $code)
    {
        $institution = $this->resolveInstitution($code);

        if (! $this->isPublicEnabled($institution->id)) {
            return redirect()
                ->route('public.pre-enrollments.create', $institution->code)
                ->with('error', __('pre_enrollment.public.closed'));
        }

        // Honeypot: bots fill hidden fields; humans never see them.
        if (filled($request->input('website'))) {
            return redirect()
                ->route('public.pre-enrollments.done', [
                    'code' => $institution->code,
                    'temp' => 'PRE-' . date('Y') . '-0000',
                ]);
        }

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'post_name' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date|before:today',
            'place_of_birth' => 'nullable|string|max:150',
            'parent_name' => 'required|string|max:150',
            'parent_phone' => 'required|string|max:40',
            'parent_email' => 'nullable|email|max:150',
            'requested_grade_level_id' => 'nullable|exists:grade_levels,id',
            'requested_class_section_id' => 'nullable|exists:class_sections,id',
            'requested_option' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'locale' => 'nullable|in:en,fr',
        ]);

        if (! empty($data['locale'])) {
            session(['locale' => $data['locale']]);
            app()->setLocale($data['locale']);
        }

        $this->assertBelongsToInstitution($institution->id, $data);

        $existing = $this->preEnrollments->findDuplicate((int) $institution->id, $data);
        $pre = $this->preEnrollments->register($data, (int) $institution->id, 'web');

        return redirect()
            ->route('public.pre-enrollments.done', [
                'code' => $institution->code,
                'temp' => $pre->temporary_id,
            ])
            ->with('success', $existing && $existing->id === $pre->id
                ? __('pre_enrollment.public.already_registered', ['id' => $pre->temporary_id])
                : __('pre_enrollment.public.success', ['id' => $pre->temporary_id]));
    }

    public function done(string $code, string $temp)
    {
        $institution = $this->resolveInstitution($code);

        $candidate = PreEnrollment::where('institution_id', $institution->id)
            ->where('temporary_id', $temp)
            ->first();

        return view('pre_enrollments.public.done', [
            'institution' => $institution,
            'candidate' => $candidate,
            'temporaryId' => $temp,
        ]);
    }

    protected function resolveInstitution(string $code): Institution
    {
        $institution = Institution::query()
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->where('is_active', true)
            ->first();

        if (! $institution) {
            abort(404, __('pre_enrollment.public.school_not_found'));
        }

        return $institution;
    }

    protected function isPublicEnabled(int $institutionId): bool
    {
        $value = InstitutionSetting::get($institutionId, 'pre_enrollment_public_enabled', '1');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    /** @return array<string, mixed> */
    protected function formPayload(Institution $institution): array
    {
        $grades = GradeLevel::where('institution_id', $institution->id)
            ->orderBy('order_index')
            ->pluck('name', 'id');

        $classes = ClassSection::with('gradeLevel')
            ->where('institution_id', $institution->id)
            ->get()
            ->mapWithKeys(fn ($c) => [
                $c->id => trim($c->name . ' (' . ($c->gradeLevel->name ?? '') . ')'),
            ]);

        $session = AcademicSession::where('institution_id', $institution->id)
            ->where('is_current', true)
            ->first()
            ?? AcademicSession::where('institution_id', $institution->id)
                ->orderByDesc('start_date')
                ->first();

        return [
            'institution' => $institution,
            'grades' => $grades,
            'classes' => $classes,
            'session' => $session,
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function assertBelongsToInstitution(int $institutionId, array $data): void
    {
        if (! empty($data['requested_grade_level_id'])) {
            $ok = GradeLevel::where('institution_id', $institutionId)
                ->where('id', $data['requested_grade_level_id'])
                ->exists();
            if (! $ok) {
                throw ValidationException::withMessages([
                    'requested_grade_level_id' => __('pre_enrollment.public.invalid_grade'),
                ]);
            }
        }

        if (! empty($data['requested_class_section_id'])) {
            $ok = ClassSection::where('institution_id', $institutionId)
                ->where('id', $data['requested_class_section_id'])
                ->exists();
            if (! $ok) {
                throw ValidationException::withMessages([
                    'requested_class_section_id' => __('pre_enrollment.public.invalid_class'),
                ]);
            }
        }
    }
}
