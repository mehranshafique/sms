@extends('layout.layout')

@section('content')
    <div class="content-body">

        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success">{{ __('modules.success_message') }}</div>
            @endif

            <!-- Add Module Button -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModuleModal">{{ __('modules.add_module_button') }}</button>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>{{ __('modules.name') }}</th>
                                        <th>{{ __('modules.slug') }}</th>
                                        <th>{{ __('modules.actions') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($modules as $module)
                                        <tr>
                                            <td>{{ $module->name }}</td>
                                            <td>{{ $module->slug }}</td>
                                            <td>
                                                <a href="{{ route('permissions.index',$module->id)  }}" class="btn btn-xs sharp btn-info">
                                                    <i class="fa fa-key"></i>
                                                </a>

                                                <button
                                                    class="btn btn-xs sharp btn-primary edit-btn"
                                                    data-id="{{ $module->id }}"
                                                >
                                                    <i class="fa fa-pencil"></i> {{ __('modules.edit_button') }}
                                                </button>

                                                <form action="{{ route('modules.destroy', $module->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button class="btn btn-xs sharp btn-danger delete-btn">
                                                        <i class="fa fa-trash"></i> {{ __('modules.delete_button') }}
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
                            <h5 class="modal-title">{{ __('modules.add_module') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" name="name" class="form-control" placeholder="{{ __('modules.module_name') }}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">{{ __('modules.add_module_button') }}</button>
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
                            <h5 class="modal-title">{{ __('modules.edit_module') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" name="name" class="form-control" id="editModuleName" placeholder="{{ __('modules.module_name') }}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">{{ __('modules.update_module_button') }}</button>
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
                    title: '{{ __("modules.delete_confirmation_title") }}',
                    text: '{{ __("modules.delete_confirmation_text") }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("modules.delete_confirm_button") }}',
                    cancelButtonText: '{{ __("modules.delete_cancel_button") }}'
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
                        $('#editModuleName').val(data.name);
                        $('#editModuleForm').attr('action', '/modules/' + moduleId);
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
