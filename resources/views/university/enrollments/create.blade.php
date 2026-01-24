@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('university_enrollment.create_new') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('university.enrollments.index') }}" class="btn btn-light">
                    {{ __('university_enrollment.cancel') }}
                </a>
            </div>
        </div>

        <form action="{{ route('university.enrollments.store') }}" method="POST" id="enrollForm">
            @csrf
            <div class="row">
                <div class="col-xl-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header border-0 pb-0">
                            <h4 class="card-title">{{ __('university_enrollment.basic_info') }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.select_program') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" class="form-control default-select" required>
                                        <option value="">-- Select Program --</option>
                                        @foreach($programs as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.select_student') }} <span class="text-danger">*</span></label>
                                    <select name="student_ids[]" class="form-control default-select select2" multiple data-live-search="true" required>
                                        {{-- 
                                            Show ALL unenrolled students. 
                                            Ideally loaded via AJAX for performance if list is huge, 
                                            but keeping it simple as per request. 
                                        --}}
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Select one or more students to enroll.</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.enrolled_at') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="enrolled_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.status_label') }}</label>
                                    <select name="status" class="form-control default-select">
                                        <option value="active">Active</option>
                                        <option value="left">Left</option>
                                        <option value="graduated">Graduated</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">{{ __('university_enrollment.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        if($.fn.select2) { $('.select2').select2(); }

        $('#enrollForm').submit(function(e) {
            e.preventDefault();
            let btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    Swal.fire('Success', res.message, 'success').then(() => {
                        window.location.href = res.redirect;
                    });
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('{{ __('university_enrollment.save') }}');
                    Swal.fire('Error', xhr.responseJSON.message || 'Error occurred', 'error');
                }
            });
        });
    });
</script>
@endsection