<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            
            @php
                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('Super Admin');
                $isStudent = $user->hasRole('Student');
                $isTeacher = $user->hasRole('Teacher');
                
                // Fetch Institution Type for Menu Logic
                $institutionType = 'mixed'; // Default
                if (!$isSuperAdmin) {
                    $instId = session('active_institution_id') ?: $user->institute_id;
                    if($instId) {
                        // We use a direct query or cache here to avoid N+1 if not eager loaded
                        $inst = \App\Models\Institution::find($instId);
                        if($inst) $institutionType = $inst->type;
                    }
                }
                
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
                <li><a class="ai-icon {{ request()->routeIs('dashboard') ? 'mm-active' : '' }}" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>
                <li><a class="ai-icon {{ request()->routeIs('roles.*') ? 'mm-active' : '' }}" href="{{ route('roles.index') }}"><i class="la la-shield"></i><span class="nav-text">{{ __('sidebar.permissions.roles') }}</span></a></li>

                <li class="nav-label">{{ __('sidebar.reporting') }}</li>
                <li class="{{ request()->routeIs('institutes.create', 'header-officers.create', 'audit-logs.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.creation') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('institutes.create') }}" class="{{ request()->routeIs('institutes.create') ? 'mm-active' : '' }}">{{ __('sidebar.institution_creation') }}</a></li>
                        <li><a href="{{ route('header-officers.create') }}" class="{{ request()->routeIs('header-officers.create') ? 'mm-active' : '' }}">{{ __('sidebar.headoff_creation') }}</a></li>
                        <li><a href="{{ route('audit-logs.index') }}" class="{{ request()->routeIs('audit-logs.*') ? 'mm-active' : '' }}">{{ __('sidebar.audit_log') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.management') }}</li>
                <li class="{{ request()->routeIs('institutes.index', 'institutes.edit', 'institutes.show', 'header-officers.index', 'header-officers.edit', 'packages.*', 'subscriptions.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-university"></i><span class="nav-text">{{ __('sidebar.institution_mgmt') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('institutes.index') }}" class="{{ request()->routeIs('institutes.index', 'institutes.edit', 'institutes.show') ? 'mm-active' : '' }}">{{ __('sidebar.all_institutions') }}</a></li>
                        <li><a href="{{ route('header-officers.index') }}" class="{{ request()->routeIs('header-officers.index', 'header-officers.edit') ? 'mm-active' : '' }}">{{ __('sidebar.header_officers.title') }}</a></li>
                        <li><a href="{{ route('institutes.index') }}?status=0">{{ __('sidebar.expired_institution') }}</a></li>
                        <li><a href="{{ route('packages.index') }}" class="{{ request()->routeIs('packages.*') ? 'mm-active' : '' }}">{{ __('sidebar.packages.title') }}</a></li>
                        <li><a href="{{ route('subscriptions.index') }}" class="{{ request()->routeIs('subscriptions.*') ? 'mm-active' : '' }}">{{ __('sidebar.subscriptions.title') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.configuration') }}</li>
                <li class="{{ request()->routeIs('configuration.*', 'sms_templates.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.system_config') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('configuration.index') }}#smtp">{{ __('sidebar.smtp') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#sms">{{ __('sidebar.id_sender_sms') }}</a></li>
                        <li><a href="{{ route('sms_templates.index') }}" class="{{ request()->routeIs('sms_templates.*') ? 'mm-active' : '' }}">{{ __('sidebar.sms_templates') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#school_year">{{ __('sidebar.school_year') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#modules">{{ __('sidebar.modules') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#recharge">{{ __('sidebar.recharging') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li class="{{ request()->routeIs('subscriptions.invoices') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.finance') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('subscriptions.index') }}">Billing</a></li> 
                        <li><a href="{{ route('subscriptions.invoices') }}" class="{{ request()->routeIs('subscriptions.invoices') ? 'mm-active' : '' }}">{{ __('sidebar.billing_requests') }} (Invoices)</a></li>
                    </ul>
                </li>

            @else
                
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('dashboard') ? 'mm-active' : '' }}" href="{{ route('dashboard') }}"><i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>

                {{-- ACADEMICS --}}
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                @if($hasModule('academic_sessions')) @can('academic_session.view') 
                <li class="{{ request()->routeIs('academic-sessions.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('academic-sessions.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.sessions.title') }}</span></a>
                </li> 
                @endcan @endif
                
                {{-- Departments (University/Mixed) --}}
                @if($hasModule('departments') && in_array($institutionType, ['university', 'mixed'])) 
                    @can('department.view') 
                    <li class="{{ request()->routeIs('departments.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('departments.index') }}"><i class="la la-building"></i><span class="nav-text">{{ __('sidebar.departments.title') }}</span></a>
                    </li> 
                    @endcan 
                @endif

                @if($hasModule('grade_levels')) @can('grade_level.view') 
                <li class="{{ request()->routeIs('grade-levels.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('grade-levels.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('class_sections')) @can('class_section.view') 
                <li class="{{ request()->routeIs('class-sections.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('class-sections.index') }}"><i class="la la-th-list"></i><span class="nav-text">{{ __('sidebar.class_sections.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('subjects')) @can('subject.view') 
                <li class="{{ request()->routeIs('subjects.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('subjects.index') }}"><i class="la la-book"></i><span class="nav-text">{{ __('sidebar.subjects.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('class_subjects')) 
                    @can('class_subject.view') 
                    <li class="{{ request()->routeIs('class-subjects.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('class-subjects.index') }}"><i class="la la-list-alt"></i><span class="nav-text">{{ __('sidebar.class_subjects.title') }}</span></a>
                    </li> 
                    @endcan 
                @endif

                @if($hasModule('timetables')) @can('timetable.view') 
                <li class="{{ request()->routeIs('timetables.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('timetables.index') }}"><i class="la la-clock-o"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a>
                </li> 
                @endcan @endif

                {{-- EXAMINATIONS --}}
                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                @if($hasModule('exams')) @can('exam.view') 
                <li class="{{ request()->routeIs('exams.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('exams.index') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.exams.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('exam_schedules'))
                    @if($isStudent || $isTeacher)
                        <li class="{{ request()->routeIs('exam-schedules.index') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('exam-schedules.index') }}"><i class="la la-calendar-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.view_schedule') }}</span></a>
                        </li>
                    @elseif($user->can('exam_schedule.create'))
                        <li class="{{ request()->routeIs('exam-schedules.manage') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('exam-schedules.manage') }}"><i class="la la-calendar-plus-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.manage') }}</span></a>
                        </li>
                    @endif
                @endif
                
                @if($hasModule('assignments'))
                    @if($isStudent)
                        <li class="{{ request()->routeIs('assignments.*') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.my_assignments') }}</span></a>
                        </li>
                    @elseif($isTeacher)
                        <li class="{{ request()->routeIs('assignments.*') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.class_assignments') }}</span></a>
                        </li>
                    @elseif($user->can('assignment.view'))
                        <li class="{{ request()->routeIs('assignments.*') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.title') }}</span></a>
                        </li>
                    @endif
                @endif

                @if($hasModule('exam_marks')) @can('exam_mark.create') 
                <li class="{{ request()->routeIs('marks.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('marks.create') }}"><i class="la la-edit"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('results') || $hasModule('examinations')) 
                    @if(auth()->user()->can('view result_card') || $isStudent || $isTeacher)
                    <li class="{{ request()->routeIs('results.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('results.index') }}"><i class="la la-certificate"></i><span class="nav-text">{{ __('sidebar.results') }}</span></a>
                    </li>
                    @endif
                @endif

                @if($hasModule('examinations')) 
                <li class="{{ request()->routeIs('reports.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('reports.index') }}"><i class="la la-file-pdf-o"></i><span class="nav-text">{{ __('sidebar.academic_reports') }}</span></a>
                </li> 
                @endif

                {{-- COMMUNICATION --}}
                @if($hasModule('communication'))
                <li class="nav-label">{{ __('sidebar.communication') }}</li>
                    @can('notice.view') 
                    <li class="{{ request()->routeIs('notices.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.notices.title') }}</span></a>
                    </li> 
                    @endcan
                    @if($isStudent) 
                    <li class="{{ request()->routeIs('student.notices.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('student.notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.my_notices') }}</span></a>
                    </li> 
                    @endif
                @endif

                {{-- VOTING --}}
                @if($hasModule('voting'))
                <li class="nav-label">{{ __('sidebar.voting') }}</li>
                    @can('election.view') 
                    <li class="{{ request()->routeIs('elections.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.elections.title') }}</span></a>
                    </li> 
                    @endcan
                    @if($isStudent) 
                    <li class="{{ request()->routeIs('student.elections.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('student.elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.my_elections') }}</span></a>
                    </li> 
                    @endif
                @endif

                {{-- PEOPLE --}}
                <li class="nav-label">{{ __('sidebar.people') }}</li>
                @if($hasModule('students')) @can('student.view') 
                <li class="{{ request()->routeIs('students.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('students.index') }}"><i class="la la-users"></i><span class="nav-text">{{ __('sidebar.students.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('enrollments'))
                    {{-- 1. Standard Enrollment (Primary/Secondary/Mixed) --}}
                    @if(in_array($institutionType, ['primary', 'secondary', 'mixed', 'vocational']))
                        @can('student_enrollment.view')
                        <li class="{{ request()->routeIs('enrollments.*') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('enrollments.index') }}"><i class="la la-id-card"></i><span class="nav-text">{{ __('sidebar.enrollments.title') }}</span></a>
                        </li>
                        @endcan
                    @endif

                    {{-- 2. University Enrollment (University/Mixed) --}}
                    @if(in_array($institutionType, ['university', 'mixed']))
                        @can('university_enrollment.view')
                        <li class="{{ request()->routeIs('university.enrollments.*') ? 'mm-active' : '' }}">
                            <a class="ai-icon" href="{{ route('university.enrollments.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.university_enrollments.title') }}</span></a>
                        </li>
                        @endcan
                    @endif
                @endif
                
                @if($hasModule('student_attendance')) @can('student_attendance.view') 
                <li class="{{ request()->routeIs('attendance.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('attendance.index') }}"><i class="la la-check-square"></i><span class="nav-text">{{ __('sidebar.attendance.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('student_promotion')) @can('student_promotion.view') 
                <li class="{{ request()->routeIs('promotions.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('promotions.index') }}"><i class="la la-level-up"></i><span class="nav-text">{{ __('sidebar.promotions.title') }}</span></a>
                </li> 
                @endcan @endif
                
                @if($hasModule('staff')) @can('staff.view') 
                <li class="{{ request()->routeIs('staff.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('staff.index') }}"><i class="la la-chalkboard-teacher"></i><span class="nav-text">{{ __('sidebar.staff.title') }}</span></a>
                </li> 
                @endcan 
                @can('staff_attendance.view') 
                <li class="{{ request()->routeIs('staff-attendance.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('staff-attendance.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.staff_attendance') }}</span></a>
                </li> 
                @endcan @endif

                {{-- FINANCE --}}
                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                @if($hasModule('fee_structures') || $hasModule('fee_types') || $hasModule('invoices'))
                    <li class="{{ request()->routeIs('fee-types.*', 'fees.*', 'invoices.*', 'finance.balances.*', 'finance.reports.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.fees_collection') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('fee_types')) @can('fee_type.view') 
                            <li><a href="{{ route('fee-types.index') }}" class="{{ request()->routeIs('fee-types.*') ? 'mm-active' : '' }}">{{ __('sidebar.fee_types.title') }}</a></li> 
                            @endcan @endif
                            
                            @if($hasModule('fee_structures')) @can('fee_structure.view') 
                            <li><a href="{{ route('fees.index') }}" class="{{ request()->routeIs('fees.*') ? 'mm-active' : '' }}">{{ __('sidebar.fee_structures.title') }}</a></li> 
                            @endcan @endif
                            
                            @if($hasModule('invoices'))
                                @can('invoice.create') 
                                <li><a href="{{ route('invoices.create') }}" class="{{ request()->routeIs('invoices.create') ? 'mm-active' : '' }}">{{ __('sidebar.invoices.generate') }}</a></li> 
                                @endcan
                                @can('invoice.view')
                                <li><a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index', 'invoices.show') ? 'mm-active' : '' }}">{{ __('sidebar.invoices.list') }}</a></li>
                                <li><a href="{{ route('finance.balances.index') }}" class="{{ request()->routeIs('finance.balances.*') ? 'mm-active' : '' }}">{{ __('sidebar.student_balances') }}</a></li>
                                @endcan
                            @endif
                            @can('invoice.view') 
                            <li><a href="{{ route('finance.reports.class_summary') }}" class="{{ request()->routeIs('finance.reports.*') ? 'mm-active' : '' }}">{{ __('sidebar.financial_reports') }}</a></li> 
                            @endcan
                        </ul>
                    </li>
                @endif

                @if($hasModule('payrolls') || $hasModule('budgets'))
                    <li class="{{ request()->routeIs('salary-structures.*', 'payroll.*', 'budgets.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-wallet"></i><span class="nav-text">{{ __('sidebar.budget_payroll') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('payrolls')) @can('payroll.view')
                                <li><a href="{{ route('salary-structures.index') }}" class="{{ request()->routeIs('salary-structures.*') ? 'mm-active' : '' }}">{{ __('sidebar.salary_structures') }}</a></li>
                                <li><a href="{{ route('payroll.index') }}" class="{{ request()->routeIs('payroll.*') ? 'mm-active' : '' }}">{{ __('sidebar.generate_payroll') }}</a></li>
                            @endcan @endif
                            @if($hasModule('budgets')) @can('budget.view')
                                <li><a href="{{ route('budgets.categories') }}" class="{{ request()->routeIs('budgets.categories') ? 'mm-active' : '' }}">{{ __('sidebar.budget_categories') }}</a></li>
                                <li><a href="{{ route('budgets.index') }}" class="{{ request()->routeIs('budgets.index') ? 'mm-active' : '' }}">{{ __('sidebar.budget_allocation') }}</a></li>
                                <li><a href="{{ route('budgets.requests') }}" class="{{ request()->routeIs('budgets.requests') ? 'mm-active' : '' }}">{{ __('sidebar.fund_requests') }}</a></li>
                            @endcan @endif
                        </ul>
                    </li>
                @endif
                
                @can('institution.view') 
                <li class="{{ request()->routeIs('subscriptions.invoices') ? 'mm-active' : '' }}">
                    <a href="{{ route('subscriptions.invoices') }}" class="ai-icon"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.billing') }}</span></a>
                </li> 
                @endcan

                {{-- SETTINGS --}}
                <li class="nav-label">{{ __('sidebar.settings') }}</li>
                @can('institution.view') 
                <li class="{{ request()->routeIs('settings.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('settings.index') }}"><i class="la la-cogs"></i><span class="nav-text">{{ __('settings.page_title') }}</span></a>
                </li> 
                @endcan
                
                @if($hasModule('settings')) @can('institution.update')
                    <li class="{{ request()->routeIs('configuration.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('configuration.index') }}"><i class="fa fa-sliders"></i><span class="nav-text">{{ __('configuration.page_title') }}</span></a>
                    </li>
                    <li class="{{ request()->routeIs('sms_templates.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('sms_templates.index') }}"><i class="fa fa-commenting"></i><span class="nav-text">{{ __('sidebar.sms_templates') }}</span></a>
                    </li>
                @endcan @endif
                
                @can('role.view') 
                <li class="{{ request()->routeIs('roles.*') ? 'mm-active' : '' }}">
                    <a class="ai-icon" href="{{ route('roles.index') }}"><i class="la la-shield"></i><span class="nav-text">{{ __('sidebar.permissions.roles') }}</span></a>
                </li> 
                @endcan

            @endif
        </ul>
    </div>
</div>