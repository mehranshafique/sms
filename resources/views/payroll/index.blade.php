@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Payroll Management</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Generate Payroll</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('payroll.generate') }}" method="POST" class="row align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label>Month</label>
                                <select name="month" class="form-control default-select">
                                    @for($m=1; $m<=12; $m++)
                                        <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Year</label>
                                <input type="number" name="year" class="form-control" value="{{ date('Y') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Generate</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Payroll History</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Staff</th>
                                        <th>Month</th>
                                        <th>Base Pay</th>
                                        <th>Net Salary</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payrolls as $payroll)
                                    <tr>
                                        <td>{{ $payroll->staff->full_name }}</td>
                                        <td>{{ $payroll->month_year->format('M Y') }}</td>
                                        <td>{{ number_format($payroll->basic_pay, 2) }}</td>
                                        <td>{{ number_format($payroll->net_salary, 2) }}</td>
                                        <td><span class="badge badge-{{ $payroll->status == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($payroll->status) }}</span></td>
                                        <td>
                                            <a href="{{ route('payroll.payslip', $payroll->id) }}" class="btn btn-xs btn-info" target="_blank">Payslip</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $payrolls->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection