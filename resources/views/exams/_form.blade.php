<form action="{{ isset($exam) ? route('exams.update', $exam->id) : route('exams.store') }}" method="POST" id="examForm">
    @csrf
    @if(isset($exam))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('exam.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            {{-- Session --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.select_session') }} <span class="text-danger">*</span></label>
                                <select name="academic_session_id" class="form-control default-select" required>
                                    <option value="">{{ __('exam.select_session') }}</option>
                                    @if(isset($sessions) && count($sessions) > 0)
                                        @foreach($sessions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('academic_session_id', $exam->academic_session_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- Name --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $exam->name ?? '') }}" placeholder="{{ __('exam.enter_name') }}" required>
                            </div>

                            {{-- Exam Category - Flattened List to avoid Select2 Optgroup issues --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.category') }} <span class="text-danger">*</span></label>
                                <select name="category" class="form-control default-select" required>
                                    <option value="">{{ __('exam.select_category') }}</option>
                                    
                                    {{-- Periods --}}
                                    <option value="p1" {{ (old('category', $exam->category ?? '') == 'p1') ? 'selected' : '' }}>-- {{ __('exam.p1') }} --</option>
                                    <option value="p2" {{ (old('category', $exam->category ?? '') == 'p2') ? 'selected' : '' }}>-- {{ __('exam.p2') }} --</option>
                                    <option value="p3" {{ (old('category', $exam->category ?? '') == 'p3') ? 'selected' : '' }}>-- {{ __('exam.p3') }} --</option>
                                    <option value="p4" {{ (old('category', $exam->category ?? '') == 'p4') ? 'selected' : '' }}>-- {{ __('exam.p4') }} --</option>
                                    <option value="p5" {{ (old('category', $exam->category ?? '') == 'p5') ? 'selected' : '' }}>-- {{ __('exam.p5') }} --</option>
                                    <option value="p6" {{ (old('category', $exam->category ?? '') == 'p6') ? 'selected' : '' }}>-- {{ __('exam.p6') }} --</option>
                                    
                                    {{-- Primary --}}
                                    <option disabled>──────────</option>
                                    <option value="trimester_exam_1" {{ (old('category', $exam->category ?? '') == 'trimester_exam_1') ? 'selected' : '' }}>{{ __('exam.trimester_exam_1') }}</option>
                                    <option value="trimester_exam_2" {{ (old('category', $exam->category ?? '') == 'trimester_exam_2') ? 'selected' : '' }}>{{ __('exam.trimester_exam_2') }}</option>
                                    <option value="trimester_exam_3" {{ (old('category', $exam->category ?? '') == 'trimester_exam_3') ? 'selected' : '' }}>{{ __('exam.trimester_exam_3') }}</option>
                                    
                                    {{-- Secondary --}}
                                    <option disabled>──────────</option>
                                    <option value="semester_exam_1" {{ (old('category', $exam->category ?? '') == 'semester_exam_1') ? 'selected' : '' }}>{{ __('exam.semester_exam_1') }}</option>
                                    <option value="semester_exam_2" {{ (old('category', $exam->category ?? '') == 'semester_exam_2') ? 'selected' : '' }}>{{ __('exam.semester_exam_2') }}</option>
                                    
                                    {{-- University --}}
                                    <option disabled>──────────</option>
                                    <option value="university_session_1" {{ (old('category', $exam->category ?? '') == 'university_session_1') ? 'selected' : '' }}>{{ __('exam.university_session_1') }}</option>
                                    <option value="university_session_2" {{ (old('category', $exam->category ?? '') == 'university_session_2') ? 'selected' : '' }}>{{ __('exam.university_session_2') }}</option>
                                </select>
                                <small class="text-muted">Used for report card aggregation (e.g. P1 + P2 + Trimester Exam 1).</small>
                            </div>

                            {{-- Status --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.status_label') }}</label>
                                <select name="status" class="form-control default-select">
                                    <option value="scheduled" {{ (old('status', $exam->status ?? '') == 'scheduled') ? 'selected' : '' }}>{{ __('exam.scheduled') }}</option>
                                    <option value="ongoing" {{ (old('status', $exam->status ?? '') == 'ongoing') ? 'selected' : '' }}>{{ __('exam.ongoing') }}</option>
                                    <option value="completed" {{ (old('status', $exam->status ?? '') == 'completed') ? 'selected' : '' }}>{{ __('exam.completed') }}</option>
                                    <option value="published" {{ (old('status', $exam->status ?? '') == 'published') ? 'selected' : '' }}>{{ __('exam.published') }}</option>
                                </select>
                            </div>

                            {{-- Dates --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="start_date" class="form-control datepicker" value="{{ old('start_date', isset($exam) ? $exam->start_date->format('Y-m-d') : '') }}" placeholder="YYYY-MM-DD" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="end_date" class="form-control datepicker" value="{{ old('end_date', isset($exam) ? $exam->end_date->format('Y-m-d') : '') }}" placeholder="YYYY-MM-DD" required>
                            </div>

                            {{-- Description --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('exam.description') }}</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description ?? '') }}</textarea>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ isset($exam) ? __('exam.update_exam') : __('exam.save_exam') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@section('js')
{{-- Ensure SweetAlert is loaded --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Fix Z-Index issues for SweetAlert in case it's behind other modals/headers --}}
<style>
    .swal2-container {
        z-index: 99999 !important;
    }
    .swal2-actions button {
        margin: 0 5px !important;
    }
</style>

<script>
    $(document).ready(function() {
        $('#examForm').on('submit', function(e) {
            e.preventDefault();
            
            let form = $(this);
            let btn = form.find('button[type="submit"]');
            let initialBtnText = btn.text();
            let formData = new FormData(this);

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            $.ajax({
                url: form.attr('action'),
                type: 'POST', // Laravel handles PUT via _method
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    btn.attr('disabled', true).text('Processing...');
                },
                success: function(response) {
                    btn.attr('disabled', false).text(initialBtnText);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: true, // Explicitly show button
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        // Redirect regardless of button click or timer
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    });
                },
                error: function(xhr) {
                    btn.attr('disabled', false).text(initialBtnText);
                    
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            let input = form.find('[name="' + key + '"]');
                            input.addClass('is-invalid');
                            
                            let errorHtml = '<div class="invalid-feedback d-block">' + value[0] + '</div>';
                            if (input.hasClass('default-select') || input.next().hasClass('nice-select')) {
                                input.parent().append(errorHtml);
                            } else {
                                input.after(errorHtml);
                            }
                        });
                        
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validation Error',
                            text: 'Please check the form for highlighted errors.',
                            confirmButtonColor: '#d33'
                        });

                    } else {
                        let errorMsg = 'An error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg,
                            confirmButtonColor: '#d33'
                        });
                    }
                }
            });
        });
    });
</script>
@endsection