@extends('layout.layout')

@section('styles')
<!-- Clock Picker -->
<link href="{{asset('vendor/clockpicker/css/bootstrap-clockpicker.min.css')}}" rel="stylesheet">
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('staff.mark_staff_attendance') }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('staff-attendance.create') }}" method="GET" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('staff.date') }}</label>
                                    <input type="text" name="date" class="form-control datepicker" value="{{ $date }}" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">{{ __('staff.fetch_staff') }}</button>
                                </div>
                            </div>
                        </form>

                        <form action="{{ route('staff-attendance.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered verticle-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>{{ __('staff.full_name') }}</th>
                                            <th>{{ __('staff.employee_id') }}</th>
                                            <th width="30%">{{ __('staff.status_label') }}</th>
                                            <th width="15%">{{ __('staff.check_in') }}</th>
                                            <th width="15%">{{ __('staff.check_out') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($staffMembers as $staff)
                                            @php
                                                $record = $attendance[$staff->id] ?? null;
                                                $status = $record ? $record->status : 'present'; // Default
                                                $in = $record ? ($record->check_in ? $record->check_in->format('H:i') : '') : '';
                                                $out = $record ? ($record->check_out ? $record->check_out->format('H:i') : '') : '';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $staff->full_name }}</strong>
                                                    <div class="small text-muted">{{ $staff->designation }}</div>
                                                </td>
                                                <td>{{ $staff->employee_id }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        @foreach(['present', 'absent', 'late', 'excused', 'half_day'] as $s)
                                                            <div class="form-check me-3">
                                                                <input class="form-check-input" type="radio" 
                                                                       name="attendance[{{ $staff->id }}][status]" 
                                                                       value="{{ $s }}" 
                                                                       {{ $status == $s ? 'checked' : '' }}>
                                                                <label class="form-check-label">{{ __('staff.'.$s) }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group clockpicker">
                                                        <input type="text" name="attendance[{{ $staff->id }}][check_in]" class="form-control" value="{{ $in }}" placeholder="09:00">
                                                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group clockpicker">
                                                        <input type="text" name="attendance[{{ $staff->id }}][check_out]" class="form-control" value="{{ $out }}" placeholder="17:00">
                                                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-success btn-lg">{{ __('staff.save_attendance') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<!-- Clock Picker -->
<script src="{{ asset('vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>
<script>
    $(document).ready(function() {
        if($.fn.datepicker) {
            $('.datepicker').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
        }
        if($.fn.clockpicker) {
            $('.clockpicker').clockpicker({
                donetext: 'Done',
                placement: 'top',
                autoclose: true
            });
        }
    });
</script>
@endsection