<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            
            @php
                use App\Enums\RoleEnum;
                use App\Enums\InstitutionType;
                use App\Enums\UserType;
                use App\Models\InstitutionSetting;
                use App\Models\Subscription;
                use App\Models\Institution;

                $user = auth()->user();
                
                // Role Checks using Enums
                $isSuperAdmin = $user->hasRole(RoleEnum::SUPER_ADMIN->value);
                $isHeadOfficer = $user->hasRole(RoleEnum::HEAD_OFFICER->value);
                $isSchoolAdmin = $user->hasRole(RoleEnum::SCHOOL_ADMIN->value);
                $isTeacher = $user->hasRole(RoleEnum::TEACHER->value);
                $isStudent = $user->hasRole(RoleEnum::STUDENT->value);
                $isGuardian = $user->hasRole(RoleEnum::GUARDIAN->value);
                
                // Determine Active Context
                $activeInstId = session('active_institution_id');
                
                // Super Admin is in "Global Mode" ONLY if no specific institution is selected
                $isGlobalMode = $isSuperAdmin && (!$activeInstId || $activeInstId === 'global');
                
                // Get Active Institution Object (for ID display and Type check)
                $activeInstitution = null;
                $activeInstType = null;

                // Only fetch institution details if we are NOT in global mode, or if user is strictly a school-level user
                if (!$isGlobalMode || $isHeadOfficer || $isSchoolAdmin) {
                    $targetId = $activeInstId ?: $user->institute_id;
                    if ($targetId && $targetId !== 'global') {
                        $activeInstitution = Institution::find($targetId);
                        $activeInstType = $activeInstitution ? $activeInstitution->type : null;
                    }
                }

                // Helper to check module access
                if (!isset($enabledModules)) {
                    $enabledModules = [];
                    if ($activeInstitution) {
                        $setting = InstitutionSetting::where('institution_id', $activeInstitution->id)
                            ->where('key', 'enabled_modules')
                            ->first();
                        
                        if ($setting && $setting->value) {
                            $enabledModules = is_array($setting->value) ? $setting->value : json_decode($setting->value, true);
                        } else {
                            $sub = Subscription::with('package')
                                ->where('institution_id', $activeInstitution->id)
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

            {{-- ============================================================= --}}
            {{-- PART 1: SUPER ADMIN GLOBAL SECTION --}}
            {{-- ============================================================= --}}
            {{-- Visible ONLY to Super Admins (Always visible) --}}
            @if($isSuperAdmin)
                <li class="nav-label first">{{ __('sidebar.main_admin') }}</li>
                
                {{-- Global Dashboard Link --}}
                <li>
                    <a class="ai-icon {{ request()->routeIs('dashboard') && $isGlobalMode ? 'mm-active' : '' }}" 
                       href="{{ $isGlobalMode ? route('dashboard') : route('institution.switch', 'global') }}">
                        <i class="la la-home"></i>
                        <span class="nav-text">{{ __('sidebar.dashboard.title') }}</span>
                    </a>
                </li>
                
                {{-- Permissions & Modules --}}
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-shield"></i><span class="nav-text">{{ __('sidebar.permissions.title') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a class="{{ request()->routeIs('roles.*') ? 'mm-active' : '' }}" href="{{ route('roles.index') }}">{{ __('sidebar.permissions.roles') }}</a></li>
                        {{-- Modules link removed as requested --}}
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.reporting') }}</li>
                <li class="{{ request()->routeIs('institutes.create', 'header-officers.create', 'audit-logs.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.creation') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a class="{{ request()->routeIs('institutes.create') ? 'mm-active' : '' }}" href="{{ route('institutes.create') }}">{{ __('sidebar.institution_creation') }}</a></li>
                        <li><a class="{{ request()->routeIs('header-officers.create') ? 'mm-active' : '' }}" href="{{ route('header-officers.create') }}">{{ __('sidebar.headoff_creation') }}</a></li>
                        <li><a class="{{ request()->routeIs('audit-logs.*') ? 'mm-active' : '' }}" href="{{ route('audit-logs.index') }}">{{ __('sidebar.audit_log') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.management') }}</li>
                <li class="{{ request()->routeIs('institutes.index', 'institutes.edit', 'institutes.show', 'campuses.*', 'header-officers.index', 'header-officers.edit', 'packages.*', 'subscriptions.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-university"></i><span class="nav-text">{{ __('sidebar.institution_mgmt') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a class="{{ request()->routeIs('institutes.index', 'institutes.edit', 'institutes.show') ? 'mm-active' : '' }}" href="{{ route('institutes.index') }}">{{ __('sidebar.all_institutions') }}</a></li>
                        <li><a class="{{ request()->routeIs('campuses.*') ? 'mm-active' : '' }}" href="{{ route('campuses.index') }}">{{ __('sidebar.campuses.title') }}</a></li>
                        <li><a class="{{ request()->routeIs('header-officers.index', 'header-officers.edit') ? 'mm-active' : '' }}" href="{{ route('header-officers.index') }}">{{ __('sidebar.header_officers.title') }}</a></li>
                        <li><a href="{{ route('institutes.index') }}?status=0">{{ __('sidebar.expired_institution') }}</a></li>
                        <li><a class="{{ request()->routeIs('packages.*') ? 'mm-active' : '' }}" href="{{ route('packages.index') }}">{{ __('sidebar.packages.title') }}</a></li>
                        <li><a class="{{ request()->routeIs('subscriptions.index', 'subscriptions.edit') ? 'mm-active' : '' }}" href="{{ route('subscriptions.index') }}">{{ __('sidebar.subscriptions.title') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.finance') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a class="{{ request()->routeIs('subscriptions.invoices*') ? 'mm-active' : '' }}" href="{{ route('subscriptions.invoices') }}">{{ __('sidebar.billing_requests') }}</a></li>
                    </ul>
                </li>

                <li class="nav-label">{{ __('sidebar.configuration') }}</li>
                <li>
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.system_config') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a class="{{ request()->routeIs('configuration.index') ? 'mm-active' : '' }}" href="{{ route('configuration.index') }}">{{ __('sidebar.system_config') }}</a></li>
                        <li><a class="{{ request()->routeIs('sms_templates.*') ? 'mm-active' : '' }}" href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                    </ul>
                </li>
            @endif


            {{-- ============================================================= --}}
            {{-- PART 2: SCHOOL CONTEXT SECTION --}}
            {{-- ============================================================= --}}
            
            @if(($isSchoolAdmin || $isHeadOfficer) || ($isSuperAdmin && $activeInstitution))
                
                {{-- SEPARATOR / HEADER WITH SCHOOL ID --}}
                <li class="nav-label first" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    {{ RoleEnum::SCHOOL_ADMIN->value }} <br>
                    @if($activeInstitution)
                        <span style="font-size: 11px; opacity: 0.7; font-weight: normal; letter-spacing: 1px;">
                            ({{ $activeInstitution->code ?? $activeInstitution->id }})
                        </span>
                    @endif
                </li>

                <li><a class="ai-icon {{ request()->routeIs('dashboard') && !$isGlobalMode ? 'mm-active' : '' }}" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>

                {{-- ACADEMICS --}}
                @if(
                    ($hasModule('academic_sessions') && $user->can('academic_session.view')) ||
                    ($hasModule('grade_levels') && $user->can('grade_level.view')) ||
                    ($hasModule('class_sections') && $user->can('class_section.view')) ||
                    ($hasModule('departments') && $user->can('department.view')) ||
                    ($hasModule('subjects') && $user->can('subject.view')) ||
                    ($hasModule('timetables') && $user->can('timetable.view')) ||
                    ($hasModule('assignments') && $user->can('assignment.view'))
                )
                    <li class="nav-label">{{ __('sidebar.academics') }}</li>
                    
                    @if($hasModule('academic_sessions') && $user->can('academic_session.view'))
                        <li><a class="ai-icon {{ request()->routeIs('academic-sessions.*') ? 'mm-active' : '' }}" href="{{ route('academic-sessions.index') }}"><i class="la la-calendar-check-o"></i><span class="nav-text">{{ __('sidebar.sessions.title') }}</span></a></li>
                    @endif

                    @if($hasModule('departments') && $user->can('department.view') && in_array($activeInstType, [InstitutionType::UNIVERSITY->value, 'mixed', 'lmd']))
                        <li><a class="ai-icon {{ request()->routeIs('departments.*') ? 'mm-active' : '' }}" href="{{ route('departments.index') }}"><i class="la la-building"></i><span class="nav-text">{{ __('sidebar.departments.title') }}</span></a></li>
                    @endif

                    {{-- PROGRAMS (University Only) --}}
                    @if(in_array($activeInstType, [InstitutionType::UNIVERSITY->value, 'mixed', 'lmd']))
                        <li>
                            <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                                <i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.programs') ?? 'Programs' }}</span>
                            </a>
                            <ul aria-expanded="false">
                                <li><a class="{{ request()->routeIs('programs.*') ? 'mm-active' : '' }}" href="{{ route('programs.index') }}">{{ __('lmd.programs_page_title') ?? 'Programs' }}</a></li>
                                <li><a class="{{ request()->routeIs('units.*') ? 'mm-active' : '' }}" href="{{ route('units.index') }}">{{ __('lmd.units_page_title') ?? 'Academic Units' }}</a></li>
                            </ul>
                        </li>
                    @endif

                    @if($hasModule('grade_levels') && $user->can('grade_level.view'))
                        <li><a class="ai-icon {{ request()->routeIs('grade-levels.*') ? 'mm-active' : '' }}" href="{{ route('grade-levels.index') }}"><i class="la la-graduation-cap"></i><span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span></a></li>
                    @endif

                    {{-- Class & Subject Config Group --}}
                    <li class="{{ request()->routeIs('class-sections.*', 'subjects.*', 'class-subjects.*', 'timetables.*', 'assignments.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-book"></i><span class="nav-text">{{ __('sidebar.class_subjects.title') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('class_sections') && $user->can('class_section.view'))
                                <li><a class="{{ request()->routeIs('class-sections.*') ? 'mm-active' : '' }}" href="{{ route('class-sections.index') }}">{{ __('sidebar.class_sections.title') }}</a></li>
                            @endif
                            @if($hasModule('subjects') && $user->can('subject.view'))
                                <li><a class="{{ request()->routeIs('subjects.*') ? 'mm-active' : '' }}" href="{{ route('subjects.index') }}">{{ __('sidebar.subjects.title') }}</a></li>
                            @endif
                            @if($hasModule('class_subjects') && $user->can('class_subject.view'))
                                <li><a class="{{ request()->routeIs('class-subjects.*') ? 'mm-active' : '' }}" href="{{ route('class-subjects.index') }}">{{ __('sidebar.class_subjects.title') }}</a></li>
                            @endif
                            @if($hasModule('timetables') && $user->can('timetable.view'))
                                <li><a class="{{ request()->routeIs('timetables.*') ? 'mm-active' : '' }}" href="{{ route('timetables.index') }}">{{ __('sidebar.timetables.title') }}</a></li>
                            @endif
                            @if($hasModule('assignments') && $user->can('assignment.view'))
                                <li><a class="{{ request()->routeIs('assignments.*') ? 'mm-active' : '' }}" href="{{ route('assignments.index') }}">{{ __('sidebar.assignments.title') }}</a></li>
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
                    
                    <li class="{{ request()->routeIs('students.*', 'parents.*', 'enrollments.*', 'university.enrollments.*', 'promotions.*', 'attendance.*', 'pickups.*', 'transfers.*', 'requests.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-users"></i><span class="nav-text">{{ __('sidebar.students.title') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('students') && $user->can('student.view'))
                                <li><a class="{{ request()->routeIs('students.*') ? 'mm-active' : '' }}" href="{{ route('students.index') }}">{{ __('sidebar.students.title') }}</a></li>
                            @endif
                            
                            @can('student.view') 
                                <li><a class="{{ request()->routeIs('parents.*') ? 'mm-active' : '' }}" href="{{ route('parents.index') }}">{{ __('parent.page_title') ?? 'Parents' }}</a></li>
                            @endcan

                            {{-- Standard Enrollment --}}
                            @if($hasModule('student_enrollments') && in_array($activeInstType, [InstitutionType::PRIMARY->value, InstitutionType::SECONDARY->value, 'mixed', InstitutionType::VOCATIONAL->value]) && $user->can('student_enrollment.view'))
                                <li><a class="{{ request()->routeIs('enrollments.*') ? 'mm-active' : '' }}" href="{{ route('enrollments.index') }}">{{ __('sidebar.enrollments.title') }}</a></li>
                            @endif

                            {{-- University Enrollment --}}
                            @if($hasModule('university_enrollments') && in_array($activeInstType, [InstitutionType::UNIVERSITY->value, 'mixed', 'lmd']) && $user->can('university_enrollment.view'))
                                <li><a class="{{ request()->routeIs('university.enrollments.*') ? 'mm-active' : '' }}" href="{{ route('university.enrollments.index') }}">{{ __('sidebar.university_enrollments.title') }}</a></li>
                            @endif

                            @if($hasModule('student_attendance') && $user->can('student_attendance.view'))
                                <li><a class="{{ request()->routeIs('attendance.*') ? 'mm-active' : '' }}" href="{{ route('attendance.index') }}">{{ __('sidebar.attendance.title') }}</a></li>
                            @endif

                            @if($hasModule('student_promotion') && $user->can('student_promotion.create'))
                                <li><a class="{{ request()->routeIs('promotions.*') ? 'mm-active' : '' }}" href="{{ route('promotions.index') }}">{{ __('sidebar.promotions.title') }}</a></li>
                            @endif
                            
                            {{-- Requests / Leaves --}}
                            <li><a class="{{ request()->routeIs('requests.*') ? 'mm-active' : '' }}" href="{{ route('requests.index') }}">{{ __('sidebar.requests') }}</a></li>
                            
                            <li><a class="{{ request()->routeIs('pickups.teacher') ? 'mm-active' : '' }}" href="{{ route('pickups.teacher') }}">{{ __('pickup.manager_title') ?? 'Pickup Requests' }}</a></li>
                            <li><a class="{{ request()->routeIs('pickups.parent') ? 'mm-active' : '' }}" href="{{ route('pickups.parent') }}">{{ __('pickup.page_title') ?? 'Generate Student QR' }}</a></li>
                        </ul>
                    </li>

                    @if($hasModule('staff') && $user->can('staff.view'))
                        <li class="{{ request()->routeIs('staff.*', 'staff-attendance.*') ? 'mm-active' : '' }}">
                            <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-chalkboard-teacher"></i><span class="nav-text">{{ __('sidebar.staff.title') }}</span></a>
                            <ul aria-expanded="false">
                                <li><a class="{{ request()->routeIs('staff.*') ? 'mm-active' : '' }}" href="{{ route('staff.index') }}">{{ __('sidebar.staff.title') }}</a></li>
                                @if($user->can('staff_attendance.view'))
                                    <li><a class="{{ request()->routeIs('staff-attendance.*') ? 'mm-active' : '' }}" href="{{ route('staff-attendance.index') }}">{{ __('sidebar.staff_attendance') }}</a></li>
                                @endif
                                 <li><a class="{{ request()->routeIs('staff-leaves.*') ? 'mm-active' : '' }}" href="{{ route('staff-leaves.index') }}">{{ __('sidebar.staff_leaves') }}</a></li>
                            </ul>
                        </li>
                    @endif
                @endif
                
                {{-- SECURITY / GUARD LINK --}}
                @if($user->hasRole('Guard') || $user->can('student.view'))
                    <li class="nav-label">Security</li>
                    <li>
                        <a class="ai-icon {{ request()->routeIs('pickups.scanner') ? 'mm-active' : '' }}" href="{{ route('pickups.scanner') }}"><i class="la la-qrcode"></i><span class="nav-text">{{ __('pickup.scanner_title') ?? 'QR Scanner' }}</span></a>
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
                                <li><a class="{{ request()->routeIs('fee-types.*') ? 'mm-active' : '' }}" href="{{ route('fee-types.index') }}">{{ __('sidebar.fee_types.title') }}</a></li>
                            @endif
                            @if($hasModule('fee_structures') && $user->can('fee_structure.view'))
                                <li><a class="{{ request()->routeIs('fees.*') ? 'mm-active' : '' }}" href="{{ route('fees.index') }}">{{ __('sidebar.fee_structures.title') }}</a></li>
                            @endif
                            
                            @if($user->can('invoice.create'))
                                <li><a class="{{ request()->routeIs('invoices.create') ? 'mm-active' : '' }}" href="{{ route('invoices.create') }}">{{ __('sidebar.invoices.generate') }}</a></li>
                            @endif
                            <li><a class="{{ request()->routeIs('invoices.index', 'invoices.show', 'invoices.edit') ? 'mm-active' : '' }}" href="{{ route('invoices.index') }}">{{ __('sidebar.invoices.list') }}</a></li>
                            <li><a class="{{ request()->routeIs('finance.balances.*') ? 'mm-active' : '' }}" href="{{ route('finance.balances.index') }}">{{ __('sidebar.student_balances') }}</a></li>
                            <li><a class="{{ request()->routeIs('finance.reports.*') ? 'mm-active' : '' }}" href="{{ route('finance.reports.class_summary') }}">{{ __('sidebar.financial_reports') }}</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Budget & Payroll --}}
                    @if(($hasModule('budgets') && $user->can('budget.view')) || ($hasModule('payrolls') && $user->can('payroll.view')))
                    <li class="{{ request()->routeIs('budgets.*', 'payroll.*', 'salary-structures.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-wallet"></i><span class="nav-text">{{ __('sidebar.budget_payroll') }}</span></a>
                        <ul aria-expanded="false">
                            @if($hasModule('budgets'))
                                <li><a class="{{ request()->routeIs('budgets.categories') ? 'mm-active' : '' }}" href="{{ route('budgets.categories') }}">{{ __('sidebar.budget_categories') }}</a></li>
                                <li><a class="{{ request()->routeIs('budgets.index', 'budgets.create', 'budgets.edit') ? 'mm-active' : '' }}" href="{{ route('budgets.index') }}">{{ __('sidebar.budget_allocation') }}</a></li>
                                <li><a class="{{ request()->routeIs('budgets.requests*') ? 'mm-active' : '' }}" href="{{ route('budgets.requests') }}">{{ __('sidebar.fund_requests') }}</a></li>
                            @endif
                            @if($hasModule('payrolls'))
                                <li><a class="{{ request()->routeIs('salary-structures.*') ? 'mm-active' : '' }}" href="{{ route('salary-structures.index') }}">{{ __('sidebar.salary_structures') }}</a></li>
                                <li><a class="{{ request()->routeIs('payroll.*') ? 'mm-active' : '' }}" href="{{ route('payroll.index') }}">{{ __('sidebar.generate_payroll') }}</a></li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    {{-- Platform Billing --}}
                    @can('institution.view')
                    <li><a class="ai-icon {{ request()->routeIs('subscriptions.invoices*') ? 'mm-active' : '' }}" href="{{ route('subscriptions.invoices') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.billing') }}</span></a></li>
                    @endcan
                @endif

                {{-- EXAMINATIONS --}}
                @if($hasModule('exams') && $user->can('exam.view'))
                    <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                    <li class="{{ request()->routeIs('exams.*', 'exam-schedules.*', 'marks.*', 'results.*', 'reports.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.examinations') }}</span></a>
                        <ul aria-expanded="false">
                            <li><a class="{{ request()->routeIs('exams.*') ? 'mm-active' : '' }}" href="{{ route('exams.index') }}">{{ __('sidebar.exams.title') }}</a></li>
                            
                            @if($hasModule('exam_schedules'))
                                <li><a class="{{ request()->routeIs('exam-schedules.*') ? 'mm-active' : '' }}" href="{{ route('exam-schedules.manage') }}">{{ __('sidebar.exam_schedules.manage') }}</a></li>
                            @endif
                            
                            @if($hasModule('exam_marks'))
                                <li><a class="{{ request()->routeIs('marks.*') ? 'mm-active' : '' }}" href="{{ route('marks.create') }}">{{ __('sidebar.marks.title') }}</a></li>
                            @endif
                            
                            <li><a class="{{ request()->routeIs('results.*') ? 'mm-active' : '' }}" href="{{ route('results.index') }}">{{ __('sidebar.results') }}</a></li>
                            <li><a class="{{ request()->routeIs('reports.*') ? 'mm-active' : '' }}" href="{{ route('reports.index') }}">{{ __('sidebar.academic_reports') }}</a></li>
                        </ul>
                    </li>
                @endif
                
                {{-- COMMUNICATION & VOTING --}}
                @if($hasModule('communication') || $hasModule('voting'))
                    <li class="nav-label">{{ __('sidebar.communication') }}</li>
                    @if($hasModule('communication') && $user->can('notice.view'))
                        <li><a class="ai-icon {{ request()->routeIs('notices.*') ? 'mm-active' : '' }}" href="{{ route('notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.notices.title') }}</span></a></li>
                        
                        @if($user->hasRole([RoleEnum::SUPER_ADMIN->value, RoleEnum::SCHOOL_ADMIN->value, RoleEnum::HEAD_OFFICER->value]))
                        <li><a class="ai-icon {{ request()->routeIs('chatbot.*') ? 'mm-active' : '' }}" href="{{ route('chatbot.settings.index') }}"><i class="fa fa-comments"></i><span class="nav-text">{{ __('chatbot.page_title') ?? 'Chatbot' }}</span></a></li>
                        @endif
                    @endif

                    @if($hasModule('voting') && $user->can('election.view'))
                        <li><a class="ai-icon {{ request()->routeIs('elections.*') ? 'mm-active' : '' }}" href="{{ route('elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.elections.title') }}</span></a></li>
                    @endif
                @endif

                {{-- CONFIGURATION --}}
                @if($user->can('setting.manage') && !$isSuperAdmin)
                    <li class="nav-label">{{ __('sidebar.settings') }}</li>
                    <li class="{{ request()->routeIs('configuration.*', 'settings.*', 'roles.*', 'sms_templates.*') ? 'mm-active' : '' }}">
                        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false"><i class="la la-cogs"></i><span class="nav-text">{{ __('sidebar.settings') }}</span></a>
                        <ul aria-expanded="false">
                            <li><a class="{{ request()->routeIs('settings.*') ? 'mm-active' : '' }}" href="{{ route('settings.index') }}">{{ __('settings.page_title') ?? 'Settings' }}</a></li>
                            <li><a class="{{ request()->routeIs('configuration.*') ? 'mm-active' : '' }}" href="{{ route('configuration.index') }}">{{ __('configuration.page_title') ?? 'Configuration' }}</a></li>
                            <li><a class="{{ request()->routeIs('sms_templates.*') ? 'mm-active' : '' }}" href="{{ route('sms_templates.index') }}">{{ __('sidebar.sms_templates') }}</a></li>
                            <li><a class="{{ request()->routeIs('roles.*') ? 'mm-active' : '' }}" href="{{ route('roles.index') }}">{{ __('sidebar.permissions.roles') }}</a></li>
                        </ul>
                    </li>
                @endif

            @endif


            {{-- ============================================================= --}}
            {{-- PART 3: TEACHER SECTION --}}
            {{-- ============================================================= --}}
            @if($isTeacher)
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('dashboard') ? 'mm-active' : '' }}" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>
                
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('timetables.routine') ? 'mm-active' : '' }}" href="{{ route('timetables.routine') }}"><i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a></li>
                
                @if($hasModule('assignments'))
                    <li><a class="ai-icon {{ request()->routeIs('assignments.*') ? 'mm-active' : '' }}" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.class_assignments') }}</span></a></li>
                @endif
                
                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('marks.*') ? 'mm-active' : '' }}" href="{{ route('marks.create') }}"><i class="la la-edit"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a></li>
                
                @if($hasModule('exam_schedules'))
                    <li><a class="ai-icon {{ request()->routeIs('exam-schedules.*') ? 'mm-active' : '' }}" href="{{ route('exam-schedules.index') }}"><i class="la la-calendar-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.view_schedule') }}</span></a></li>
                @endif

                <li class="nav-label">{{ __('sidebar.finance') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('budgets.requests*') ? 'mm-active' : '' }}" href="{{ route('budgets.requests') }}"><i class="la la-money"></i><span class="nav-text">{{ __('sidebar.fund_requests') }}</span></a></li>
                
                {{-- Teacher Requests/Leaves --}}
                <li><a class="ai-icon {{ request()->routeIs('requests.*') ? 'mm-active' : '' }}" href="{{ route('requests.index') }}"><i class="la la-envelope"></i><span class="nav-text">{{ __('sidebar.requests') }}</span></a></li>

                <li class="nav-label">Pickup System</li>
                <li><a class="ai-icon {{ request()->routeIs('pickups.teacher') ? 'mm-active' : '' }}" href="{{ route('pickups.teacher') }}"><i class="la la-child"></i><span class="nav-text">{{ __('pickup.manager_title') ?? 'Pickup Requests' }}</span></a></li>
            @endif


            {{-- ============================================================= --}}
            {{-- PART 4: STUDENT SECTION --}}
            {{-- ============================================================= --}}
            @if($isStudent)
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('dashboard') ? 'mm-active' : '' }}" href="{{ route('dashboard') }}"><i class="la la-home"></i><span class="nav-text">{{ __('sidebar.dashboard.title') }}</span></a></li>
                
                <li class="nav-label">{{ __('sidebar.academics') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('timetables.routine') ? 'mm-active' : '' }}" href="{{ route('timetables.routine') }}"><i class="la la-calendar"></i><span class="nav-text">{{ __('sidebar.timetables.title') }}</span></a></li>
                
                @if($hasModule('assignments'))
                    <li><a class="ai-icon {{ request()->routeIs('assignments.*') ? 'mm-active' : '' }}" href="{{ route('assignments.index') }}"><i class="la la-tasks"></i><span class="nav-text">{{ __('sidebar.assignments.my_assignments') }}</span></a></li>
                @endif

                <li class="nav-label">{{ __('sidebar.examinations') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('marks.my_marks') ? 'mm-active' : '' }}" href="{{ route('marks.my_marks') }}"><i class="la la-file-text"></i><span class="nav-text">{{ __('sidebar.marks.title') }}</span></a></li>
                
                @if($hasModule('exam_schedules'))
                    <li><a class="ai-icon {{ request()->routeIs('exam-schedules.*') ? 'mm-active' : '' }}" href="{{ route('exam-schedules.index') }}"><i class="la la-calendar-o"></i><span class="nav-text">{{ __('sidebar.exam_schedules.view_schedule') }}</span></a></li>
                @endif

                <li class="nav-label">{{ __('sidebar.communication') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('student.notices.*') ? 'mm-active' : '' }}" href="{{ route('student.notices.index') }}"><i class="la la-bullhorn"></i><span class="nav-text">{{ __('sidebar.my_notices') }}</span></a></li>
                <li><a class="ai-icon {{ request()->routeIs('requests.*') ? 'mm-active' : '' }}" href="{{ route('requests.index') }}"><i class="la la-envelope"></i><span class="nav-text">{{ __('sidebar.requests') }}</span></a></li>
                
                @if($hasModule('voting'))
                    <li><a class="ai-icon {{ request()->routeIs('student.elections.*') ? 'mm-active' : '' }}" href="{{ route('student.elections.index') }}"><i class="la la-vote-yea"></i><span class="nav-text">{{ __('sidebar.my_elections') }}</span></a></li>
                @endif
            @endif

            {{-- ============================================================= --}}
            {{-- PART 5: PARENT / GUARDIAN SECTION --}}
            {{-- ============================================================= --}}
            @if($isGuardian)
                <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>
                <li><a class="ai-icon {{ request()->routeIs('pickups.parent') ? 'mm-active' : '' }}" href="{{ route('pickups.parent') }}"><i class="la la-qrcode"></i><span class="nav-text">{{ __('pickup.page_title') ?? 'Student Pickup' }}</span></a></li>
            @endif

        </ul>
    </div>
</div>