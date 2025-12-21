@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('marks.enter_marks') }}</h4>
                    <p class="mb-0">{{ __('marks.manage_subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <form method="GET" action="{{ route('marks.create') }}" id="filterForm">
                            <div class="row align-items-end">
                                {{-- 1. Exam --}}
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('marks.select_exam') }}</label>
                                    <select name="exam_id" class="form-control default-select" id="exam_select" onchange="this.form.submit()">
                                        <option value="">-- {{ __('marks.select_exam') }} --</option>
                                        @foreach($exams as $id => $name)
                                            <option value="{{ $id }}" {{ (request('exam_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 2. Class --}}
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('marks.select_class') }}</label>
                                    <select name="class_section_id" class="form-control default-select" id="class_select" onchange="this.form.submit()" {{ request('exam_id') ? '' : 'disabled' }}>
                                        <option value="">-- {{ __('marks.select_class') }} --</option>
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}" {{ (request('class_section_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 3. Subject --}}
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">{{ __('marks.select_subject') }}</label>
                                    <select name="subject_id" class="form-control default-select" id="subject_select" {{ request('class_section_id') ? '' : 'disabled' }}>
                                        <option value="">-- {{ __('marks.select_subject') }} --</option>
                                        @if(isset($subjects) && count($subjects) > 0)
                                            @foreach($subjects as $id => $name)
                                                <option value="{{ $id }}" {{ (request('subject_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <button type="submit" class="btn btn-primary w-100 shadow-sm">{{ __('marks.load_students') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Marks Entry Table --}}
        @if(isset($students) && count($students) > 0)
        <form action="{{ route('marks.store') }}" method="POST" id="marksForm">
            @csrf
            <input type="hidden" name="exam_id" value="{{ request('exam_id') }}">
            <input type="hidden" name="class_section_id" value="{{ request('class_section_id') }}">
            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-header border-0 pb-0 pt-4 px-4">
                            <h4 class="card-title mb-0 fw-bold fs-18">{{ __('marks.student_list') }}</h4>
                        </div>
                        <div class="card-body p-0 pt-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">#</th>
                                            <th>{{ __('marks.student_name') }}</th>
                                            <th>{{ __('marks.roll_no') }}</th>
                                            <th>{{ __('marks.marks_obtained') }}</th>
                                            <th class="text-center">{{ __('marks.is_absent') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $index => $enrollment)
                                            @php
                                                $student = $enrollment->student;
                                                $record = isset($existingMarks[$student->id]) ? $existingMarks[$student->id] : null;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">{{ $index + 1 }}</td>
                                                <td class="fw-bold text-primary">{{ $student->full_name }}</td>
                                                <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                                <td>
                                                    <input type="number" 
                                                           name="marks[{{ $student->id }}]" 
                                                           class="form-control mark-input w-50" 
                                                           value="{{ $record ? $record->marks_obtained : '' }}" 
                                                           min="0" step="0.01" 
                                                           {{ ($record && $record->is_absent) ? 'disabled' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check d-inline-block">
                                                        <input class="form-check-input absent-check" 
                                                               type="checkbox" 
                                                               name="absent[{{ $student->id }}]" 
                                                               value="1"
                                                               {{ ($record && $record->is_absent) ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer border-0 text-end pb-4 pe-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm px-5" style="min-width: 160px;">{{ __('marks.save_marks') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @elseif(request('subject_id'))
            <div class="alert alert-warning text-center">{{ __('marks.no_records_found') ?? 'No students found.' }}</div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        
        // Absent Checkbox Logic
        $('.absent-check').change(function(){
            let row = $(this).closest('tr');
            let input = row.find('.mark-input');
            if($(this).is(':checked')) {
                input.prop('disabled', true).val(0);
            } else {
                input.prop('disabled', false).val('');
            }
        });

        // Submit Logic (Fixed Button State)
        $('#marksForm').submit(function(e){
            e.preventDefault();
            
            let btn = $(this).find('button[type="submit"]');
            let originalText = btn.html(); // Capture original text
            
            // Set Loading State
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    // Explicitly restore button state
                    btn.prop('disabled', false).html(originalText);
                    
                    let msg = 'Error occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', html: msg });
                }
            });
        });
    });
</script>
@endsection