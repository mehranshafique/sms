<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            
            @php
                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('Super Admin');
                $isStudent = $user->hasRole('Student');
                $isTeacher = $user->hasRole('Teacher');
                
                if (!isset($enabledModules)) {
                    $enabledModules = [];
                    $institutionId = session('active_institution_id') ?: $user->institute_id;
                    
                    if ($institutionId && $institutionId !== 'global') {
                        $setting = \App\Models\InstitutionSetting::where('institution_id', $institutionId)
                            ->where('key', 'enabled_modules')
                            ->first();
                        
                        if ($setting && $setting->value) {
                            $enabledModules = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);
                        } else {
                            $sub = \App\Models\Subscription::with('package')
                                ->where('institution_id', $institutionId)
                                ->where('status', 'active')
                                ->where('end_date', '>=', now()->startOfDay())
                                ->latest('created_at')
                                ->first();
                                
                            if ($sub && $sub->package) {
                                $enabledModules = $sub->package->modules ?? [];
                            }
                        }
                    }
                }
                
                $modules = $enabledModules ?? [];
                $hasModule = function($slug) use ($modules, $isSuperAdmin) {
                    if ($isSuperAdmin) return true;
                    $slug = strtolower(trim($slug));
                    $cleanModules = array_map(fn($m) => strtolower(trim($m)), $modules);
                    return in_array($slug, $cleanModules);
                };
            @endphp

            @if($isSuperAdmin)
                
                <li class="nav-label first">{{ __('sidebar.main_admin') }}</li>
                <li><a class="ai-icon" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>
                <li><a class="ai-icon" href="{{ route('roles.index') }}"><i class="la la-shield"></i><span class="nav-text">{{ __('sidebar.permissions.roles') }}</span></a></li>

                <li class="nav-label">{{ __('sidebar.reporting') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.creation') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('institutes.create') }}">{{ __('sidebar.institution_creation') }}</a></li>
                        <li><a href="{{ route('header-officers.create') }}">{{ __('sidebar.headoff_creation') }}</a></li>
                        <li><a href="{{ route('audit-logs.index') }}">{{ __('sidebar.audit_log') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.management') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-university"></i><span class="nav-text">{{ __('sidebar.institution_mgmt') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('institutes.index') }}">{{ __('sidebar.all_institutions') }}</a></li>
                        <li><a href="{{ route('header-officers.index') }}">{{ __('sidebar.header_officers.title') }}</a></li>
                        <li><a href="{{ route('institutes.index') }}?status=0">{{ __('sidebar.expired_institution') }}</a></li>
                        <li><a href="{{ route('packages.index') }}">{{ __('sidebar.packages.title') }}</a></li>
                        <li><a href="{{ route('subscriptions.index') }}">{{ __('sidebar.subscriptions.title') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.configuration') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.system_config') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('configuration.index') }}#smtp">{{ __('sidebar.smtp') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#sms">{{ __('sidebar.id_sender_sms') }}</a></li>
                        <li><a href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#school_year">{{ __('sidebar.school_year') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#modules">{{ __('sidebar.modules') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#recharge">{{ __('sidebar.recharging') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.finance') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('subscriptions.index') }}">Billing</a></li> 
                        <li><a href="{{ route('subscriptions.invoices') }}">{{ __('sidebar.billing_requests') }} (Invoices)</a></li>
                    </ul>
                </li>

            @else
                
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon" href="{{ route('dashboard') }}"><i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>

                {{-- ACADEMICS --}}
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                @if($hasModule('academic_sessions')) @can('academic_session.view') <li><a class="ai-icon" href="{{ route('academic-sessions.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.sessions.title') }}</span></a></li> @endcan @endif
                @if($hasModule('grade_levels')) @can('grade_level.view') <li><a class="ai-icon" href="{{ route('grade-levels.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span></a></li> @endcan @endif
                @if($hasModule('class_sections')) @can('class_section.view') <li><a class="ai-icon" href="{{ route('class-sections.index') }}"><i class="la la-th-list"></i><span class="nav-text">{{ __('sidebar.class_sections.title') }}</span></a></li> @endcan @endif
                @if($hasModule('subjects')) @can('subject.view') <li><a class="ai-icon" href="{{ route('subjects.index') }}"><i class="la la-book"></i><span class="nav-text">{{ __('sidebar.subjects.title') }}</span></a></li> @endcan @endif
                @if($hasModule('timetables')) @can('timetable.view') <li><a class="ai-icon" href="{{ route('timetables.index') }}"><i class="la la-clock-o"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a></li> @endcan @endif

                {{-- exams --}}
                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                @if($hasModule('exams')) @can('exam.view') <li><a class="ai-icon" href="{{ route('exams.index') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.exams.title') }}</span></a></li> @endcan @endif
                
                @if($hasModule('exam_schedules'))
                    @if($isStudent || $isTeacher)
                        <li><a class="ai-icon" href="{{ route('exam-schedules.index') }}"><i class="la la-calendar-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.view_schedule') }}</span></a></li>
                    @elseif($user->can('exam_schedule.create'))
                        <li><a class="ai-icon" href="{{ route('exam-schedules.manage') }}"><i class="la la-calendar-plus-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.manage') }}</span></a></li>
                    @endif
                @endif
                
                @if($hasModule('assignments'))
                    @if($isStudent)
                        <li><a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.my_assignments') }}</span></a></li>
                    @elseif($isTeacher)
                        <li><a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.class_assignments') }}</span></a></li>
                    @elseif($user->can('assignment.view'))
                        <li><a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.title') }}</span></a></li>
                    @endif
                @endif

                @if($hasModule('exam_marks')) @can('exam_mark.create') <li><a class="ai-icon" href="{{ route('marks.create') }}"><i class="la la-edit"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a></li> @endcan @endif
                
                @if($hasModule('results') || $hasModule('exams')) 
                    @if(auth()->user()->can('view result_card') || $isStudent || $isTeacher)
                    <li><a class="ai-icon" href="{{ route('results.index') }}"><i class="la la-certificate"></i><span class="nav-text">{{ __('sidebar.results') }}</span></a></li>
                    @endif
                @endif

                @if($hasModule('exams')) <li><a class="ai-icon" href="{{ route('reports.index') }}"><i class="la la-file-pdf-o"></i><span class="nav-text">{{ __('sidebar.academic_reports') }}</span></a></li> @endif

                {{-- COMMUNICATION --}}
                @if($hasModule('communication'))
                <li class="nav-label">{{ __('sidebar.communication') }}</li>
                    @can('notice.view') <li><a class="ai-icon" href="{{ route('notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.notices.title') }}</span></a></li> @endcan
                    @if($isStudent) <li><a class="ai-icon" href="{{ route('student.notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.my_notices') }}</span></a></li> @endif
                @endif

                {{-- VOTING --}}
                @if($hasModule('voting'))
                <li class="nav-label">{{ __('sidebar.voting') }}</li>
                    @can('election.view') <li><a class="ai-icon" href="{{ route('elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.elections.title') }}</span></a></li> @endcan
                    @if($isStudent) <li><a class="ai-icon" href="{{ route('student.elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.my_elections') }}</span></a></li> @endif
                @endif

                {{-- LIBRARY & TRANSPORT (Added based on request) --}}
                <!-- @if($hasModule('library'))
                <li class="nav-label">{{ __('sidebar.library.title') }}</li>
                    {{-- Placeholder routes until implemented --}}
                    <li><a class="ai-icon" href="javascript:void(0)"><i class="la la-book"></i><span class="nav-text">{{ __('sidebar.library.title') }}</span></a></li>
                @endif

                @if($hasModule('transport'))
                <li class="nav-label">{{ __('sidebar.transport.title') }}</li>
                    <li><a class="ai-icon" href="javascript:void(0)"><i class="la la-bus"></i><span class="nav-text">{{ __('sidebar.transport.title') }}</span></a></li>
                @endif -->

                {{-- PEOPLE --}}
                <li class="nav-label">{{ __('sidebar.people') }}</li>
                @if($hasModule('students')) @can('student.view') <li><a class="ai-icon" href="{{ route('students.index') }}"><i class="la la-users"></i><span class="nav-text">{{ __('sidebar.students.title') }}</span></a></li> @endcan @endif
                @if($hasModule('enrollments')) @can('student_enrollment.view') <li><a class="ai-icon" href="{{ route('enrollments.index') }}"><i class="la la-id-card"></i><span class="nav-text">{{ __('sidebar.enrollments.title') }}</span></a></li> @endcan @endif
                @if($hasModule('student_attendance')) @can('student_attendance.view') <li><a class="ai-icon" href="{{ route('attendance.index') }}"><i class="la la-check-square"></i><span class="nav-text">{{ __('sidebar.attendance.title') }}</span></a></li> @endcan @endif
                @if($hasModule('student_promotion')) @can('student_promotion.view') <li><a class="ai-icon" href="{{ route('promotions.index') }}"><i class="la la-level-up"></i><span class="nav-text">{{ __('sidebar.promotions.title') }}</span></a></li> @endcan @endif
                @if($hasModule('staff')) @can('staff.view') <li><a class="ai-icon" href="{{ route('staff.index') }}"><i class="la la-chalkboard-teacher"></i><span class="nav-text">{{ __('sidebar.staff.title') }}</span></a></li> @endcan 
                @can('staff_attendance.view') <li><a class="ai-icon" href="{{ route('staff-attendance.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.staff_attendance') }}</span></a></li> @endcan @endif

                {{-- FINANCE --}}
                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                @if($hasModule('fee_structures') || $hasModule('fee_types') || $hasModule('invoices'))
                    <li>
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.fees_collection') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('fee_types')) @can('fee_type.view') <li><a href="{{ route('fee-types.index') }}">{{ __('sidebar.fee_types.title') }}</a></li> @endcan @endif
                            @if($hasModule('fee_structures')) @can('fee_structure.view') <li><a href="{{ route('fees.index') }}">{{ __('sidebar.fee_structures.title') }}</a></li> @endcan @endif
                            @if($hasModule('invoices'))
                                @can('invoice.create') <li><a href="{{ route('invoices.create') }}">{{ __('sidebar.invoices.generate') }}</a></li> @endcan
                                @can('invoice.view')
                                <li><a href="{{ route('invoices.index') }}">{{ __('sidebar.invoices.list') }}</a></li>
                                <li><a href="{{ route('finance.balances.index') }}">{{ __('sidebar.student_balances') }}</a></li>
                                @endcan
                            @endif
                            @can('invoice.view') <li><a href="{{ route('finance.reports.class_summary') }}">{{ __('sidebar.financial_reports') }}</a></li> @endcan
                        </ul>
                    </li>
                @endif

                @if($hasModule('payrolls') || $hasModule('budgets'))
                    <li>
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-wallet"></i><span class="nav-text">{{ __('sidebar.budget_payroll') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('payrolls')) @can('payroll.view')
                                <li><a href="{{ route('salary-structures.index') }}">{{ __('sidebar.salary_structures') }}</a></li>
                                <li><a href="{{ route('payroll.index') }}">{{ __('sidebar.generate_payroll') }}</a></li>
                            @endcan @endif
                            @if($hasModule('budgets')) @can('budget.view')
                                <li><a href="{{ route('budgets.categories') }}">{{ __('sidebar.budget_categories') }}</a></li>
                                <li><a href="{{ route('budgets.index') }}">{{ __('sidebar.budget_allocation') }}</a></li>
                                <li><a href="{{ route('budgets.requests') }}">{{ __('sidebar.fund_requests') }}</a></li>
                            @endcan @endif
                        </ul>
                    </li>
                @endif
                
                @can('institution.view') <li><a href="{{ route('subscriptions.invoices') }}" class="ai-icon"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.billing') }}</span></a></li> @endcan

                {{-- SETTINGS --}}
                <li class="nav-label">{{ __('sidebar.settings') }}</li>
                @can('institution.view') <li><a class="ai-icon" href="{{ route('settings.index') }}"><i class="la la-cogs"></i><span class="nav-text">{{ __('settings.page_title') }}</span></a></li> @endcan
                @if($hasModule('settings')) @can('institution.update')
                    <li><a class="ai-icon" href="{{ route('configuration.index') }}"><i class="fa fa-sliders"></i><span class="nav-text">{{ __('configuration.page_title') }}</span></a></li>
                    <li><a class="ai-icon" href="{{ route('sms_templates.index') }}"><i class="fa fa-commenting"></i><span class="nav-text">{{ __('sidebar.sms_templates') }}</span></a></li>
                @endcan @endif
                @can('role.view') <li><a class="ai-icon" href="{{ route('roles.index') }}"><i class="la la-shield"></i><span class="nav-text">{{ __('sidebar.permissions.roles') }}</span></a></li> @endcan

            @endif
        </ul>
    </div>
</div>