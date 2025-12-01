@extends('layout.layout')

@section('content')
    <div class="content-body">

        <div class="container-fluid">

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <!-- Add Module Button -->
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModuleModal">Add Module</button>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Slug</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($modules as $module)
                                            <tr>
                                                <td>{{ $module->name }}</td>
                                                <td>{{ $module->slug }}</td>
{{--                                                <td>--}}
{{--                                                    <button class="btn btn-sm btn-warning edit-btn" data-id="{{ $module->id }}">Edit</button>--}}
{{--                                                    <form action="{{ route('modules.destroy', $module->id) }}" method="POST" class="d-inline delete-form">--}}
{{--                                                        @csrf--}}
{{--                                                        @method('DELETE')--}}
{{--                                                        <button type="button" class="btn btn-sm btn-danger delete-btn">Delete</button>--}}
{{--                                                    </form>--}}
{{--                                                </td>--}}

                                                <td>
                                                    <a href="{{ route('permissions.index',$module->id)  }}" class="btn btn-xs sharp btn-info">
                                                        <i class="fa fa-key"></i>
                                                    </a>

                                                    <button
                                                        class="btn btn-xs sharp btn-primary edit-btn"
                                                        data-id="{{ $module->id }}"
                                                        data-name="{{ $module->name }}"
{{--                                                        data-bs-toggle="modal"--}}
{{--                                                        data-bs-target="#editModuleModal"--}}
                                                    >
                                                        <i class="fa fa-pencil"></i>
                                                    </button>


                                                    <form action="{{ route('modules.destroy', $module->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button class="btn btn-xs sharp btn-danger delete-btn" >
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Module Modal -->
                <div class="modal fade" id="addModuleModal" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('modules.store') }}" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Module</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" name="name" class="form-control" placeholder="Module Name" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Add Module</button>
                        </div>
                    </form>
                </div>
            </div>

                <!-- Edit Module Modal -->
            <div class="modal fade" id="editModuleModal" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content" id="editModuleForm">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Module</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="text" name="name" class="form-control" id="editModuleName" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update Module</button>
                                </div>
                            </form>
                        </div>
                    </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function(){

            // Delete Confirmation
            $('.delete-btn').click(function(e){
                e.preventDefault();
                var form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This module will be deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if(result.isConfirmed){
                        form.submit();
                    }
                });
            });

            // Edit Module Button Click
            $('.edit-btn').click(function(){

                var moduleId = $(this).data('id');

                $.ajax({
                    url: '/modules/' + moduleId + '/edit',
                    type: 'GET',
                    success: function(data){
                        // Fill the input with module name
                        $('#editModuleName').val(data.name);

                        // Set form action dynamically
                        $('#editModuleForm').attr('action', '/modules/' + moduleId);

                        // Show the modal
                        $('#editModuleModal').modal('show');
                    },
                    error: function(xhr){
                        alert('Something went wrong!');
                    }
                });
            });

        });
    </script>

@endsection

{{--@section('scripts')--}}
    <!-- jQuery CDN (latest version 3.x) -->



{{--@endsection--}}
