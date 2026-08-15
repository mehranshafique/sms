@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('assignment.page_title') }}</h4>
                    <p class="mb-0">{{ __('assignment.subtitle') }}</p>
                </div>
            </div>
            @if(auth()->user()->can('create', App\Models\Assignment::class) || auth()->user()->hasRole(['Super Admin', 'Head Officer', 'Teacher']))
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('assignments.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus me-2"></i> {{ __('assignment.create_new') }}
                </a>
            </div>
            @endif
        </div>

        @if(($approvalRequired ?? false) && ($canApprove ?? false) && ($pendingCount ?? 0) > 0)
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <span><i class="fa fa-clock me-2"></i> {{ __('assignment.pending_notice', ['count' => $pendingCount]) }}</span>
                <button type="button" class="btn btn-sm btn-warning" id="filterPendingBtn">{{ __('assignment.show_pending') }}</button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('assignment.status') }}</label>
                                <select id="statusFilter" class="form-control">
                                    <option value="">{{ __('assignment.all_statuses') }}</option>
                                    <option value="approved">{{ __('assignment.status_approved') }}</option>
                                    <option value="pending">{{ __('assignment.status_pending') }}</option>
                                    <option value="rejected">{{ __('assignment.status_rejected') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="assignmentsTable" class="display w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('assignment.title') }}</th>
                                        <th>{{ __('assignment.class') }}</th>
                                        <th>{{ __('assignment.subject') }}</th>
                                        <th>{{ __('assignment.deadline') }}</th>
                                        <th>{{ __('assignment.teacher') }}</th>
                                        <th>{{ __('assignment.status') }}</th>
                                        <th class="text-end">{{ __('assignment.action') }}</th>
                                    </tr>
                                </thead>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = $('#assignmentsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('assignments.index') }}',
                data: function (params) {
                    params.status = $('#statusFilter').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'title', name: 'title' },
                { data: 'class_name', name: 'classSection.name' },
                { data: 'subject.name', name: 'subject.name' },
                { data: 'deadline', name: 'deadline' },
                { data: 'teacher_name', name: 'teacher.user.name', orderable: false },
                { data: 'status_badge', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            order: [[4, 'desc']]
        });

        $('#statusFilter').on('change', function () {
            table.ajax.reload();
        });

        $('#filterPendingBtn').on('click', function () {
            $('#statusFilter').val('pending').trigger('change');
        });

        function updateStatus(url, status, reason) {
            $.ajax({
                url: url,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', status: status, rejection_reason: reason || null },
                success: function (res) {
                    table.ajax.reload(null, false);
                    if (typeof digitexNotifySuccess === 'function') {
                        digitexNotifySuccess(res.message);
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error' });
                }
            });
        }

        $(document).on('click', '.approve-assignment-btn', function () {
            const url = $(this).data('url');
            Swal.fire({
                title: @json(__('assignment.approve_confirm')),
                text: @json(__('assignment.approve_warning')),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: @json(__('assignment.approve')),
                cancelButtonText: @json(__('assignment.cancel'))
            }).then((result) => {
                if (result.isConfirmed) {
                    updateStatus(url, 'approved');
                }
            });
        });

        $(document).on('click', '.reject-assignment-btn', function () {
            const url = $(this).data('url');
            Swal.fire({
                title: @json(__('assignment.reject_confirm')),
                input: 'textarea',
                inputLabel: @json(__('assignment.rejection_reason')),
                inputPlaceholder: @json(__('assignment.rejection_reason_placeholder')),
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: @json(__('assignment.reject')),
                cancelButtonText: @json(__('assignment.cancel'))
            }).then((result) => {
                if (result.isConfirmed) {
                    updateStatus(url, 'rejected', result.value);
                }
            });
        });

        $(document).on('click', '.delete-assignment-btn', function () {
            const url = $(this).data('url');
            Swal.fire({
                title: @json(__('assignment.delete_confirm')),
                text: @json(__('assignment.delete_warning')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: @json(__('assignment.yes_delete')),
                cancelButtonText: @json(__('assignment.cancel'))
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                    success: function (res) {
                        $('#assignmentsTable').DataTable().ajax.reload(null, false);
                        if (typeof digitexNotifySuccess === 'function') {
                            digitexNotifySuccess(res.message || @json(__('assignment.success_delete')));
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Error' });
                    }
                });
            });
        });
    });
</script>
@endsection
