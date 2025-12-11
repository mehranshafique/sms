@extends('layout.layout')

@section('content')
    <div class="content-body">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                @lang('academic_session.index.add_session')
            </button>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example" class="display" style="min-width: 845px">
                                    <thead>
                                    <tr>
                                        <th>@lang('academic_session.index.name')</th>
                                        <th>@lang('academic_session.index.start_year')</th>
                                        <th>@lang('academic_session.index.end_year')</th>
                                        <th>@lang('academic_session.index.status')</th>
                                        <th>@lang('academic_session.index.current')</th>
                                        <th>@lang('academic_session.index.actions')</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($sessions as $session)
                                        <tr>
                                            <td>{{ $session->name }}</td>
                                            <td>{{ $session->start_year }}</td>
                                            <td>{{ $session->end_year }}</td>
                                            <td>{{ ucfirst($session->status) }}</td>
                                            <td>
                                                @if($session->is_current)
                                                    <span class="badge bg-success">@lang('academic_session.index.yes')</span>
                                                @else
                                                    <span class="badge bg-secondary">@lang('academic_session.index.no')</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button
                                                    class="btn btn-xs sharp btn-primary edit-btn"
                                                    data-id="{{ $session->id }}"
                                                >
                                                    <i class="fa fa-pencil"></i>
                                                </button>

                                                <form action="{{ route('academic-sessions.destroy', $session->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-xs sharp btn-danger delete-btn">
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

            <!-- ADD MODAL -->
            <div class="modal fade" id="addSessionModal" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('academic-sessions.store') }}" method="POST" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">@lang('academic_session.create.title')</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <input type="text" name="name" class="form-control mb-2"
                                   placeholder="@lang('academic_session.create.name_placeholder')" required>

                            <input type="number" name="start_year" class="form-control mb-2"
                                   placeholder="@lang('academic_session.create.start_year')" required>

                            <input type="number" name="end_year" class="form-control mb-2"
                                   placeholder="@lang('academic_session.create.end_year')" required>

                            <select name="status" class="form-control mb-2">
                                <option value="planned">@lang('academic_session.status.planned')</option>
                                <option value="active">@lang('academic_session.status.active')</option>
                                <option value="closed">@lang('academic_session.status.closed')</option>
                            </select>

                            <label class="mt-2">
                                <input type="checkbox" name="is_current" value="1">
                                @lang('academic_session.create.set_current')
                            </label>

                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">
                                @lang('academic_session.create.create_btn')
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- EDIT MODAL -->
            <div class="modal fade" id="editSessionModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" class="modal-content" id="editSessionForm">
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title">@lang('academic_session.edit.title')</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <input type="text" name="name" id="editName" class="form-control mb-2" required>

                            <input type="number" name="start_year" id="editStartYear" class="form-control mb-2" required>

                            <input type="number" name="end_year" id="editEndYear" class="form-control mb-2" required>

                            <select name="status" id="editStatus" class="form-control mb-2">
                                <option value="planned">@lang('academic_session.status.planned')</option>
                                <option value="active">@lang('academic_session.status.active')</option>
                                <option value="closed">@lang('academic_session.status.closed')</option>
                            </select>

                            <label class="mt-2">
                                <input type="checkbox" name="is_current" id="editIsCurrent" value="1">
                                @lang('academic_session.edit.set_current')
                            </label>

                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">
                                @lang('academic_session.edit.update_btn')
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {

            // Delete Confirmation
            $('.delete-btn').click(function(e){
                e.preventDefault();
                let form = $(this).closest('form');

                Swal.fire({
                    title: "@lang('academic_session.alert.delete_title')",
                    text: "@lang('academic_session.alert.delete_text')",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "@lang('academic_session.alert.delete_confirm')",
                    cancelButtonText: "@lang('academic_session.alert.cancel')"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Edit Session
            $('.edit-btn').click(function () {
                let id = $(this).data('id');

                $.ajax({
                    url: '/academic-sessions/' + id + '/edit',
                    type: 'GET',
                    success: function (data) {

                        $('#editName').val(data.name);
                        $('#editStartYear').val(data.start_year);
                        $('#editEndYear').val(data.end_year);
                        $('#editStatus').val(data.status);

                        $('#editIsCurrent').prop('checked', data.is_current == 1);

                        $('#editSessionForm').attr('action', '/academic-sessions/' + id);

                        $('#editSessionModal').modal('show');
                    }
                });
            });

        });
    </script>

@endsection
