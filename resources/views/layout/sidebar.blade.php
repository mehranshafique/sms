<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            
            @php
                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('Super Admin');
                $modules = $enabledModules ?? []; // Shared via Middleware
                
                // Helper to check if a specific module is enabled
                $hasModule = function($slug) use ($modules, $isSuperAdmin) {
                    return $isSuperAdmin || in_array($slug, $modules);
                };
            @endphp

            {{-- ========================================================= --}}
            {{-- MAIN ADMIN (PLATFORM OWNER) MENU --}}
            {{-- ========================================================= --}}
            @if($isSuperAdmin)
                
                <li class="nav-label first">{{ __('sidebar.main_admin') }}</li>
                
                <li>
                    <a class="ai-icon" href="{{ route('dashboard') }}">
                        <i class="la la-home"></i>
                        <span class="nav-text">{{ __('sidebar.dashboard.title') }}</span>
                    </a>
                </li>
                
                <li>
                    <a class="ai-icon" href="{{ route('roles.index') }}">
                        <i class="la la-shield"></i>
                        <span class="nav-text">{{ __('sidebar.permissions.roles') }}</span>
                    </a>
                </li>

                {{-- REPORTING --}}
                <li class="nav-label">{{ __('sidebar.reporting') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.creation') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('institutes.create') }}">{{ __('sidebar.institution_creation') }}</a></li>
                        <li><a href="{{ route('header-officers.create') }}">{{ __('sidebar.headoff_creation') }}</a></li>
                        <li><a href="{{ route('audit-logs.index') }}">{{ __('sidebar.audit_log') }}</a></li>
                    </ul>
                </li>

                {{-- MANAGEMENT --}}
                <li class="nav-label">{{ __('sidebar.management') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-university"></i><span class="nav-text">{{ __('sidebar.institution_mgmt') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('institutes.index') }}">{{ __('sidebar.all_institutions') }}</a></li>
                        <li><a href="{{ route('header-officers.index') }}">{{ __('sidebar.header_officers.title') }}</a></li>
                        <li><a href="{{ route('institutes.index') }}?status=0">{{ __('sidebar.expired_institution') }}</a></li>
                        <li><a href="{{ route('packages.index') }}">{{ __('sidebar.packages.title') }}</a></li>
                        <li><a href="{{ route('subscriptions.index') }}">{{ __('sidebar.subscriptions.title') }}</a></li>
                    </ul>
                </li>

                {{-- CONFIGURATION --}}
                <li class="nav-label">{{ __('sidebar.configuration') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.system_config') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('configuration.index') }}#smtp">{{ __('sidebar.smtp') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#sms">{{ __('sidebar.id_sender_sms') }}</a></li>
                        <li><a href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#school_year">{{ __('sidebar.school_year') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#modules">{{ __('sidebar.modules') }}</a></li>
                        <li><a href="{{ route('configuration.index') }}#recharge">{{ __('sidebar.recharging') }}</a></li>
                    </ul>
                </li>

                {{-- FINANCE --}}
                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-money"></i><span class="nav-text">{{ __('sidebar.finance') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('subscriptions.index') }}">Billing</a></li> 
                        <li><a href="{{ route('subscriptions.invoices') }}">{{ __('sidebar.billing_requests') }} (Invoices)</a></li>
                    </ul>
                </li>

            {{-- ========================================================= --}}
            {{-- SCHOOL OPERATIONS MENU --}}
            {{-- ========================================================= --}}
            @else
                
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li>
                    <a class="ai-icon" href="{{ route('dashboard') }}">
                        <i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span>
                    </a>
                </li>

                {{-- ACADEMICS GROUP --}}
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                
                @if($hasModule('academic_sessions'))
                    @can('academic_session.view')
                    <li><a class="ai-icon" href="{{ route('academic-sessions.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.sessions.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('grade_levels'))
                    @can('grade_level.view')
                    <li><a class="ai-icon" href="{{ route('grade-levels.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('class_sections'))
                    @can('class_section.view')
                    <li><a class="ai-icon" href="{{ route('class-sections.index') }}"><i class="la la-th-list"></i><span class="nav-text">{{ __('sidebar.class_sections.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('subjects'))
                    @can('subject.view')
                    <li><a class="ai-icon" href="{{ route('subjects.index') }}"><i class="la la-book"></i><span class="nav-text">{{ __('sidebar.subjects.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('timetables'))
                    @can('timetable.view')
                    <li><a class="ai-icon" href="{{ route('timetables.index') }}"><i class="la la-clock-o"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a></li>
                    @endcan
                @endif

                {{-- EXAMINATIONS GROUP --}}
                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                
                @if($hasModule('exams'))
                    @can('exam.view')
                    <li><a class="ai-icon" href="{{ route('exams.index') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.exams.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('exam_marks'))
                    @can('exam_mark.create')
                    <li><a class="ai-icon" href="{{ route('marks.create') }}"><i class="la la-edit"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a></li>
                    @endcan
                @endif

                {{-- NEW: RESULT CARD MODULE --}}
                @if($hasModule('results') || $hasModule('exams')) 
                    @if(auth()->user()->can('view result_card') || auth()->user()->hasRole(['Super Admin', 'Head Officer', 'Teacher', 'Student']))
                    <li><a class="ai-icon" href="{{ route('results.index') }}"><i class="la la-certificate"></i><span class="nav-text">{{ __('sidebar.results') }}</span></a></li>
                    @endif
                @endif

                {{-- COMMUNICATION GROUP --}}
                @if($hasModule('communication'))
                <li class="nav-label">{{ __('sidebar.communication') }}</li>
                    @can('notice.view')
                    <li><a class="ai-icon" href="{{ route('notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.notices.title') }}</span></a></li>
                    @endcan
                    @if(auth()->user()->hasRole('Student'))
                    <li><a class="ai-icon" href="{{ route('student.notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.my_notices') }}</span></a></li>
                    @endif
                @endif

                {{-- VOTING GROUP --}}
                @if($hasModule('voting'))
                <li class="nav-label">{{ __('sidebar.voting') }}</li>
                    @can('election.view')
                    <li><a class="ai-icon" href="{{ route('elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.elections.title') }}</span></a></li>
                    @endcan
                    @if(auth()->user()->hasRole('Student'))
                    <li><a class="ai-icon" href="{{ route('student.elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.my_elections') }}</span></a></li>
                    @endif
                @endif

                {{-- PEOPLE GROUP --}}
                <li class="nav-label">{{ __('sidebar.people') }}</li>
                
                @if($hasModule('students'))
                    @can('student.view')
                    <li><a class="ai-icon" href="{{ route('students.index') }}"><i class="la la-users"></i><span class="nav-text">{{ __('sidebar.students.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('enrollments'))
                    @can('student_enrollment.view')
                    <li><a class="ai-icon" href="{{ route('enrollments.index') }}"><i class="la la-id-card"></i><span class="nav-text">{{ __('sidebar.enrollments.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('student_attendance'))
                    @can('student_attendance.view')
                    <li><a class="ai-icon" href="{{ route('attendance.index') }}"><i class="la la-check-square"></i><span class="nav-text">{{ __('sidebar.attendance.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('student_promotion'))
                    @can('student_promotion.view')
                    <li><a class="ai-icon" href="{{ route('promotions.index') }}"><i class="la la-level-up"></i><span class="nav-text">{{ __('sidebar.promotions.title') }}</span></a></li>
                    @endcan
                @endif
                
                @if($hasModule('staff'))
                    @can('staff.view')
                    <li><a class="ai-icon" href="{{ route('staff.index') }}"><i class="la la-chalkboard-teacher"></i><span class="nav-text">{{ __('sidebar.staff.title') }}</span></a></li>
                    @endcan
                @endif

                {{-- FINANCE GROUP --}}
                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                
                {{-- Consolidated Finance Check --}}
                @if($hasModule('fee_structures') || $hasModule('fee_types') || $hasModule('invoices') || $hasModule('payrolls'))
                    @can('fee_structure.view')
                    <li>
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                            <i class="la la-money"></i><span class="nav-text">{{ __('sidebar.finance') }}</span>
                        </a>
                        <ul aria-expanded="false">
                            @if($hasModule('fee_types'))
                                <li><a href="{{ route('fee-types.index') }}">{{ __('sidebar.fee_types.title') }}</a></li>
                            @endif
                            @if($hasModule('fee_structures'))
                                <li><a href="{{ route('fees.index') }}">{{ __('sidebar.fee_structures.title') }}</a></li>
                            @endif
                            @if($hasModule('invoices'))
                                <li><a href="{{ route('invoices.create') }}">{{ __('sidebar.invoices.generate') }}</a></li>
                                <li><a href="{{ route('invoices.index') }}">{{ __('sidebar.invoices.list') }}</a></li>
                            @endif
                            {{-- NEW PAYROLL MODULES --}}
                            @if($hasModule('payrolls'))
                                <li><a href="{{ route('salary-structures.index') }}">{{ __('sidebar.salary_structures') }}</a></li>
                                <li><a href="{{ route('payroll.index') }}">{{ __('sidebar.generate_payroll') }}</a></li>
                            @endif
                        </ul>
                    </li>
                    @can('institution.view') {{-- Assuming Head Officer has this --}}
                    <li>
                        <a href="{{ route('subscriptions.invoices') }}" class="ai-icon" aria-expanded="false">
                            <i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.billing') }}</span>
                        </a>
                    </li>
                    @endcan
                    @endcan
                @endif

                {{-- SETTINGS GROUP --}}
                <li class="nav-label">{{ __('sidebar.settings') }}</li>
                
                @can('institution.view')
                <li><a class="ai-icon" href="{{ route('settings.index') }}"><i class="la la-cogs"></i><span class="nav-text">{{ __('settings.page_title') }}</span></a></li>
                @endcan
                
                @if($hasModule('settings'))
                    @can('institution.update')
                    <li><a class="ai-icon" href="{{ route('configuration.index') }}"><i class="fa fa-sliders"></i><span class="nav-text">{{ __('configuration.page_title') }}</span></a></li>
                    {{-- Added SMS Templates Link --}}
                    <li><a class="ai-icon" href="{{ route('sms_templates.index') }}"><i class="fa fa-commenting"></i><span class="nav-text">{{ __('sidebar.sms_templates') }}</span></a></li>
                    @endcan
                @endif
                
                @can('role.view')
                <li><a class="ai-icon" href="{{ route('roles.index') }}"><i class="la la-shield"></i><span class="nav-text">{{ __('sidebar.permissions.roles') }}</span></a></li>
                @endcan

            @endif

        </ul>
    </div>
</div>