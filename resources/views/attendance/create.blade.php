@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Header --}}
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold fs-20">{{ __('attendance.mark_attendance') }}</h4>
                    <p class="mb-0 text-muted fs-14">{{ __('attendance.select_criteria') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">{{ __('attendance.attendance_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('attendance.mark_attendance') }}</a></li>
                </ol>
            </div>
        </div>

        {{-- Selection Form --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <form method="GET" action="{{ route('attendance.create') }}">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('attendance.select_class') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" class="form-control default-select" required>
                                        <option value="">-- {{ __('attendance.select_class') }} --</option>
                                        @foreach($classSections as $id => $name)
                                            <option value="{{ $id }}" {{ (request('class_section_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('attendance.select_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="date" class="form-control datepicker" value="{{ request('date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <button type="submit" class="btn btn-primary w-100 shadow-sm">{{ __('attendance.load_students') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Student List Form --}}
        @if(count($students) > 0)
        
        {{-- Lock Alert --}}
        @if(isset($isLocked) && $isLocked)
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa fa-lock me-2 fs-4"></i>
                <div>
                    <strong>{{ __('attendance.attendance_locked') }}</strong> 
                    {{ __('attendance.attendance_locked_desc') }}
                </div>
            </div>
        @endif

        <form action="{{ route('attendance.store') }}" method="POST" id="attendanceForm">
            @csrf
            <input type="hidden" name="class_section_id" value="{{ request('class_section_id') }}">
            <input type="hidden" name="attendance_date" value="{{ request('date') }}">

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-header border-0 pb-0 pt-4 px-4 bg-white d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0 fw-bold fs-18">{{ __('attendance.student_list') }}</h4>
                            @if(!$isLocked)
                            <div>
                                <button type="button" class="btn btn-xs btn-success me-2 shadow-sm" id="markAllPresent">{{ __('attendance.mark_all_present') }}</button>
                                <button type="button" class="btn btn-xs btn-danger shadow-sm" id="markAllAbsent">{{ __('attendance.mark_all_absent') }}</button>
                            </div>
                            @endif
                        </div>
                        <div class="card-body p-0 pt-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">{{ __('attendance.table_no') }}</th>
                                            <th>{{ __('attendance.student') }}</th>
                                            <th>{{ __('attendance.roll_no') }}</th>
                                            <th class="text-center">{{ __('attendance.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $index => $student)
                                            @php
                                                $currentStatus = 'present';
                                                if($isUpdate && isset($existingAttendance[$student->id])) {
                                                    $currentStatus = $existingAttendance[$student->id]->status;
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">{{ $index + 1 }}</td>
                                                <td>
                                                    <span class="fw-bold text-primary">{{ $student->full_name }}</span>
                                                </td>
                                                <td>{{ $student->admission_number }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-3">
                                                        @foreach(['present' => ['P', 'success'], 'absent' => ['A', 'danger'], 'late' => ['L', 'warning'], 'excused' => ['E', 'info']] as $val => $conf)
                                                        <div class="form-check custom-radio">
                                                            <input class="form-check-input status-radio" type="radio" 
                                                                   name="attendance[{{ $student->id }}]" 
                                                                   value="{{ $val }}" 
                                                                   id="{{ $val }}_{{ $student->id }}" 
                                                                   {{ $currentStatus == $val ? 'checked' : '' }}
                                                                   {{ $isLocked ? 'disabled' : '' }}>
                                                            <label class="form-check-label text-{{ $conf[1] }} fw-bold" for="{{ $val }}_{{ $student->id }}">
                                                                {{ $conf[0] }}
                                                            </label>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer border-0 bg-white text-end pb-4 pe-4">
                            @if(!$isLocked)
                                <button type="submit" class="btn btn-primary btn-lg shadow-sm px-5">
                                    {{ $isUpdate ? __('attendance.update_attendance') : __('attendance.save_attendance') }}
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-lg shadow-sm px-5" disabled>
                                    <i class="fa fa-lock me-2"></i> {{ __('attendance.locked') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @elseif(request('class_section_id'))
            <div class="alert alert-warning text-center shadow-sm">{{ __('attendance.not_enrolled') }}</div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        // Mark All Helpers
        $('#markAllPresent').click(function(){
            $('input[value="present"]').prop('checked', true);
        });
        $('#markAllAbsent').click(function(){
            $('input[value="absent"]').prop('checked', true);
        });

        // Submit Logic
        $('#attendanceForm').submit(function(e){
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("attendance.success") }}',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    let msg = '{{ __("attendance.error_occurred") }}';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: '{{ __("attendance.validation_error") }}', html: msg });
                }
            });
        });
    });
</script>
@endsection