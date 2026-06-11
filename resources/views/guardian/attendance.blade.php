@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0 mb-3">
            <div class="col-12 d-flex align-items-center">
                <a href="{{ route('guardian.index') }}" class="btn btn-outline-primary btn-sm me-3"><i class="fa fa-arrow-left"></i></a>
                <div>
                    <h4>{{ __('attendance.parent_report_title') }} — {{ $student->full_name }}</h4>
                    <p class="text-muted mb-0">{{ __('attendance.comparative_report') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <form method="GET" class="mb-3">
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <select name="period" class="form-control default-select w-auto d-inline-block" onchange="this.form.submit()">
                        <option value="week" @selected($period === 'week')>{{ __('attendance.this_week') }}</option>
                        <option value="month" @selected($period === 'month')>{{ __('attendance.this_month') }}</option>
                    </select>
                </form>

                @include('attendance.reports._comparison_table', ['comparisonTable' => $comparisonTable])
            </div>
        </div>
    </div>
</div>
@endsection
