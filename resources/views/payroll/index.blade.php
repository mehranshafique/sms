@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('payroll.page_title') }}</h4>
                    <p class="mb-0">{{ __('payroll.manage_salaries') }}</p>
                </div>
            </div>
        </div>

        {{-- Generator Card --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title text-white mb-0"><i class="fa fa-cogs me-2"></i> {{ __('payroll.generate_payroll') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info solid fade show">
                            <i class="fa fa-info-circle me-1"></i> <strong>{{ __('payroll.note_title') }}</strong> {!! __('payroll.generate_note') !!}
                        </div>
                        <form action="{{ route('payroll.generate') }}" method="POST" class="row align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label font-w600">{{ __('payroll.select_month') }}</label>
                                <select name="month" class="form-control default-select">
                                    @for($m=1; $m<=12; $m++)
                                        <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-w600">{{ __('payroll.select_year') }}</label>
                                <input type="number" name="year" class="form-control" value="{{ date('Y') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100 shadow">
                                    <i class="fa fa-calculator me-2"></i> {{ __('payroll.generate_btn') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- History Table --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h4 class="card-title">{{ __('payroll.payroll_history') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-responsive-sm header-border">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('payroll.staff') }}</th>
                                        <th>{{ __('payroll.period') }}</th>
                                        <th>{{ __('payroll.work_units') }}</th>
                                        <th>{{ __('payroll.earnings') }}</th>
                                        <th>{{ __('payroll.deductions') }}</th>
                                        <th>{{ __('payroll.net_pay') }}</th>
                                        <th>{{ __('payroll.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payrolls as $payroll)
                                    @php
                                        // Heuristic to guess if it was hourly
                                        $isHourly = $payroll->total_days > 32; 
                                        $unitLabel = $isHourly ? __('payroll.hourly_short') : __('payroll.days_short');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="w-space-no">
                                                    {{-- Show correct name and employee ID --}}
                                                    <strong>{{ $payroll->staff->user->name ?? 'N/A' }}</strong><br>
                                                    <small class="text-muted">{{ $payroll->staff->employee_id ?? '#' . $payroll->staff->id }}</small>
                                                </span>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-light">{{ $payroll->month_year->format('M Y') }}</span></td>
                                        <td>
                                            <strong>{{ number_format($payroll->total_days, $isHourly ? 1 : 0) }}</strong> {{ $unitLabel }}
                                        </td>
                                        <td class="text-success">+ {{ number_format($payroll->basic_pay + $payroll->total_allowance, 2) }}</td>
                                        <td class="text-danger">- {{ number_format($payroll->total_deduction, 2) }}</td>
                                        <td><span class="text-primary font-w600">{{ config('app.currency_symbol') }} {{ number_format($payroll->net_salary, 2) }}</span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="fa fa-print me-1"></i> {{ __('payroll.payslip') }}
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('payroll.payslip', ['payroll' => $payroll->id, 'format' => 'a4']) }}" target="_blank">
                                                        <i class="fa fa-file-pdf-o me-2"></i> A4 Format
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('payroll.payslip', ['payroll' => $payroll->id, 'format' => 'pos80']) }}" target="_blank">
                                                        <i class="fa fa-receipt me-2"></i> POS 80mm
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('payroll.payslip', ['payroll' => $payroll->id, 'format' => 'pos58']) }}" target="_blank">
                                                        <i class="fa fa-ticket me-2"></i> POS 58mm
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted p-4">{{ __('payroll.no_records') }}</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center mt-3">
                                {{ $payrolls->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection