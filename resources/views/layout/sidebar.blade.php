<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">

            <li class="nav-label first">Main Menu</li>

            {{-- Dashboard --}}
            <li class="{{ isActive('dashboard') }}">
                <a class="ai-icon {{ isActive('dashboard') }}"
                   href="{{ route('dashboard') }}" aria-expanded="false">
                    <i class="la la-calendar"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            @can('institute.view')
            <li class="{{ isActive(['institutes.index','institutes.create','institutes.edit']) }}">
                <a class="ai-icon {{ isActive('institutes.index') }}"
                   href="{{ route('institutes.index') }}" aria-expanded="false">
                    <i class="la la-university"></i>
                    <span class="nav-text">Institutes</span>
                </a>
            </li>
            @endcan

            @can('sub-campus.view')
                <li class="{{ isActive(['header-officers.index',]) }}">
                    <a class="ai-icon {{ isActive('header-officers.index') }}"
                       href="{{ route('header-officers.index') }}" aria-expanded="false">
                        <i class="la la-user"></i>
                        <span class="nav-text">Header Officers</span>
                    </a>
                </li>
            @endcan
            {{-- Permissions Menu --}}
{{--            @can('permissions.view')--}}
            <li class="{{ isActive(['roles.index', 'permissions.index','roles.assign-permissions']) }}">
                <a class="has-arrow" href="javascript:void(0)" aria-expanded="false">
                    <i class="la la-shield"></i>
                    <span class="nav-text">Permissions</span>
                </a>
                <ul aria-expanded="false" class="{{ isActive(['roles.index', 'permissions.index','roles.assign-permissions'], 'mm-show') }}">
                    <li><a class="{{ isActive(['roles.index', 'roles.assign-permissions']) }}"
                           href="{{ route('roles.index') }}">Roles</a></li>

                    <li><a class="{{ isActive(['modules.index','permissions.index']) }}"
                           href="{{ route('modules.index') }}">Modules</a></li>

{{--                    <li><a class="{{ isActive('permissions.index') }}"--}}
{{--                           href="{{ route('permissions.index') }}">Permissions</a></li>--}}
                </ul>
            </li>
{{--            @endcan--}}
        </ul>
    </div>
</div>
