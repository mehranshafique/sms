<?php

namespace App\Services;

use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Invoice;
use App\Models\Notice;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Subject;
use App\Models\User;

class GlobalSearchService
{
    private const PER_GROUP = 5;
    private const MAX_RESULTS = 20;

    public function search(User $user, string $query, ?int $institutionId): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';
        $results = [];

        $results = array_merge($results, $this->searchPages($user, $query));

        if ($user->hasRole('Super Admin') && !$institutionId) {
            $results = array_merge($results, $this->searchInstitutions($term));
        }

        if ($institutionId) {
            if ($user->can('student.view')) {
                $results = array_merge($results, $this->searchStudents($term, $institutionId));
            }

            if ($user->can('staff.view')) {
                $results = array_merge($results, $this->searchStaff($term, $institutionId));
            }

            if ($user->can('student.view')) {
                $results = array_merge($results, $this->searchParents($term, $institutionId));
            }

            if ($user->can('subject.view')) {
                $results = array_merge($results, $this->searchSubjects($term, $institutionId));
            }

            if ($user->can('class_section.view')) {
                $results = array_merge($results, $this->searchClassSections($term, $institutionId));
            }

            if ($user->can('invoice.view')) {
                $results = array_merge($results, $this->searchInvoices($term, $institutionId));
            }

            if ($user->can('exam.view')) {
                $results = array_merge($results, $this->searchExams($term, $institutionId));
            }

            if ($user->can('notice.view')) {
                $results = array_merge($results, $this->searchNotices($term, $institutionId));
            }
        }

        if ($user->hasRole('Student') && $user->student) {
            $results = array_merge($results, $this->searchStudentSelf($user, $query));
        }

        return array_slice($results, 0, self::MAX_RESULTS);
    }

    private function result(string $type, string $label, ?string $subtitle, string $url, string $icon): array
    {
        return [
            'type' => $type,
            'type_label' => __('header.search_types.' . $type),
            'label' => $label,
            'subtitle' => $subtitle,
            'url' => $url,
            'icon' => $icon,
        ];
    }

    private function searchPages(User $user, string $query): array
    {
        $needle = mb_strtolower($query);
        $results = [];

        foreach ($this->searchablePages($user) as $page) {
            $haystack = mb_strtolower($page['label'] . ' ' . implode(' ', $page['keywords']));
            if (!str_contains($haystack, $needle)) {
                continue;
            }

            $results[] = $this->result(
                'page',
                $page['label'],
                __('header.search_page'),
                $page['url'],
                $page['icon'] ?? 'la la-link'
            );
        }

        return $results;
    }

    private function searchablePages(User $user): array
    {
        $pages = [
            ['label' => __('sidebar.dashboard.title'), 'url' => route('dashboard'), 'keywords' => ['dashboard', 'home', 'accueil'], 'icon' => 'la la-home'],
            ['label' => __('sidebar.students.title'), 'url' => route('students.index'), 'keywords' => ['student', 'students', 'eleve', 'eleves'], 'permission' => 'student.view', 'icon' => 'la la-user-graduate'],
            ['label' => __('parent.page_title'), 'url' => route('parents.index'), 'keywords' => ['parent', 'guardian', 'tuteur'], 'permission' => 'student.view', 'icon' => 'la la-users'],
            ['label' => __('sidebar.staff.title'), 'url' => route('staff.index'), 'keywords' => ['staff', 'employee', 'personnel'], 'permission' => 'staff.view', 'icon' => 'la la-id-badge'],
            ['label' => __('sidebar.subjects.title'), 'url' => route('subjects.index'), 'keywords' => ['subject', 'course', 'matiere', 'cours'], 'permission' => 'subject.view', 'icon' => 'la la-book'],
            ['label' => __('sidebar.class_sections.title'), 'url' => route('class-sections.index'), 'keywords' => ['class', 'section', 'classe'], 'permission' => 'class_section.view', 'icon' => 'la la-door-open'],
            ['label' => __('sidebar.timetables.title'), 'url' => route('timetables.index'), 'keywords' => ['timetable', 'schedule', 'horaire'], 'permission' => 'timetable.view', 'icon' => 'la la-calendar'],
            ['label' => __('sidebar.attendance.title'), 'url' => route('attendance.index'), 'keywords' => ['attendance', 'presence'], 'permission' => 'student_attendance.view', 'icon' => 'la la-check-square'],
            ['label' => __('sidebar.exams.title'), 'url' => route('exams.index'), 'keywords' => ['exam', 'examination', 'examen'], 'permission' => 'exam.view', 'icon' => 'la la-trophy'],
            ['label' => __('sidebar.invoices.list'), 'url' => route('invoices.index'), 'keywords' => ['invoice', 'fee', 'finance', 'facture', 'frais'], 'permission' => 'invoice.view', 'icon' => 'la la-file-invoice-dollar'],
            ['label' => __('sidebar.notices.title'), 'url' => route('notices.index'), 'keywords' => ['notice', 'announcement', 'avis', 'communication'], 'permission' => 'notice.view', 'icon' => 'la la-bullhorn'],
            ['label' => __('sidebar.enrollments.title'), 'url' => route('enrollments.index'), 'keywords' => ['enrollment', 'inscription'], 'permission' => 'student_enrollment.view', 'icon' => 'la la-user-plus'],
            ['label' => __('sidebar.settings'), 'url' => route('settings.index'), 'keywords' => ['settings', 'configuration', 'parametres'], 'permission' => 'setting.view', 'icon' => 'la la-cog'],
            ['label' => __('header.my_profile'), 'url' => route('profile.index'), 'keywords' => ['profile', 'account', 'profil', 'compte'], 'icon' => 'la la-user'],
        ];

        if ($user->hasRole('Super Admin')) {
            $pages[] = ['label' => __('sidebar.all_institutions'), 'url' => route('institutes.index'), 'keywords' => ['institution', 'school', 'ecole'], 'icon' => 'la la-university'];
            $pages[] = ['label' => __('sidebar.subscriptions.title'), 'url' => route('subscriptions.index'), 'keywords' => ['subscription', 'abonnement'], 'icon' => 'la la-credit-card'];
            $pages[] = ['label' => __('sidebar.audit_log'), 'url' => route('audit-logs.index'), 'keywords' => ['audit', 'log', 'journal'], 'icon' => 'la la-history'];
        }

        return array_values(array_filter($pages, function ($page) use ($user) {
            if (!empty($page['permission']) && !$user->can($page['permission'])) {
                return false;
            }

            return true;
        }));
    }

    private function searchInstitutions(string $term): array
    {
        return Institution::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('email', 'like', $term);
            })
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($inst) => $this->result(
                'institution',
                $inst->name,
                $inst->code,
                route('institutes.edit', $inst->id),
                'la la-university'
            ))
            ->all();
    }

    private function searchStudents(string $term, int $institutionId): array
    {
        return Student::query()
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('admission_number', 'like', $term)
                    ->orWhere('roll_number', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('mobile_number', 'like', $term)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term]);
            })
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($student) => $this->result(
                'student',
                $student->full_name,
                $student->admission_number,
                route('students.show', $student->id),
                'la la-user-graduate'
            ))
            ->all();
    }

    private function searchStaff(string $term, int $institutionId): array
    {
        return Staff::query()
            ->with('user:id,name,email')
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($term) {
                $q->where('employee_id', 'like', $term)
                    ->orWhere('designation', 'like', $term)
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            })
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($staff) => $this->result(
                'staff',
                $staff->user->name ?? __('header.search_unknown_staff'),
                $staff->employee_id ?: ($staff->designation ?? ''),
                route('staff.show', $staff->id),
                'la la-chalkboard-teacher'
            ))
            ->all();
    }

    private function searchParents(string $term, int $institutionId): array
    {
        return StudentParent::query()
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($term) {
                $q->where('father_name', 'like', $term)
                    ->orWhere('mother_name', 'like', $term)
                    ->orWhere('guardian_name', 'like', $term)
                    ->orWhere('guardian_phone', 'like', $term)
                    ->orWhere('father_phone', 'like', $term);
            })
            ->limit(self::PER_GROUP)
            ->get()
            ->map(function ($parent) {
                $label = $parent->guardian_name ?: ($parent->father_name ?: $parent->mother_name);

                return $this->result(
                    'parent',
                    $label ?: __('header.search_unknown_parent'),
                    $parent->guardian_phone ?: $parent->father_phone,
                    route('parents.show', $parent->id),
                    'la la-users'
                );
            })
            ->all();
    }

    private function searchSubjects(string $term, int $institutionId): array
    {
        return Subject::query()
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term);
            })
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($subject) => $this->result(
                'subject',
                $subject->name,
                $subject->code,
                route('subjects.edit', $subject->id),
                'la la-book'
            ))
            ->all();
    }

    private function searchClassSections(string $term, int $institutionId): array
    {
        return ClassSection::query()
            ->with('gradeLevel:id,name')
            ->where('institution_id', $institutionId)
            ->where('name', 'like', $term)
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($section) => $this->result(
                'class_section',
                $section->name,
                $section->gradeLevel->name ?? null,
                route('class-sections.edit', $section->id),
                'la la-door-open'
            ))
            ->all();
    }

    private function searchInvoices(string $term, int $institutionId): array
    {
        return Invoice::query()
            ->with('student:id,first_name,last_name,admission_number')
            ->where('institution_id', $institutionId)
            ->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                    ->orWhereHas('student', function ($s) use ($term) {
                        $s->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('admission_number', 'like', $term);
                    });
            })
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($invoice) => $this->result(
                'invoice',
                $invoice->invoice_number,
                $invoice->student?->full_name,
                route('invoices.show', $invoice->id),
                'la la-file-invoice-dollar'
            ))
            ->all();
    }

    private function searchExams(string $term, int $institutionId): array
    {
        return Exam::query()
            ->where('institution_id', $institutionId)
            ->where('name', 'like', $term)
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($exam) => $this->result(
                'exam',
                $exam->name,
                ucfirst($exam->status ?? ''),
                route('exams.show', $exam->id),
                'la la-trophy'
            ))
            ->all();
    }

    private function searchNotices(string $term, int $institutionId): array
    {
        return Notice::query()
            ->where('institution_id', $institutionId)
            ->where('title', 'like', $term)
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn ($notice) => $this->result(
                'notice',
                $notice->title,
                ucfirst($notice->audience ?? ''),
                route('notices.show', $notice->id),
                'la la-bullhorn'
            ))
            ->all();
    }

    private function searchStudentSelf(User $user, string $query): array
    {
        $needle = mb_strtolower($query);
        $results = [];
        $student = $user->student;

        $studentPages = [
            ['label' => __('dashboard.student_dashboard'), 'url' => route('dashboard'), 'keywords' => ['dashboard', 'home']],
            ['label' => __('dashboard.my_fees'), 'url' => route('dashboard'), 'keywords' => ['fee', 'fees', 'frais', 'invoice']],
            ['label' => __('sidebar.notices.title'), 'url' => route('student.notices.index'), 'keywords' => ['notice', 'announcement', 'avis']],
        ];

        foreach ($studentPages as $page) {
            $haystack = mb_strtolower($page['label'] . ' ' . implode(' ', $page['keywords']));
            if (str_contains($haystack, $needle)) {
                $results[] = $this->result('page', $page['label'], __('header.search_page'), $page['url'], 'la la-link');
            }
        }

        if (str_contains(mb_strtolower($student->full_name), $needle) || str_contains($student->admission_number, $needle)) {
            $results[] = $this->result(
                'student',
                $student->full_name,
                $student->admission_number,
                route('students.show', $student->id),
                'la la-user-graduate'
            );
        }

        return $results;
    }
}
