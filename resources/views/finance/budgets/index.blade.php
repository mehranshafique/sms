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

        {{-- GLOBAL VIEW DASHBOARD --}}
        @php
            function formatBudget($n) {
                if($n > 1000000) return number_format($n/1000000, 2) . 'M';
                if($n > 1000) return number_format($n/1000, 2) . 'k';
                return number_format($n, 2);
            }
        @endphp

        <div class="row mb-4">
            {{-- Allocated Card --}}
            <div class="col-xl-4 col-sm-6">
                <div class="card bg-primary text-white mb-0 shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 text-uppercase fs-12 font-w600">{{ __('budget.total_allocated') }}</p>
                            <h3 class="text-white mb-0 font-w600" title="{{ number_format($globalStats['allocated'], 2) }}">
                                {{ formatBudget($globalStats['allocated']) }}
                            </h3>
                        </div>
                        <div class="d-inline-block position-relative donut-chart-sale">
                            <span class="donut1" data-peity='{ "fill": ["rgba(255, 255, 255, 0.5)", "rgba(255, 255, 255, 0.2)"],   "innerRadius": 20, "radius": 25}'></span> <!-- Dummy chart or Icon -->
                            <i class="fa fa-money fa-2x text-white position-absolute top-50 start-50 translate-middle"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Spent Card --}}
            <div class="col-xl-4 col-sm-6">
                <div class="card bg-warning text-white mb-0 shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 text-uppercase fs-12 font-w600">{{ __('budget.total_spent') }}</p>
                            <h3 class="text-white mb-0 font-w600" title="{{ number_format($globalStats['spent'], 2) }}">
                                {{ formatBudget($globalStats['spent']) }}
                            </h3>
                        </div>
                        <div class="d-inline-block">
                            <i class="fa fa-credit-card-alt fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remaining Card --}}
            <div class="col-xl-4 col-sm-6">
                <div class="card bg-success text-white mb-0 shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 text-uppercase fs-12 font-w600">{{ __('budget.total_remaining') }}</p>
                            <h3 class="text-white mb-0 font-w600" title="{{ number_format($globalStats['remaining'], 2) }}">
                                {{ formatBudget($globalStats['remaining']) }}
                            </h3>
                        </div>
                        <div class="d-inline-block">
                            <i class="fa fa-balance-scale fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pb-0">
                        <h4 class="card-title text-primary">{{ __('budget.budget_periods') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="budgetTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('budget.category') }}</th>
                                        <th>{{ __('budget.period') }}</th> 
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
                    {{-- 1. Category --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.select_category') }} <span class="text-danger">*</span></label>
                        <select name="budget_category_id" class="form-control default-select" required>
                            <option value="">-- {{ __('budget.select_category') }} --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 2. Period Name --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.period_name') }}</label>
                        <input type="text" name="period_name" class="form-control" placeholder="{{ __('budget.enter_period_name') }}">
                        <small class="text-muted">e.g. "Q1 2025", "Jan-Mar Operations"</small>
                    </div>

                    {{-- 3. Date Range --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('budget.start_date') }}</label>
                            <input type="text" name="start_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('budget.end_date') }}</label>
                            <input type="text" name="end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>
                    </div>

                    {{-- 4. Amount --}}
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

{{-- Edit Budget Modal --}}
<div class="modal fade" id="editBudgetModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('budget.edit') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBudgetForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_budget_id">
                
                <div class="modal-body">
                    {{-- 1. Period Name --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.period_name') }}</label>
                        <input type="text" name="period_name" id="edit_period_name" class="form-control" placeholder="{{ __('budget.enter_period_name') }}">
                    </div>

                    {{-- 2. Date Range --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('budget.start_date') }}</label>
                            <input type="text" name="start_date" id="edit_start_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('budget.end_date') }}</label>
                            <input type="text" name="end_date" id="edit_end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>
                    </div>

                    {{-- 3. Amount --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('budget.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="allocated_amount" id="edit_allocated_amount" class="form-control" required min="0" step="0.01">
                        <small class="text-warning d-block mt-1">{{ __('budget.allocation_warning') }}</small>
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
                <h5 class="modal-title">{{ __('budget.request_fund') }} <span id="reqCatName" class="text-primary fs-14"></span></h5>
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
        // Init Datepicker
        if($.fn.datepicker) {
            $('.datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
        }

        var table = $('#budgetTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('budgets.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'category.name', name: 'category.name' },
                { data: 'period_info', name: 'period_name' }, 
                { data: 'allocated_amount', name: 'allocated_amount' },
                { data: 'spent_amount', name: 'spent_amount' },
                { data: 'remaining', name: 'remaining', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        // Allocate Submission
        $('#allocateForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#allocateModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire('Success', response.message, 'success').then(() => location.reload());
                    $('#allocateForm')[0].reset();
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                    if(xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    Swal.fire('Error', msg, 'error');
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

        // Submit Request
        $('#requestForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#requestModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire('Success', response.message, 'success').then(() => location.reload());
                    $('#requestForm')[0].reset();
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON.message || 'Error';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        // --- EDIT BUDGET LOGIC ---
        $(document).on('click', '.edit-budget-btn', function() {
            let id = $(this).data('id');
            // Route mapping: budgets/{id}/edit
            let url = "{{ url('finance/budgets') }}/" + id + "/edit"; 

            $.get(url, function(data) {
                $('#edit_budget_id').val(data.id);
                $('#edit_allocated_amount').val(data.allocated_amount);
                $('#edit_period_name').val(data.period_name);
                
                $('#edit_start_date').val(data.start_date);
                if($.fn.datepicker) $('#edit_start_date').datepicker('update', data.start_date);
                
                $('#edit_end_date').val(data.end_date);
                if($.fn.datepicker) $('#edit_end_date').datepicker('update', data.end_date);

                $('#editBudgetModal').modal('show');
            }).fail(function() {
                Swal.fire('Error', 'Could not fetch budget data', 'error');
            });
        });

        // Submit Edit
        $('#editBudgetForm').submit(function(e) {
            e.preventDefault();
            let id = $('#edit_budget_id').val();
            let url = "{{ url('finance/budgets') }}/" + id; // Maps to update PUT

            $.ajax({
                url: url,
                type: 'POST', 
                data: $(this).serialize(),
                success: function(response) {
                    $('#editBudgetModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire('Success', response.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error';
                    if(xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors)[0][0];
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });
</script>
@endsection