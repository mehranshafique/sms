@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('payroll.salary_structure') }}</h4>
                    <p class="mb-0">{{ __('payroll.manage_salaries') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('payroll.salary_structure') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="salaryTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('payroll.staff_name') }}</th>
                                        <th>{{ __('payroll.designation') }}</th>
                                        <th>{{ __('payroll.base_salary') }}</th>
                                        <th>{{ __('payroll.allowances') }}</th>
                                        <th>{{ __('payroll.net_salary') }} (Est)</th>
                                        <th>{{ __('payroll.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                {{-- ADDED: Table Footer for Totals --}}
                                <tfoot>
                                    <tr>
                                        <th colspan="3" style="text-align:right; font-weight:bold;">Total:</th>
                                        <th id="total-base-salary" style="font-weight:bold;">0.00</th>
                                        <th></th>
                                        <th id="total-net-salary" style="font-weight:bold;">0.00</th>
                                        <th></th>
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
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('#salaryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('salary-structures.index') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'user.name' },
                { data: 'designation', name: 'designation' },
                { data: 'base_salary', name: 'base_salary', searchable: false },
                { data: 'allowances', name: 'allowances', orderable: false, searchable: false },
                { data: 'net_estimate', name: 'net_estimate', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            language: {
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' },
                search: "{{ __('pagination.search') ?? 'Search' }}",
                lengthMenu: "{{ __('pagination.show') ?? 'Show' }} _MENU_ {{ __('pagination.entries') ?? 'entries' }}",
                info: "Showing _START_ to _END_ of _TOTAL_ entries" // Add specific keys if needed
            },
            // ADDED: Footer Callback to calculate totals
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Helper function to reliably get integer/float data for summation
                var intVal = function (i) {
                    if (typeof i === 'string') {
                        i = i.replace(/<[^>]*>?/gm, ''); // Strip HTML tags (e.g., span badges)
                        i = i.replace(/[^\d.-]/g, '');   // Keep only numbers, decimals, and minus signs
                    }
                    var val = parseFloat(i);
                    return isNaN(val) ? 0 : val;         // Return 0 if Not a Number (e.g., "Not Set")
                };

                // Total over current page for Base Salary (Column Index 3)
                var pageTotalBase = api
                    .column(3, { page: 'current' })
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                // Total over current page for Net Salary (Column Index 5)
                var pageTotalNet = api
                    .column(5, { page: 'current' })
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                // Update footer HTML with 2 decimal places
                $(api.column(3).footer()).html(pageTotalBase.toFixed(2));
                $(api.column(5).footer()).html(pageTotalNet.toFixed(2));
            }
        });
    });
</script>
@endsection