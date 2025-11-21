@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-12">
                    <h4>Assign Permissions to Role: <strong>{{ $role->name }}</strong></h4>
                </div>
            </div>

            <form action="{{ route('roles.update-permissions', $role->id) }}" method="POST">
                @csrf
                <div class="row">
                    @foreach($modules as $module)
                        <div class="col-12 mb-3">
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
                                                        {{ $permission->name }}
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
