@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('parent.page_title') }}</h4>
                    <p class="mb-0">{{ __('parent.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('parents.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-2"></i> {{ __('parent.create_new') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="parentTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('parent.name') }}</th>
                                        <th>{{ __('parent.phones') }}</th>
                                        <th>{{ __('parent.email') }}</th>
                                        <th>{{ __('parent.wards') }}</th>
                                        <th class="text-end">{{ __('parent.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        var table = $('#parentTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('parents.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'father_name' }, // Search usually targets this col
                { data: 'phones', orderable: false },
                { data: 'email', name: 'guardian_email' },
                { data: 'wards', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                searchPlaceholder: "{{ __('parent.search_placeholder') }}",
                emptyTable: "{{ __('parent.no_records_found') }}"
            }
        });

        $(document).on('click', '.delete-btn', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: "{{ __('parent.confirm_delete') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: "{{ __('parent.delete') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "/parents/" + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(res) {
                            Swal.fire('Deleted!', res.message, 'success');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error';
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endsection