<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            
            @php
                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('Super Admin');
                $isHeadOfficer = $user->hasRole('Head Officer');
                $isSchoolAdmin = $user->hasRole('School Admin');
                $isTeacher = $user->hasRole('Teacher');
                $isStudent = $user->hasRole('Student');
                
                // Helper to check module access
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

                // Helper to check Institution Type
                $institutionType = 'mixed';
                if (!$isSuperAdmin) {
                    $instId = session('active_institution_id') ?: $user->institute_id;
                    if($instId) {
                        $inst = \App\Models\Institution::find($instId);
                        if($inst) $institutionType = $inst->type;
                    }
                }
            @endphp

            {{-- 1. SUPER ADMIN SECTION --}}
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
                        <li><a href="{{ route('institutes.index') }}">{{ __('sidebar.all_institutions') }}</a></li>
                        <li><a href="{{ route('header-officers.index') }}">{{ __('sidebar.header_officers.title') }}</a></li>
                        <li><a href="{{ route('institutes.index') }}?status=0">{{ __('sidebar.expired_institution') }}</a></li>
                        <li><a href="{{ route('packages.index') }}">{{ __('sidebar.packages.title') }}</a></li>
                        <li><a href="{{ route('subscriptions.index') }}">{{ __('sidebar.subscriptions.title') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.finance') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('subscriptions.invoices') }}">{{ __('sidebar.billing_requests') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.configuration') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.system_config') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('configuration.index') }}">{{ __('sidebar.system_config') }}</a></li>
                        <li><a href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                    </ul>
                </li>
            @endif


            {{-- 2. SCHOOL ADMIN / HEAD OFFICER SECTION --}}
            @if($isSchoolAdmin || $isHeadOfficer)
                
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('dashboard') ? 'mm-active' : '' }}" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>

                {{-- ACADEMICS --}}
                @if(
                    ($hasModule('academic_sessions') && $user->can('academic_session.view')) ||
                    ($hasModule('grade_levels') && $user->can('grade_level.view')) ||
                    ($hasModule('class_sections') && $user->can('class_section.view')) ||
                    ($hasModule('departments') && $user->can('department.view')) ||
                    ($hasModule('subjects') && $user->can('subject.view')) ||
                    ($hasModule('timetables') && $user->can('timetable.view'))
                )
                    <li class="nav-label">{{ __('sidebar.academics') }}</li>
                    
                    @if($hasModule('academic_sessions') && $user->can('academic_session.view'))
                        <li><a class="ai-icon" href="{{ route('academic-sessions.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.sessions.title') }}</span></a></li>
                    @endif

                    @if($hasModule('departments') && $user->can('department.view') && in_array($institutionType, ['university', 'mixed', 'lmd']))
                        <li><a class="ai-icon" href="{{ route('departments.index') }}"><i class="la la-building"></i><span class="nav-text">{{ __('sidebar.departments.title') }}</span></a></li>
                    @endif

                    {{-- PROGRAMS (University Only) --}}
                    @if(in_array($institutionType, ['university', 'mixed', 'lmd']))
                        <li>
                            <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                                <i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.programs') ?? 'Programs' }}</span>
                            </a>
                            <ul aria-expanded="false">
                                <li><a href="{{ route('programs.index') }}">{{ __('lmd.programs_page_title') }}</a></li>
                                <li><a href="{{ route('units.index') }}">{{ __('lmd.units_page_title') }}</a></li>
                            </ul>
                        </li>
                    @endif

                    @if($hasModule('grade_levels') && $user->can('grade_level.view'))
                        <li><a class="ai-icon" href="{{ route('grade-levels.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span></a></li>
                    @endif

                    {{-- Class & Subject Config Group --}}
                    <li class="{{ request()->routeIs('class-sections.*', 'subjects.*', 'class-subjects.*', 'timetables.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-book"></i><span class="nav-text">{{ __('sidebar.class_subjects.title') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('class_sections') && $user->can('class_section.view'))
                                <li><a href="{{ route('class-sections.index') }}">{{ __('sidebar.class_sections.title') }}</a></li>
                            @endif
                            @if($hasModule('subjects') && $user->can('subject.view'))
                                <li><a href="{{ route('subjects.index') }}">{{ __('sidebar.subjects.title') }}</a></li>
                            @endif
                            @if($hasModule('class_subjects') && $user->can('class_subject.view'))
                                <li><a href="{{ route('class-subjects.index') }}">{{ __('sidebar.class_subjects.title') }}</a></li>
                            @endif
                            @if($hasModule('timetables') && $user->can('timetable.view'))
                                <li><a href="{{ route('timetables.index') }}">{{ __('sidebar.timetables.title') }}</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- PEOPLE --}}
                @if(
                    ($hasModule('students') && $user->can('student.view')) || 
                    ($hasModule('student_enrollments') && $user->can('student_enrollment.view')) ||
                    ($hasModule('university_enrollments') && $user->can('university_enrollment.view')) ||
                    ($hasModule('staff') && $user->can('staff.view'))
                )
                    <li class="nav-label">{{ __('sidebar.people') }}</li>
                    
                    <li class="{{ request()->routeIs('students.*', 'parents.*', 'enrollments.*', 'university.enrollments.*', 'promotions.*', 'attendance.*', 'pickups.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-users"></i><span class="nav-text">{{ __('sidebar.students.title') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('students') && $user->can('student.view'))
                                <li><a href="{{ route('students.index') }}">{{ __('sidebar.students.title') }}</a></li>
                            @endif
                            
                            {{-- NEW: Parents Link --}}
                            @can('student.view') 
                                <li><a href="{{ route('parents.index') }}">{{ __('parent.page_title') ?? 'Parents' }}</a></li>
                            @endcan

                            {{-- Standard Enrollment --}}
                            @if($hasModule('student_enrollments') && in_array($institutionType, ['primary', 'secondary', 'mixed', 'vocational']) && $user->can('student_enrollment.view'))
                                <li><a href="{{ route('enrollments.index') }}">{{ __('sidebar.enrollments.title') }}</a></li>
                            @endif

                            {{-- University Enrollment --}}
                            @if($hasModule('university_enrollments') && in_array($institutionType, ['university', 'mixed', 'lmd']) && $user->can('university_enrollment.view'))
                                <li><a href="{{ route('university.enrollments.index') }}">{{ __('sidebar.university_enrollments.title') }}</a></li>
                            @endif

                            @if($hasModule('student_attendance') && $user->can('student_attendance.view'))
                                <li><a href="{{ route('attendance.index') }}">{{ __('sidebar.attendance.title') }}</a></li>
                            @endif

                            @if($hasModule('student_promotion') && $user->can('student_promotion.create'))
                                <li><a href="{{ route('promotions.index') }}">{{ __('sidebar.promotions.title') }}</a></li>
                            @endif
                            
                            {{-- NEW: Pickup System (Admin/Guard View) --}}
                            <li><a href="{{ route('pickups.teacher') }}">{{ __('pickup.manager_title') ?? 'Pickup Requests' }}</a></li>
                            
                            {{-- UPDATED: Pickup System (QR Generator for Admins) --}}
                            <li><a href="{{ route('pickups.parent') }}">{{ __('pickup.page_title') ?? 'Generate Student QR' }}</a></li>
                        </ul>
                    </li>

                    @if($hasModule('staff') && $user->can('staff.view'))
                        <li class="{{ request()->routeIs('staff.*', 'staff-attendance.*') ? 'mm-active' : '' }}">
                            <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-chalkboard-teacher"></i><span class="nav-text">{{ __('sidebar.staff.title') }}</span></a>
                            <ul aria-expanded="false">
                                <li><a href="{{ route('staff.index') }}">{{ __('sidebar.staff.title') }}</a></li>
                                @if($user->can('staff_attendance.view'))
                                    <li><a href="{{ route('staff-attendance.index') }}">{{ __('sidebar.staff_attendance') }}</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                @endif
                
                {{-- NEW: SECURITY / GUARD LINK --}}
                @if($user->hasRole('Guard') || $user->can('student.view'))
                    <li class="nav-label">Security</li>
                    <li>
                        <a class="ai-icon" href="{{ route('pickups.scanner') }}"><i class="la la-qrcode"></i><span class="nav-text">{{ __('pickup.scanner_title') ?? 'QR Scanner' }}</span></a>
                    </li>
                @endif

                {{-- FINANCE --}}
                @if(
                    ($hasModule('invoices') && $user->can('invoice.view')) || 
                    ($hasModule('budgets') && $user->can('budget.view')) ||
                    ($hasModule('payrolls') && $user->can('payroll.view'))
                )
                    <li class="nav-label">{{ __('sidebar.finance') }}</li>
                    
                    {{-- Fees & Collection --}}
                    @if($hasModule('invoices') && $user->can('invoice.view'))
                    <li class="{{ request()->routeIs('fee-types.*', 'fees.*', 'invoices.*', 'finance.balances.*', 'finance.reports.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.fees_collection') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('fee_types') && $user->can('fee_type.view'))
                                <li><a href="{{ route('fee-types.index') }}">{{ __('sidebar.fee_types.title') }}</a></li>
                            @endif
                            @if($hasModule('fee_structures') && $user->can('fee_structure.view'))
                                <li><a href="{{ route('fees.index') }}">{{ __('sidebar.fee_structures.title') }}</a></li>
                            @endif
                            
                            @if($user->can('invoice.create'))
                                <li><a href="{{ route('invoices.create') }}">{{ __('sidebar.invoices.generate') }}</a></li>
                            @endif
                            <li><a href="{{ route('invoices.index') }}">{{ __('sidebar.invoices.list') }}</a></li>
                            <li><a href="{{ route('finance.balances.index') }}">{{ __('sidebar.student_balances') }}</a></li>
                            <li><a href="{{ route('finance.reports.class_summary') }}">{{ __('sidebar.financial_reports') }}</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Budget & Payroll --}}
                    @if(($hasModule('budgets') && $user->can('budget.view')) || ($hasModule('payrolls') && $user->can('payroll.view')))
                    <li class="{{ request()->routeIs('budgets.*', 'payroll.*', 'salary-structures.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-wallet"></i><span class="nav-text">{{ __('sidebar.budget_payroll') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('budgets'))
                                <li><a href="{{ route('budgets.categories') }}">{{ __('sidebar.budget_categories') }}</a></li>
                                <li><a href="{{ route('budgets.index') }}">{{ __('sidebar.budget_allocation') }}</a></li>
                                <li><a href="{{ route('budgets.requests') }}">{{ __('sidebar.fund_requests') }}</a></li>
                            @endif
                            @if($hasModule('payrolls'))
                                <li><a href="{{ route('salary-structures.index') }}">{{ __('sidebar.salary_structures') }}</a></li>
                                <li><a href="{{ route('payroll.index') }}">{{ __('sidebar.generate_payroll') }}</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    {{-- Platform Billing --}}
                    @can('institution.view')
                    <li><a class="ai-icon" href="{{ route('subscriptions.invoices') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.billing') }}</span></a></li>
                    @endcan
                @endif

                {{-- EXAMINATIONS --}}
                @if($hasModule('exams') && $user->can('exam.view'))
                    <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                    <li class="{{ request()->routeIs('exams.*', 'exam-schedules.*', 'marks.*', 'results.*', 'reports.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.examinations') }}</span></a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('exams.index') }}">{{ __('sidebar.exams.title') }}</a></li>
                            
                            @if($hasModule('exam_schedules'))
                                <li><a href="{{ route('exam-schedules.manage') }}">{{ __('sidebar.exam_schedules.manage') }}</a></li>
                            @endif
                            
                            @if($hasModule('exam_marks'))
                                <li><a href="{{ route('marks.create') }}">{{ __('sidebar.marks.title') }}</a></li>
                            @endif
                            
                            <li><a href="{{ route('results.index') }}">{{ __('sidebar.results') }}</a></li>
                            <li><a href="{{ route('reports.index') }}">{{ __('sidebar.academic_reports') }}</a></li>
                        </ul>
                    </li>
                @endif
                
                {{-- COMMUNICATION & VOTING --}}
                @if($hasModule('communication') || $hasModule('voting'))
                    <li class="nav-label">{{ __('sidebar.communication') }}</li>
                    @if($hasModule('communication') && $user->can('notice.view'))
                        <li><a class="ai-icon" href="{{ route('notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.notices.title') }}</span></a></li>
                        
                        {{-- NEW CHATBOT LINK --}}
                        @if($user->hasRole(['Super Admin','School Admin', 'Head Officer']))
                        <li><a class="ai-icon" href="{{ route('chatbot.settings.index') }}"><i class="fa fa-comments"></i><span class="nav-text">{{ __('chatbot.page_title') ?? 'Chatbot' }}</span></a></li>
                        @endif
                    @endif

                    @if($hasModule('voting') && $user->can('election.view'))
                        <li><a class="ai-icon" href="{{ route('elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.elections.title') }}</span></a></li>
                    @endif
                @endif

                {{-- CONFIGURATION --}}
                @if($user->can('setting.manage'))
                    <li class="nav-label">{{ __('sidebar.settings') }}</li>
                    <li class="{{ request()->routeIs('configuration.*', 'settings.*', 'roles.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.settings') }}</span></a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('settings.index') }}">{{ __('settings.page_title') }}</a></li>
                            <li><a href="{{ route('configuration.index') }}">{{ __('configuration.page_title') }}</a></li>
                            <li><a href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                            <li><a href="{{ route('roles.index') }}">{{ __('sidebar.permissions.roles') }}</a></li>
                        </ul>
                    </li>
                @endif

            @endif


            {{-- 3. TEACHER SECTION --}}
            @if($isTeacher)
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>
                
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                <li><a class="ai-icon" href="{{ route('timetables.routine') }}"><i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a></li>
                
                @if($hasModule('assignments'))
                    <li><a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.class_assignments') }}</span></a></li>
                @endif
                
                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                <li><a class="ai-icon" href="{{ route('marks.create') }}"><i class="la la-edit"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a></li>
                
                @if($hasModule('exam_schedules'))
                    <li><a class="ai-icon" href="{{ route('exam-schedules.index') }}"><i class="la la-calendar-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.view_schedule') }}</span></a></li>
                @endif

                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li><a class="ai-icon" href="{{ route('budgets.requests') }}"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.fund_requests') }}</span></a></li>

                {{-- NEW: Teacher Pickup Approval View --}}
                <li class="nav-label">Pickup System</li>
                <li><a class="ai-icon" href="{{ route('pickups.teacher') }}"><i class="la la-child"></i><span class="nav-text">{{ __('pickup.manager_title') ?? 'Pickup Requests' }}</span></a></li>
            @endif


            {{-- 4. STUDENT SECTION --}}
            @if($isStudent)
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>
                
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                <li><a class="ai-icon" href="{{ route('timetables.routine') }}"><i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a></li>
                
                @if($hasModule('assignments'))
                    <li><a class="ai-icon" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.my_assignments') }}</span></a></li>
                @endif

                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                <li><a class="ai-icon" href="{{ route('marks.my_marks') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a></li>
                
                @if($hasModule('exam_schedules'))
                    <li><a class="ai-icon" href="{{ route('exam-schedules.index') }}"><i class="la la-calendar-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.view_schedule') }}</span></a></li>
                @endif

                <li class="nav-label">{{ __('sidebar.communication') }}</li>
                <li><a class="ai-icon" href="{{ route('student.notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.my_notices') }}</span></a></li>
                
                @if($hasModule('voting'))
                    <li><a class="ai-icon" href="{{ route('student.elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.my_elections') }}</span></a></li>
                @endif
            @endif

            {{-- 5. PARENT / GUARDIAN SECTION --}}
            @if(auth()->user()->hasRole('Guardian'))
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                {{-- NEW: Pickup QR Generation --}}
                <li><a class="ai-icon" href="{{ route('pickups.parent') }}"><i class="la la-qrcode"></i><span class="nav-text">{{ __('pickup.page_title') ?? 'Student Pickup' }}</span></a></li>
            @endif

        </ul>
    </div>
</div>