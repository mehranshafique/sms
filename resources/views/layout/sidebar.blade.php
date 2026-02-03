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

                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                
                {{-- Academics Links --}}
                @if($hasModule('academic_sessions'))
                    @can('academic_session.view')<li><a class="ai-icon" href="{{ route('academic-sessions.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.sessions.title') }}</span></a></li>@endcan
                @endif
                
                {{-- Department (Show only if University/Mixed) --}}
                @if($hasModule('departments') && in_array($institutionType, ['university', 'mixed'])) 
                    @can('department.view')<li><a class="ai-icon" href="{{ route('departments.index') }}"><i class="la la-building"></i><span class="nav-text">{{ __('sidebar.departments.title') }}</span></a></li>@endcan 
                @endif

                @if($hasModule('grade_levels'))
                    @can('grade_level.view')<li><a class="ai-icon" href="{{ route('grade-levels.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span></a></li>@endcan
                @endif
                
                {{-- CLASS & SUBJECTS GROUP --}}
                @if($hasModule('class_sections') || $hasModule('subjects') || $hasModule('class_subjects') || $hasModule('timetables'))
                <li class="{{ request()->routeIs('class-sections.*', 'subjects.*', 'class-subjects.*', 'timetables.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-book"></i><span class="nav-text">{{ __('sidebar.class_subjects.title') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        @if($hasModule('class_sections')) @can('class_section.view') 
                            <li><a href="{{ route('class-sections.index') }}">{{ __('sidebar.class_sections.title') }}</a></li> 
                        @endcan @endif
                        
                        @if($hasModule('subjects')) @can('subject.view') 
                            <li><a href="{{ route('subjects.index') }}">{{ __('sidebar.subjects.title') }}</a></li> 
                        @endcan @endif
                        
                        @if($hasModule('class_subjects')) @can('class_subject.view') 
                            <li><a href="{{ route('class-subjects.index') }}">{{ __('sidebar.class_subjects.title') }}</a></li> 
                        @endcan @endif

                        @if($hasModule('timetables')) @can('timetable.view') 
                            <li><a href="{{ route('timetables.index') }}">{{ __('sidebar.timetables.title') }}</a></li> 
                        @endcan @endif
                    </ul>
                </li>
                @endif

                {{-- STUDENTS GROUP --}}
                <li class="nav-label">{{ __('sidebar.people') }}</li>
                @if($hasModule('students') || $hasModule('enrollments') || $hasModule('student_attendance') || $hasModule('student_promotion'))
                <li class="{{ request()->routeIs('students.*', 'enrollments.*', 'university.enrollments.*', 'attendance.*', 'promotions.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-users"></i><span class="nav-text">{{ __('sidebar.students.title') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        @if($hasModule('students'))
                            @can('student.view')<li><a href="{{ route('students.index') }}">{{ __('sidebar.students.title') }}</a></li>@endcan
                        @endif

                        @if($hasModule('enrollments'))
                            @if(in_array($institutionType, ['primary', 'secondary', 'mixed', 'vocational']))
                                @can('student_enrollment.view')
                                <li><a href="{{ route('enrollments.index') }}">{{ __('sidebar.enrollments.title') }}</a></li>
                                @endcan
                            @endif
                            @if(in_array($institutionType, ['university', 'mixed']))
                                @can('university_enrollment.view')
                                <li><a href="{{ route('university.enrollments.index') }}">{{ __('sidebar.university_enrollments.title') }}</a></li>
                                @endcan
                            @endif
                        @endif
                        
                        @if($hasModule('student_attendance')) @can('student_attendance.view')<li><a href="{{ route('attendance.index') }}">{{ __('sidebar.attendance.title') }}</a></li>@endcan @endif
                        @if($hasModule('student_promotion')) @can('student_promotion.view')<li><a href="{{ route('promotions.index') }}">{{ __('sidebar.promotions.title') }}</a></li>@endcan @endif
                    </ul>
                </li>
                @endif

                {{-- STAFF GROUP --}}
                @if($hasModule('staff'))
                <li class="{{ request()->routeIs('staff.*', 'staff-attendance.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-chalkboard-teacher"></i><span class="nav-text">{{ __('sidebar.staff.title') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        @can('staff.view')<li><a href="{{ route('staff.index') }}">{{ __('sidebar.staff.title') }}</a></li>@endcan
                        @can('staff_attendance.view')<li><a href="{{ route('staff-attendance.index') }}">{{ __('sidebar.staff_attendance') }}</a></li>@endcan
                    </ul>
                </li>
                @endif

                {{-- EXAMINATIONS GROUP --}}
                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                @if($hasModule('exams') || $hasModule('exam_schedules') || $hasModule('assignments') || $hasModule('exam_marks') || $hasModule('results') || $hasModule('examinations'))
                <li class="{{ request()->routeIs('exams.*', 'exam-schedules.*', 'assignments.*', 'marks.*', 'results.*', 'reports.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.examinations') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        @if($hasModule('exams')) @can('exam.view') 
                        <li><a href="{{ route('exams.index') }}">{{ __('sidebar.exams.title') }}</a></li> 
                        @endcan @endif
                        
                        @if($hasModule('exam_schedules'))
                            @if($isStudent || $isTeacher)
                                <li><a href="{{ route('exam-schedules.index') }}">{{ __('sidebar.exam_schedules.view_schedule') }}</a></li>
                            @elseif($user->can('exam_schedule.create'))
                                <li><a href="{{ route('exam-schedules.manage') }}">{{ __('sidebar.exam_schedules.manage') }}</a></li>
                            @endif
                        @endif

                        @if($hasModule('assignments'))
                            @if($isStudent)
                                <li><a href="{{ route('assignments.index') }}">{{ __('sidebar.assignments.my_assignments') }}</a></li>
                            @elseif($isTeacher)
                                <li><a href="{{ route('assignments.index') }}">{{ __('sidebar.assignments.class_assignments') }}</a></li>
                            @elseif($user->can('assignment.view'))
                                <li><a href="{{ route('assignments.index') }}">{{ __('sidebar.assignments.title') }}</a></li>
                            @endif
                        @endif

                        @if($hasModule('exam_marks')) @can('exam_mark.create') 
                        <li><a href="{{ route('marks.create') }}">{{ __('sidebar.marks.title') }}</a></li> 
                        @endcan @endif

                        @if($hasModule('results') || $hasModule('examinations')) 
                            @if(auth()->user()->can('view result_card') || $isStudent || $isTeacher)
                            <li><a href="{{ route('results.index') }}">{{ __('sidebar.results') }}</a></li>
                            @endif
                        @endif

                        @if($hasModule('examinations')) 
                        <li><a href="{{ route('reports.index') }}">{{ __('sidebar.academic_reports') }}</a></li> 
                        @endif
                    </ul>
                </li>
                @endif

                {{-- FINANCE --}}
                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                @if($hasModule('fee_structures') || $hasModule('fee_types') || $hasModule('invoices'))
                    <li class="{{ request()->routeIs('fee-types.*', 'fees.*', 'invoices.*', 'finance.balances.*', 'finance.reports.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.fees_collection') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('fee_types')) @can('fee_type.view')<li><a href="{{ route('fee-types.index') }}">{{ __('sidebar.fee_types.title') }}</a></li>@endcan @endif
                            @if($hasModule('fee_structures')) @can('fee_structure.view')<li><a href="{{ route('fees.index') }}">{{ __('sidebar.fee_structures.title') }}</a></li>@endcan @endif
                            @if($hasModule('invoices'))
                                @can('invoice.create')<li><a href="{{ route('invoices.create') }}">{{ __('sidebar.invoices.generate') }}</a></li>@endcan
                                @can('invoice.view')
                                    <li><a href="{{ route('invoices.index') }}">{{ __('sidebar.invoices.list') }}</a></li>
                                    <li><a href="{{ route('finance.balances.index') }}">{{ __('sidebar.student_balances') }}</a></li>
                                @endcan
                            @endif
                            @can('invoice.view')<li><a href="{{ route('finance.reports.class_summary') }}">{{ __('sidebar.financial_reports') }}</a></li>@endcan
                        </ul>
                    </li>
                @endif

                @if($hasModule('payrolls') || $hasModule('budgets'))
                    <li class="{{ request()->routeIs('salary-structures.*', 'payroll.*', 'budgets.*') ? 'mm-active' : '' }}">
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
                
                @can('institution.view') 
                <li class="{{ request()->routeIs('subscriptions.invoices') ? 'mm-active' : '' }}">
                    <a href="{{ route('subscriptions.invoices') }}" class="ai-icon"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.billing') }}</span></a>
                </li> 
                @endcan

                {{-- COMMUNICATION --}}
                @if($hasModule('communication'))
                <li class="nav-label">{{ __('sidebar.communication') }}</li>
                    @can('notice.view')<li><a class="ai-icon" href="{{ route('notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.notices.title') }}</span></a></li>@endcan
                    @if($isStudent) 
                    <li class="{{ request()->routeIs('student.notices.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('student.notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.my_notices') }}</span></a>
                    </li> 
                    @endif
                @endif

                @if($hasModule('voting'))
                <li class="nav-label">{{ __('sidebar.voting') }}</li>
                    @can('election.view')<li><a class="ai-icon" href="{{ route('elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.elections.title') }}</span></a></li>@endcan
                    @if($isStudent) 
                    <li class="{{ request()->routeIs('student.elections.*') ? 'mm-active' : '' }}">
                        <a class="ai-icon" href="{{ route('student.elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.my_elections') }}</span></a>
                    </li> 
                    @endif
                @endif

                {{-- SETTINGS GROUP --}}
                <li class="nav-label">{{ __('sidebar.settings') }}</li>
                @can('institution.view')
                <li class="{{ request()->routeIs('settings.*', 'configuration.*', 'sms_templates.*', 'roles.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.settings') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('settings.index') }}">{{ __('settings.page_title') }}</a></li>
                        
                        @if($hasModule('settings')) @can('institution.update')
                            <li><a href="{{ route('configuration.index') }}">{{ __('configuration.page_title') }}</a></li>
                            <li><a href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                        @endcan @endif
                        
                        @can('role.view')<li><a href="{{ route('roles.index') }}">{{ __('sidebar.permissions.roles') }}</a></li>@endcan
                    </ul>
                </li> 
                @endcan

            @endif
        </ul>
    </div>
</div>