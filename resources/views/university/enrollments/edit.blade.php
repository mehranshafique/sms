@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('university_enrollment.edit_enrollment') }}</h4>
                </div>
            </div>
        </div>

        <form action="{{ route('university.enrollments.update', $enrollment->id) }}" method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-xl-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="row">
                                {{-- Read Only Student --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.student_name') }}</label>
                                    <input type="text" class="form-control" value="{{ $enrollment->student->full_name }}" readonly disabled>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.select_program') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" class="form-control default-select" required>
                                        @foreach($programs as $id => $name)
                                            <option value="{{ $id }}" {{ $enrollment->class_section_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.roll_number') }}</label>
                                    <input type="text" name="roll_number" class="form-control" value="{{ $enrollment->roll_number }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.enrolled_at') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="enrolled_at" class="form-control" value="{{ $enrollment->enrolled_at->format('Y-m-d') }}" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('university_enrollment.status_label') }}</label>
                                    <select name="status" class="form-control default-select">
                                        <option value="active" {{ $enrollment->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="left" {{ $enrollment->status == 'left' ? 'selected' : '' }}>Left</option>
                                        <option value="graduated" {{ $enrollment->status == 'graduated' ? 'selected' : '' }}>Graduated</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-end">
                                <a href="{{ route('university.enrollments.index') }}" class="btn btn-light me-2">{{ __('university_enrollment.cancel') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('university_enrollment.update') }}</button>
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
        $('#editForm').submit(function(e) {
            e.preventDefault();
            let btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Updating...');

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
                    btn.prop('disabled', false).text('{{ __('university_enrollment.update') }}');
                    Swal.fire('Error', xhr.responseJSON.message || 'Error occurred', 'error');
                }
            });
        });
    });
</script>
@endsection