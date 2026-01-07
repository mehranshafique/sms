@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('budget.allocation_title') }}</h4>
                    <p class="mb-0">{{ $session ? $session->name : 'No Active Session' }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('budget.create')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                    <i class="fa fa-plus me-2"></i> {{ __('budget.allocate_budget') }}
                </button>
                @endcan
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="budgetTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('budget.category') }}</th>
                                        <th>{{ __('budget.allocated') }}</th>
                                        <th>{{ __('budget.spent') }}</th>
                                        <th>{{ __('budget.remaining') }}</th>
                                        <th>{{ __('budget.actions') }}</th>
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

{{-- Allocate Modal --}}
<div class="modal fade" id="allocateModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('budget.allocate_budget') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('budgets.store') }}" method="POST" id="allocateForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.select_category') }} <span class="text-danger">*</span></label>
                        <select name="budget_category_id" class="form-control default-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="allocated_amount" class="form-control" min="0" step="0.01" required placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">{{ __('budget.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('budget.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Request Funds Modal --}}
<div class="modal fade" id="requestModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('budget.request_fund') }} <span id="reqCatName" class="text-primary small"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('budgets.requests.store') }}" method="POST" id="requestForm">
                @csrf
                <input type="hidden" name="budget_id" id="reqBudgetId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.request_title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Roof Repair">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" min="1" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.description') }}</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">{{ __('budget.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('budget.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        var table = $('#budgetTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('budgets.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'category.name', name: 'category.name' },
                { data: 'allocated_amount', name: 'allocated_amount' },
                { data: 'spent_amount', name: 'spent_amount' },
                { data: 'remaining', searchable: false },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        // Allocation Submit
        $('#allocateForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#allocateModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire('Success', response.message, 'success');
                    $('#allocateForm')[0].reset();
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Failed to allocate budget', 'error');
                }
            });
        });

        // Open Request Modal
        $(document).on('click', '.request-fund-btn', function() {
            let id = $(this).data('id');
            let cat = $(this).data('cat');
            $('#reqBudgetId').val(id);
            $('#reqCatName').text('(' + cat + ')');
            $('#requestModal').modal('show');
        });

        // Request Submit
        $('#requestForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#requestModal').modal('hide');
                    Swal.fire('Success', response.message, 'success');
                    $('#requestForm')[0].reset();
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON.message || 'Error';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });
</script>
@endsection