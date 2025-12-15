<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">

            <li class="nav-label first">{{ __('sidebar.main_menu') }}</li>

            {{-- Dashboard --}}
            <li class="{{ isActive('dashboard') }}">
                <a class="ai-icon {{ isActive('dashboard') }}"
                   href="{{ route('dashboard') }}" aria-expanded="false">
                    <i class="la la-home"></i>
                    <span class="nav-text">{{ __('sidebar.dashboard.title') }}</span>
                </a>
            </li>

            {{-- Academic Sessions --}}
            @can('academic_session.view')
                <li class="{{ isActive(['academic-sessions.index','academic-sessions.create','academic-sessions.edit']) }}">
                    <a class="ai-icon {{ isActive(['academic-sessions.index']) }}"
                       href="{{ route('academic-sessions.index') }}" aria-expanded="false">
                        <i class="la la-calendar"></i>
                        <span class="nav-text">{{ __('sidebar.sessions.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Institutes --}}
            @can('institute.view')
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
                <li class="{{ isActive(['header-officers.index','header-officers.create','header-officers.edit']) }}">
                    <a class="ai-icon {{ isActive('header-officers.index') }}"
                       href="{{ route('header-officers.index') }}" aria-expanded="false">
                        <i class="la la-user-tie"></i>
                        <span class="nav-text">{{ __('sidebar.header_officers.title') }}</span>
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

            {{-- Students --}}
            @can('student.view')
                <li class="{{ isActive(['students.index','students.create','students.edit']) }}">
                    <a class="ai-icon {{ isActive('students.index') }}"
                       href="{{ route('students.index') }}" aria-expanded="false">
                        <i class="la la-user-graduate"></i>
                        <span class="nav-text">{{ __('sidebar.students.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Permissions & Roles --}}
            @if(auth()->user()->can('role.view') || auth()->user()->can('permission.view'))
                <li class="{{ isActive(['roles.index', 'permissions.index','roles.assign-permissions','modules.index']) }}">
                    <a class="has-arrow" href="javascript:void(0)" aria-expanded="false">
                        <i class="la la-shield"></i>
                        <span class="nav-text">{{ __('sidebar.permissions.title') }}</span>
                    </a>

                    <ul aria-expanded="false"
                        class="{{ isActive(['roles.index', 'permissions.index','roles.assign-permissions','modules.index'], 'mm-show') }}">

                        @can('role.view')
                            <li>
                                <a class="{{ isActive(['roles.index','roles.assign-permissions']) }}"
                                   href="{{ route('roles.index') }}">
                                    {{ __('sidebar.permissions.roles') }}
                                </a>
                            </li>
                        @endcan

                        {{-- Modules (Optional, usually for dev/superadmin only) --}}
                        @can('permission.view')
                            <li>
                                <a class="{{ isActive(['modules.index','permissions.index']) }}"
                                   href="{{ route('modules.index') }}">
                                    {{ __('sidebar.permissions.modules') }}
                                </a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endif

        </ul>
    </div>
</div>