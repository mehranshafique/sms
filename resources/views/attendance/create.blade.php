@extends('layout.layout')

@section('styles')
@include('attendance.partials.terminal_theme')
<style>
    .att-roster-row { grid-template-columns: 48px minmax(180px, 1.4fr) minmax(220px, 1fr); }
    .att-status-group { justify-content: flex-end; }
    .att-chip label { min-width: 48px; padding: .45rem .7rem; font-size: .78rem; }
    @media (max-width: 767px) {
        .att-roster-row { grid-template-columns: 48px 1fr; }
        .att-status-group { grid-column: 1 / -1; justify-content: flex-start; }
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">

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

        <div class="att-terminal mb-4">
            <div class="att-edge"></div>
            <div class="att-header">
                <div>
                    <div class="att-kicker">{{ __('attendance.access_control') }}</div>
                    <h4 class="mb-0 fw-bold">{{ __('attendance.mark_attendance_title') }}</h4>
                </div>
                <div class="att-live">{{ __('attendance.live_session') }}</div>
            </div>
            <div class="att-body">
                <form method="GET" action="{{ route('attendance.create') }}" id="selectionForm">
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('attendance.select_class') }} <span class="text-danger">*</span></label>
                            <select name="class_section_id" id="class_section_id" class="form-control default-select" required>
                                <option value="">-- {{ __('attendance.select_class') }} --</option>
                                @foreach($classSections as $id => $name)
                                    <option value="{{ $id }}" {{ (request('class_section_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if(isset($isSubjectWise) && $isSubjectWise)
                        <div class="col-md-3">
                            <label class="form-label">{{ __('attendance.subject') }} <span class="text-danger">*</span></label>
                            <select name="subject_id" id="subject_id" class="form-control default-select" required>
                                <option value="">-- {{ __('attendance.select_subject') }} --</option>
                                @if(isset($subjects))
                                    @foreach($subjects as $id => $name)
                                        <option value="{{ $id }}" {{ (request('subject_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label">{{ __('attendance.select_date') }} <span class="text-danger">*</span></label>
                            <input type="text" name="date" class="form-control datepicker" value="{{ request('date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-scan w-100">
                                <i class="fa fa-id-card me-2"></i>{{ __('attendance.load_students') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($errors->has('msg'))
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center">
                <i class="fa fa-exclamation-triangle me-2 fs-4"></i>
                <div>{{ $errors->first('msg') }}</div>
            </div>
        @endif

        @if(count($students) > 0)

        @if(isset($isLocked) && $isLocked)
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa fa-lock me-2 fs-4"></i>
                <div>
                    <strong>{{ __('attendance.attendance_locked') }}</strong>
                    {{ $lockReason ?? __('attendance.attendance_locked_desc') }}
                </div>
            </div>
        @endif

        <form action="{{ route('attendance.store') }}" method="POST" id="attendanceForm">
            @csrf
            <input type="hidden" name="class_section_id" value="{{ request('class_section_id') }}">
            <input type="hidden" name="attendance_date" value="{{ request('date') }}">
            @if(isset($isSubjectWise) && $isSubjectWise)
                <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
            @endif

            <div class="att-terminal">
                <div class="att-edge"></div>
                <div class="att-header">
                    <div>
                        <div class="att-kicker">{{ __('attendance.student_list') }}</div>
                        <h4 class="mb-0 card-title fw-bold">{{ __('attendance.roster_terminal') }}</h4>
                        <div class="att-meta mt-1">{{ __('attendance.absent_notify_hint') }}</div>
                    </div>
                    @if(!$isLocked)
                    <div class="att-actions d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-ghost" id="markAllPresent">{{ __('attendance.mark_all_present') }}</button>
                        <button type="button" class="btn btn-sm btn-ghost text-danger" id="markAllAbsent">{{ __('attendance.mark_all_absent') }}</button>
                    </div>
                    @endif
                </div>
                <div class="att-body pt-3">
                    @foreach($students as $index => $student)
                        @php
                            $currentStatus = 'present';
                            if($isUpdate && isset($existingAttendance[$student->id])) {
                                $currentStatus = $existingAttendance[$student->id]->status;
                            }
                            $initials = strtoupper(mb_substr($student->first_name ?? 'S', 0, 1) . mb_substr($student->last_name ?? '', 0, 1));
                        @endphp
                        <div class="att-roster-row is-{{ $currentStatus }}" data-row>
                            <div>
                                @if($student->student_photo)
                                    <img src="{{ asset('storage/'.$student->student_photo) }}" alt="" class="att-avatar">
                                @else
                                    <div class="att-avatar att-avatar-fallback">{{ $initials }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="att-name">{{ $student->full_name }}</div>
                                <div class="att-meta">#{{ $index + 1 }} · {{ $student->admission_number }}</div>
                            </div>
                            <div class="att-status-group">
                                @foreach(['present' => ['P', 'att-p'], 'absent' => ['A', 'att-a'], 'late' => ['L', 'att-l'], 'excused' => ['E', 'att-e']] as $val => $conf)
                                    <div class="att-chip">
                                        <input class="status-radio" type="radio"
                                               name="attendance[{{ $student->id }}]"
                                               value="{{ $val }}"
                                               id="{{ $val }}_{{ $student->id }}"
                                               {{ $currentStatus == $val ? 'checked' : '' }}
                                               {{ $isLocked ? 'disabled' : '' }}>
                                        <label class="{{ $conf[1] }}" for="{{ $val }}_{{ $student->id }}">{{ $conf[0] }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="att-footer">
                        <div class="att-count">
                            {{ __('attendance.total_students') }}: <strong>{{ count($students) }}</strong>
                            · <span id="presentCount">0</span> P
                            · <span id="absentCount">0</span> A
                        </div>
                        <div>
                            @if(!$isLocked)
                                <button type="submit" class="btn btn-scan btn-lg px-5">
                                    <i class="fa fa-check me-2"></i>
                                    {{ $isUpdate ? __('attendance.update_attendance') : __('attendance.save_attendance') }}
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-lg px-5" disabled>
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
        if(jQuery().selectpicker) {
            $('.default-select').selectpicker('refresh');
        }

        function syncRowState($input) {
            const $row = $input.closest('[data-row]');
            $row.removeClass('is-present is-absent is-late is-excused');
            $row.addClass('is-' + $input.val());
        }

        function refreshCounts() {
            $('#presentCount').text($('input.status-radio[value="present"]:checked').length);
            $('#absentCount').text($('input.status-radio[value="absent"]:checked').length);
        }

        $(document).on('change', 'input.status-radio', function() {
            syncRowState($(this));
            refreshCounts();
        });

        $('input.status-radio:checked').each(function(){ syncRowState($(this)); });
        refreshCounts();

        $('#class_section_id').change(function() {
            let classId = $(this).val();
            let subjectSelect = $('#subject_id');

            if(subjectSelect.length > 0) {
                subjectSelect.html('<option value="">{{ __("attendance.loading") }}</option>');
                subjectSelect.val('');
                if(jQuery().selectpicker) subjectSelect.selectpicker('refresh');

                if(classId) {
                    $.ajax({
                        url: '{{ route("attendance.get_subjects") }}',
                        type: 'GET',
                        data: { class_section_id: classId },
                        success: function(response) {
                            subjectSelect.html('<option value="">-- {{ __("attendance.select_subject") }} --</option>');
                            $.each(response, function(id, name) {
                                subjectSelect.append('<option value="'+id+'">'+name+'</option>');
                            });
                            subjectSelect.val('');
                            if(jQuery().selectpicker) subjectSelect.selectpicker('refresh');
                        }
                    });
                } else {
                    subjectSelect.html('<option value="">-- {{ __("attendance.select_subject") }} --</option>');
                    subjectSelect.val('');
                    if(jQuery().selectpicker) subjectSelect.selectpicker('refresh');
                }
            }
        });

        $('#markAllPresent').click(function(){
            $('input[value="present"]').prop('checked', true).trigger('change');
        });
        $('#markAllAbsent').click(function(){
            $('input[value="absent"]').prop('checked', true).trigger('change');
        });

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
                    }).then(() => { window.location.href = response.redirect; });
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
