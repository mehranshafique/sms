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

            @can('students.view')
                <li class="{{ isActive(['students.index','students.create','students.edit']) }}">
                    <a class="ai-icon {{ isActive('students.index') }}"
                       href="{{ route('students.index') }}" aria-expanded="false">
                        <i class="la la-user-graduate"></i>
                        <span class="nav-text">{{ __('sidebar.students.title') }}</span>
                    </a>
                </li>
            @endcan

            @can('staff.view')
                <li class="{{ isActive(['staff.index','staff.create','staff.edit']) }}">
                    <a class="ai-icon {{ isActive('staff.index') }}"
                       href="{{ route('staff.index') }}" aria-expanded="false">
                        <i class="la la-globe" aria-hidden="true" title="Website"></i>

                        <span class="nav-text">{{ __('sidebar.staff.title') }}</span>
                    </a>
                </li>
            @endcan

            @can('sessions.view')
                <li class="{{ isActive(['academic-sessions.index']) }}">
                    <a class="ai-icon {{ isActive('academic-sessions.index') }}"
                       href="{{ route('academic-sessions.index') }}" aria-expanded="false">
                        <i class="la la-user-tie"></i>
                        <span class="nav-text">{{ __('sidebar.sessions.title') }}</span>
                    </a>
                </li>
            @endcan

            @can('institute.view')
                <li class="{{ isActive(['institutes.index','institutes.create','institutes.edit']) }}">
                    <a class="ai-icon {{ isActive('institutes.index') }}"
                       href="{{ route('institutes.index') }}" aria-expanded="false">
                        <i class="la la-university"></i>
                        <span class="nav-text">{{ __('sidebar.institutes.title') }}</span>
                    </a>
                </li>
            @endcan

            @can('head-officers.view')
                <li class="{{ isActive(['header-officers.index']) }}">
                    <a class="ai-icon {{ isActive('header-officers.index') }}"
                       href="{{ route('header-officers.index') }}" aria-expanded="false">
                        <i class="la la-user"></i>
                        <span class="nav-text">{{ __('sidebar.header_officers.title') }}</span>
                    </a>
                </li>
            @endcan

            {{-- Permissions --}}
            @can('permissions.view')
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
