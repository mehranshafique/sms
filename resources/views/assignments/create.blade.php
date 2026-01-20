@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('assignment.create_new') }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('assignments.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                {{-- Class Section --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('assignment.class') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" id="class_section_id" class="form-control default-select" required>
                                        <option value="">-- {{ __('assignment.select_class') }} --</option>
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Subject (Dependent) --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('assignment.subject') }} <span class="text-danger">*</span></label>
                                    <select name="subject_id" id="subject_id" class="form-control default-select" disabled required>
                                        <option value="">-- {{ __('assignment.select_subject') }} --</option>
                                    </select>
                                </div>

                                {{-- Title --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('assignment.title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" placeholder="{{ __('assignment.enter_title') }}" required>
                                </div>

                                {{-- Description --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">{{ __('assignment.description') }}</label>
                                    <textarea name="description" class="form-control" rows="4"></textarea>
                                </div>

                                {{-- Deadline --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('assignment.deadline') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="deadline" class="datepicker-default form-control" placeholder="YYYY-MM-DD" required>
                                </div>

                                {{-- Attachment --}}
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('assignment.attachment') }}</label>
                                    <input type="file" name="file" class="form-control">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">{{ __('assignment.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init Datepicker
        if (jQuery().bootstrapMaterialDatePicker) {
            jQuery('.datepicker-default').bootstrapMaterialDatePicker({
                weekStart: 0,
                time: false,
                format: 'YYYY-MM-DD',
                minDate: new Date()
            });
        }

        const classSelect = document.getElementById('class_section_id');
        const subjectSelect = document.getElementById('subject_id');

        function refreshSelect(el) {
            if (typeof $ !== 'undefined' && $(el).is('select') && $.fn.selectpicker) {
                $(el).selectpicker('refresh');
            }
        }

        classSelect.addEventListener('change', function() {
            const classId = this.value;
            subjectSelect.innerHTML = '<option value="">{{ __('assignment.loading') }}</option>';
            subjectSelect.disabled = true;
            refreshSelect(subjectSelect);

            if(!classId) {
                subjectSelect.innerHTML = '<option value="">{{ __('assignment.select_subject_placeholder') }}</option>';
                return;
            }

            fetch("{{ route('assignments.get-subjects') }}?class_section_id=" + classId)
                .then(res => res.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">{{ __('assignment.select_subject_placeholder') }}</option>';
                    data.forEach(s => {
                        subjectSelect.add(new Option(s.name, s.id));
                    });
                    subjectSelect.disabled = false;
                    refreshSelect(subjectSelect);
                })
                .catch(err => {
                    console.error(err);
                    subjectSelect.innerHTML = '<option value="">{{ __('assignment.error_loading') }}</option>';
                });
        });
    });
</script>
@endsection