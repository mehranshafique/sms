@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            <div class="row page-titles mx-0 py-4">
                <div class="col-sm-6 p-md-0 ">
                    <div class="d-flex align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-black">
                                {{ __('role.assign_permissions_to_role') }}: <strong>{{ $role->name }}</strong>
                            </h5>
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
                                                        {{ explode('.', $permission->name)[1] }}
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
                        <button class="btn btn-success">{{ __('role.assign_permissions') }}</button>
                        <a href="{{ route('roles.index') }}" class="btn btn-secondary">{{ __('role.back') }}</a>
                    </div>
                </div>
            </form>

        </div>
    </div>
@endsection
