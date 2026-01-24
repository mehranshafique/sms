@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('attendance.register_title') }}</h4>
                    <p class="mb-0">{{ __('attendance.register_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('attendance.index') }}" class="btn btn-secondary btn-rounded">{{ __('attendance.back_to_list') }}</a>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form method="GET" action="{{ route('attendance.report') }}" id="reportForm">
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('attendance.select_class') }}</label>
                                    <select name="class_section_id" class="form-control default-select" onchange="this.form.submit()">
                                        <option value="">-- {{ __('attendance.select_class') }} --</option>
                                        @foreach($classSections as $id => $name)
                                            <option value="{{ $id }}" {{ request('class_section_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('attendance.month') }}</label>
                                    <select name="month" class="form-control default-select" onchange="this.form.submit()">
                                        @for($m=1; $m<=12; $m++)
                                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date("F", mktime(0, 0, 0, $m, 1)) }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('attendance.year') }}</label>
                                    <select name="year" class="form-control default-select" onchange="this.form.submit()">
                                        @for($y=date('Y'); $y>=date('Y')-5; $y--)
                                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    {{-- UPDATED PRINT BUTTON --}}
                                    @if(request('class_section_id'))
                                        <a href="{{ route('attendance.print_report', request()->all()) }}" target="_blank" class="btn btn-primary w-100">
                                            <i class="fa fa-print me-2"></i> {{ __('attendance.print') }}
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-primary w-100" disabled>
                                            <i class="fa fa-print me-2"></i> {{ __('attendance.print') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Register Table --}}
        @if(count($students) > 0)
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0 table-striped" style="font-size: 12px;">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th class="text-start p-3" style="min-width: 200px; position: sticky; left: 0; background: #f8f9fa; z-index: 2;">{{ __('attendance.student') }}</th>
                                        @for($d=1; $d<=$daysInMonth; $d++)
                                            <th style="min-width: 30px;">{{ $d }}</th>
                                        @endfor
                                        <th class="bg-light" style="position: sticky; right: 0;">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $enrollment)
                                        @php
                                            $student = $enrollment->student;
                                            $presents = 0;
                                            $totalMarked = 0;
                                        @endphp
                                        <tr>
                                            <td class="fw-bold text-start ps-3" style="position: sticky; left: 0; background: #fff; z-index: 1;">
                                                {{ $student->full_name }}
                                            </td>
                                            @for($d=1; $d<=$daysInMonth; $d++)
                                                @php
                                                    $status = $attendanceMap[$student->id][$d] ?? '-';
                                                    $color = '';
                                                    $code = '-';
                                                    
                                                    if ($status !== '-') {
                                                        $totalMarked++;
                                                        if ($status == 'present') { $code = 'P'; $color = 'text-success fw-bold'; $presents++; }
                                                        elseif ($status == 'absent') { $code = 'A'; $color = 'text-danger fw-bold'; }
                                                        elseif ($status == 'late') { $code = 'L'; $color = 'text-warning'; }
                                                        elseif ($status == 'excused') { $code = 'E'; $color = 'text-info'; }
                                                        elseif ($status == 'half_day') { $code = 'H'; $color = 'text-primary'; }
                                                    }
                                                @endphp
                                                <td class="text-center {{ $color }}">{{ $code }}</td>
                                            @endfor
                                            @php
                                                $percentage = $totalMarked > 0 ? round(($presents / $totalMarked) * 100) : 0;
                                                $bgClass = $percentage < 50 ? 'bg-danger text-white' : ($percentage < 75 ? 'bg-warning text-dark' : 'bg-success text-white');
                                            @endphp
                                            <td class="text-center fw-bold {{ $bgClass }}" style="position: sticky; right: 0;">
                                                {{ $percentage }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif(request('class_section_id'))
            <div class="alert alert-info text-center">{{ __('attendance.no_students_found_class') }}</div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function(){
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }
    });
</script>
@endsection