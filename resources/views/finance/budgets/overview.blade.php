@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-4">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('budget.finance_overview') }}</h4>
                    <p class="mb-0 text-muted">Real-time global financial status</p>
                </div>
            </div>
        </div>

        {{-- 1. Real-Time Global Stats --}}
        <div class="row">
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-primary">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-users text-white fs-24"></i></span>
                            <div class="media-body text-white text-end">
                                <p class="mb-1">Active Students</p>
                                <h3 class="text-white">{{ number_format($totalStudents) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-info">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-chart-line text-white fs-24"></i></span>
                            <div class="media-body text-white text-end">
                                <p class="mb-1">Total Expected Revenue</p>
                                <h3 class="text-white">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalExpected, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-success">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-check-circle text-white fs-24"></i></span>
                            <div class="media-body text-white text-end">
                                <p class="mb-1">Total Amount Paid</p>
                                <h3 class="text-white">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($totalPaid, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-sm-6">
                <div class="widget-stat card shadow-sm bg-danger">
                    <div class="card-body p-4">
                        <div class="media">
                            <span class="me-3"><i class="fa fa-exclamation-circle text-white fs-24"></i></span>
                            <div class="media-body text-white text-end">
                                <p class="mb-1">Remaining Debt Balance</p>
                                <h3 class="text-white">{{ \App\Enums\CurrencySymbol::default() }} {{ number_format($remainingBalance, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Breakdown per School (Visible only to HeadOff/Super Admin) --}}
        @if($isHeadOfficer && count($schoolBreakdown) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h4 class="card-title">Breakdown by Branch / School</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Branch Name</th>
                                        <th class="text-center">Enrolled Students</th>
                                        <th class="text-end">Expected Revenue</th>
                                        <th class="text-end text-success">Amount Collected</th>
                                        <th class="text-end text-danger">Outstanding Debt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schoolBreakdown as $school)
                                        <tr>
                                            <td class="fw-bold">{{ $school['name'] }}</td>
                                            <td class="text-center">{{ number_format($school['students']) }}</td>
                                            <td class="text-end">{{ number_format($school['expected'], 2) }}</td>
                                            <td class="text-end text-success fw-bold">{{ number_format($school['paid'], 2) }}</td>
                                            <td class="text-end text-danger fw-bold">{{ number_format($school['remaining'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection