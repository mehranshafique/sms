<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">

            <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>

            {{-- Dashboard --}}
            <li class="{{ isActive('dashboard') }}">
                <a class="ai-icon {{ isActive('dashboard') }}"
                   href="{{ route('dashboard') }}" aria-expanded="false">
                    <i class="la la-calendar"></i>
                    <span class="nav-text">{{ __('sidebar.dashboard.title') }}</span>
                </a>
            </li>

            {{-- Institutes --}}
            @can('institution.view')
                <li class="{{ isActive(['institutes.index','institutes.create','institutes.edit']) }}">
                    <a class="ai-icon {{ isActive('institutes.index') }}"
                       href="{{ route('institutes.index') }}" aria-expanded="false">
                        <i class="la la-university"></i>
                        <span class="nav-text">{{ __('sidebar.institutes.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Campuses --}}
            @can('campus.view')
                <li class="{{ isActive(['campuses.index','campuses.create','campuses.edit']) }}">
                    <a class="ai-icon {{ isActive('campuses.index') }}"
                       href="{{ route('campuses.index') }}" aria-expanded="false">
                        <i class="la la-building"></i>
                        <span class="nav-text">{{ __('sidebar.campuses.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Head Officers --}}
            @can('head_officer.view')
                <li class="{{ isActive(['header-officers.index']) }}">
                    <a class="ai-icon {{ isActive('header-officers.index') }}"
                       href="{{ route('header-officers.index') }}" aria-expanded="false">
                        <i class="la la-user-tie"></i>
                        <span class="nav-text">{{ __('sidebar.header_officers.title') }}</span>
                    </a>
                </li>
            @endcan

            <li class="nav-label">{{ __('sidebar.academics') }}</li>

            {{-- Academic Sessions --}}
            @can('academic_session.view')
                <li class="{{ isActive(['academic-sessions.index']) }}">
                    <a class="ai-icon {{ isActive('academic-sessions.index') }}"
                       href="{{ route('academic-sessions.index') }}" aria-expanded="false">
                        <i class="la la-calendar-check-o"></i>
                        <span class="nav-text">{{ __('sidebar.sessions.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Grade Levels --}}
            @can('grade_level.view')
                <li class="{{ isActive(['grade-levels.index']) }}">
                    <a class="ai-icon {{ isActive('grade-levels.index') }}"
                       href="{{ route('grade-levels.index') }}" aria-expanded="false">
                        <i class="la la-graduation-cap"></i>
                        <span class="nav-text">{{ __('sidebar.grade_levels.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Class Sections --}}
            @can('class_section.view')
                <li class="{{ isActive(['class-sections.index','class-sections.create','class-sections.edit']) }}">
                    <a class="ai-icon {{ isActive('class-sections.index') }}"
                       href="{{ route('class-sections.index') }}" aria-expanded="false">
                        <i class="la la-th-list"></i>
                        <span class="nav-text">{{ __('sidebar.class_sections.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Subjects --}}
            @can('subject.view')
                <li class="{{ isActive(['subjects.index','subjects.create','subjects.edit','subjects.show']) }}">
                    <a class="ai-icon {{ isActive('subjects.index') }}"
                       href="{{ route('subjects.index') }}" aria-expanded="false">
                        <i class="la la-book"></i>
                        <span class="nav-text">{{ __('sidebar.subjects.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Timetables --}}
            @can('timetable.view')
                <li class="{{ isActive(['timetables.index','timetables.create','timetables.edit']) }}">
                    <a class="ai-icon {{ isActive('timetables.index') }}"
                       href="{{ route('timetables.index') }}" aria-expanded="false">
                        <i class="la la-clock-o"></i>
                        <span class="nav-text">{{ __('sidebar.timetables.title') }}</span>
                    </a>
                </li>
            @endcan

            <li class="nav-label">Examinations</li>

            {{-- Exams --}}
            @can('exam.view')
                <li class="{{ isActive(['exams.index','exams.create','exams.edit','exams.show']) }}">
                    <a class="ai-icon {{ isActive('exams.index') }}"
                       href="{{ route('exams.index') }}" aria-expanded="false">
                        <i class="la la-file-text"></i>
                        <span class="nav-text">Exams</span>
                    </a>
                </li>
            @endcan

            {{-- Exam Marks --}}
            @can('exam_mark.create')
                <li class="{{ isActive(['marks.create']) }}">
                    <a class="ai-icon {{ isActive('marks.create') }}"
                       href="{{ route('marks.create') }}" aria-expanded="false">
                        <i class="la la-edit"></i>
                        <span class="nav-text">Enter Marks</span>
                    </a>
                </li>
            @endcan

            <li class="nav-label">{{ __('sidebar.people') }}</li>

            {{-- Students --}}
            @can('student.view')
                <li class="{{ isActive(['students.index','students.create','students.edit']) }}">
                    <a class="ai-icon {{ isActive('students.index') }}"
                       href="{{ route('students.index') }}" aria-expanded="false">
                        <i class="la la-users"></i>
                        <span class="nav-text">{{ __('sidebar.students.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Enrollments --}}
            @can('student_enrollment.view')
                <li class="{{ isActive(['enrollments.index','enrollments.create','enrollments.edit']) }}">
                    <a class="ai-icon {{ isActive('enrollments.index') }}"
                       href="{{ route('enrollments.index') }}" aria-expanded="false">
                        <i class="la la-id-card"></i>
                        <span class="nav-text">{{ __('sidebar.enrollments.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Attendance --}}
            @can('student_attendance.view')
                <li class="{{ isActive(['attendance.index','attendance.create']) }}">
                    <a class="ai-icon {{ isActive(['attendance.index','attendance.create']) }}"
                       href="{{ route('attendance.index') }}" aria-expanded="false">
                        <i class="la la-check-square"></i>
                        <span class="nav-text">Attendance</span>
                    </a>
                </li>
            @endcan

            {{-- Promotions --}}
            @can('student_promotion.view')
                <li class="{{ isActive(['promotions.index']) }}">
                    <a class="ai-icon {{ isActive('promotions.index') }}"
                       href="{{ route('promotions.index') }}" aria-expanded="false">
                        <i class="la la-level-up"></i>
                        <span class="nav-text">Promotions</span>
                    </a>
                </li>
            @endcan

            {{-- Staff --}}
            @can('staff.view')
                <li class="{{ isActive(['staff.index','staff.create','staff.edit']) }}">
                    <a class="ai-icon {{ isActive('staff.index') }}"
                       href="{{ route('staff.index') }}" aria-expanded="false">
                        <i class="la la-chalkboard-teacher"></i>
                        <span class="nav-text">{{ __('sidebar.staff.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Finance (New) --}}
            <li class="nav-label">Finance</li>
            @can('fee_structure.view')
                <li class="{{ isActive(['fees.index','fees.create']) }}">
                    <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-money"></i>
                        <span class="nav-text">Finance</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('fee-types.index') }}">Fee Types</a></li>
                        <li><a href="{{ route('fees.index') }}">Fee Structures</a></li>
                        <li><a href="{{ route('invoices.create') }}">Generate Invoices</a></li>
                        <li><a href="{{ route('invoices.index') }}">Invoices & Payments</a></li>
                    </ul>
                </li>
            @endcan

            <li class="nav-label">{{ __('sidebar.settings') }}</li>

            {{-- Permissions --}}
            @can('role.view')
                <li class="{{ isActive(['roles.index', 'permissions.index','roles.assign-permissions']) }}">
                    <a class="has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-shield"></i>
                        <span class="nav-text">{{ __('sidebar.permissions.title') }}</span>
                    </a>

                    <ul aria-expanded="false"
                        class="{{ isActive(['roles.index', 'permissions.index','roles.assign-permissions'], 'mm-show') }}">

                        <li>
                            <a class="{{ isActive(['roles.index','roles.assign-permissions']) }}"
                               href="{{ route('roles.index') }}">
                                {{ __('sidebar.permissions.roles') }}
                            </a>
                        </li>

                        <li>
                            <a class="{{ isActive(['modules.index','permissions.index']) }}"
                               href="{{ route('modules.index') }}">
                                {{ __('sidebar.permissions.modules') }}
                            </a>
                        </li>

                    </ul>
                </li>
            @endcan

        </ul>
    </div>
</div>