@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

{{--            <div class="row mb-3">--}}
{{--                <div class="col-12">--}}
{{--                    <h4>Assign Permissions to Role: <strong>{{ $role->name }}</strong></h4>--}}
{{--                </div>--}}
{{--            </div>--}}

            <div class="row page-titles mx-0 py-4">
                <div class="col-sm-6 p-md-0 ">
                    <div class="d-flex align-items-center">
{{--                        <div class="bg-white rounded-circle p-2 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">--}}
{{--                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">--}}
{{--                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>--}}
{{--                                <polyline points="9 22 9 12 15 12 15 22"></polyline>--}}
{{--                            </svg>--}}
{{--                        </div>--}}
                        <div>
                            <h5 class="mb-0 fw-bold text-black">Assign Permissions to Role: <strong>{{ $role->name }}</strong></h5>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('roles.update-permissions', $role->id) }}" method="POST">
                @csrf
                <div class="row">
                    @foreach($modules as $module)
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <strong>{{ $module->name }}</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($module->permissions as $permission)
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission->id }}"
                                                        id="perm-{{ $permission->id }}"
                                                        {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                                    >
                                                    <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                        {{ explode('.', $permission->name)[1]; }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button class="btn btn-success">Assign Permissions</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection
