@extends('layout.layout')

@section('content')
@php
    $currency = \App\Enums\CurrencySymbol::default();
@endphp

<div class="content-body">
    <div class="container-fluid">

        <div class="row page-titles mx-0 no-print">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.component_report_title') }}</h4>
                    <p class="mb-0">{{ __('finance.component_report_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('fees.index') }}">{{ __('finance.fee_structure_title') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('finance.component_report_title') }}</a></li>
                </ol>
            </div>
        </div>

        @unless($enabled)
            <div class="alert alert-warning">
                {{ __('finance.proportional_allocation_disabled_notice') }}
            </div>
        @endunless

        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body">
                <form action="{{ route('finance.reports.components') }}" method="GET">
                    <div class="row align-items-end g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('finance.select_class') }} <small class="text-muted">({{ __('finance.optional') }})</small></label>
                            <select name="class_section_id" class="form-control default-select">
                                <option value="">{{ __('finance.all_classes') }}</option>
                                @foreach($classes as $id => $name)
                                    <option value="{{ $id }}" {{ request('class_section_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-filter me-2"></i> {{ __('finance.generate_report') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <p class="mb-1 text-white opacity-75">{{ __('finance.expected') }}</p>
                        <h3 class="text-white">{{ $currency }} {{ number_format($totals['expected'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card bg-success text-white shadow-sm">
                    <div class="card-body">
                        <p class="mb-1 text-white opacity-75">{{ __('finance.collected') }}</p>
                        <h3 class="text-white">{{ $currency }} {{ number_format($totals['collected'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="widget-stat card bg-warning text-white shadow-sm">
                    <div class="card-body">
                        <p class="mb-1 text-white opacity-75">{{ __('finance.outstanding') }}</p>
                        <h3 class="text-white">{{ $currency }} {{ number_format($totals['outstanding'], 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('finance.component_breakdown') }}</h5>
                @if($currentSession)
                    <span class="badge bg-light text-dark">{{ $currentSession->name ?? '' }}</span>
                @endif
            </div>
            <div class="card-body">
                @if(empty($rows))
                    <div class="text-center text-muted py-4">{{ __('finance.no_components_yet') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('finance.component_name') }}</th>
                                    <th>{{ __('finance.fee_name') }}</th>
                                    <th class="text-end">{{ __('finance.share') }}</th>
                                    <th class="text-end">{{ __('finance.expected') }}</th>
                                    <th class="text-end">{{ __('finance.collected') }}</th>
                                    <th class="text-end">{{ __('finance.outstanding') }}</th>
                                    <th style="width:140px">{{ __('finance.progress') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $percent = $row['expected'] > 0
                                            ? min(100, round(($row['collected'] / $row['expected']) * 100))
                                            : ($row['collected'] > 0 ? 100 : 0);
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $row['label'] }}</td>
                                        <td class="text-muted">{{ $row['fee_name'] ?? '-' }}</td>
                                        <td class="text-end">{{ $row['share'] > 0 ? number_format($row['share'], 2) . '%' : '-' }}</td>
                                        <td class="text-end">{{ $currency }} {{ number_format($row['expected'], 2) }}</td>
                                        <td class="text-end text-success">{{ $currency }} {{ number_format($row['collected'], 2) }}</td>
                                        <td class="text-end text-warning">{{ $currency }} {{ number_format($row['outstanding'], 2) }}</td>
                                        <td>
                                            <div class="progress" style="height:8px">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $percent }}%</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td colspan="3">{{ __('finance.total') }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format($totals['expected'], 2) }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format($totals['collected'], 2) }}</td>
                                    <td class="text-end">{{ $currency }} {{ number_format($totals['outstanding'], 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                @if($otherCollected > 0)
                    <div class="alert alert-info mt-3 mb-0">
                        {{ __('finance.collected_outside_components') }}:
                        <strong>{{ $currency }} {{ number_format($otherCollected, 2) }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
