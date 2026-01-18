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
                                {{-- NEW: Total Row Footer --}}
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end">{{ __('finance.totals') ?? 'TOTALS' }}:</td>
                                        <td id="totalAllocated">0.00</td>
                                        <td id="totalSpent">0.00</td>
                                        <td id="totalRemaining">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
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
                            <option value="">-- {{ __('budget.select_category') }} --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="allocated_amount" class="form-control" placeholder="{{ __('budget.enter_amount') }}" required min="0" step="0.01">
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

{{-- Request Fund Modal --}}
<div class="modal fade" id="requestModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('budget.request_fund') }} <span id="reqCatName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('budgets.requests.store') }}" method="POST" id="requestForm">
                @csrf
                <input type="hidden" name="budget_id" id="reqBudgetId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.request_title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" required min="1" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.request_description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
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
                { data: 'remaining', name: 'remaining', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Helper to parse currency strings to numbers
                var intVal = function (i) {
                    return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                };

                // Calculate Totals
                var totalAlloc = api.column(2).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                var totalSpent = api.column(3).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                var totalRem = api.column(4).data().reduce((a, b) => intVal(a) + intVal(b), 0);

                // Update Footer
                $('#totalAllocated').html(totalAlloc.toLocaleString(undefined, {minimumFractionDigits: 2}));
                $('#totalSpent').html(totalSpent.toLocaleString(undefined, {minimumFractionDigits: 2}));
                $('#totalRemaining').html(totalRem.toLocaleString(undefined, {minimumFractionDigits: 2}));
            }
        });

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

        $(document).on('click', '.request-fund-btn', function() {
            let id = $(this).data('id');
            let cat = $(this).data('cat');
            $('#reqBudgetId').val(id);
            $('#reqCatName').text('(' + cat + ')');
            $('#requestModal').modal('show');
        });

        $('#requestForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#requestModal').modal('hide');
                    table.ajax.reload();
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