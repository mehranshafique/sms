@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('reports.page_title') }}</h4>
                    <p class="mb-0">Generate Student Bulletins & Transcripts</p>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- 1. Student Bulletin Card --}}
            <div class="col-xl-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('reports.student_bulletin') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Generate a report card (Bulletin) for a specific Period or Term.</p>
                        
                        <form action="{{ route('reports.bulletin') }}" method="GET" target="_blank" id="bulletinForm">
                            {{-- Student --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('reports.select_student') }} <span class="text-danger">*</span></label>
                                <select class="form-control default-select select2" name="student_id" required>
                                    <option value="">{{ __('reports.select_student') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Report Type --}}
                            <div class="mb-3">
                                <label class="form-label">Report Scope <span class="text-danger">*</span></label>
                                <select class="form-control default-select" name="report_scope" id="reportScope" required>
                                    <option value="">-- Select Scope --</option>
                                    <option value="period">Period Report (Single Evaluation)</option>
                                    <option value="trimester">Trimester Report (Primary - Term)</option>
                                    <option value="semester">Semester Report (Secondary/LMD - Term)</option>
                                </select>
                                <input type="hidden" name="type" id="typeInput">
                            </div>

                            {{-- Dynamic Fields --}}
                            <div class="row">
                                {{-- Period Select --}}
                                <div class="col-md-12 mb-3 d-none" id="periodGroup">
                                    <label class="form-label">Select Period <span class="text-danger">*</span></label>
                                    <select class="form-control default-select" name="period" id="periodSelect">
                                        <option value="">-- Select Period --</option>
                                        <option value="p1">Period 1</option>
                                        <option value="p2">Period 2</option>
                                        <option value="p3">Period 3</option>
                                        <option value="p4">Period 4</option>
                                        <option value="p5">Period 5</option>
                                        <option value="p6">Period 6</option>
                                    </select>
                                </div>

                                {{-- Trimester Select --}}
                                <div class="col-md-12 mb-3 d-none" id="trimesterGroup">
                                    <label class="form-label">Select Trimester <span class="text-danger">*</span></label>
                                    <select class="form-control default-select" name="trimester" id="trimesterSelect">
                                        <option value="">-- Select Trimester --</option>
                                        <option value="1">Trimester 1</option>
                                        <option value="2">Trimester 2</option>
                                        <option value="3">Trimester 3</option>
                                    </select>
                                </div>

                                {{-- Semester Select --}}
                                <div class="col-md-12 mb-3 d-none" id="semesterGroup">
                                    <label class="form-label">Select Semester <span class="text-danger">*</span></label>
                                    <select class="form-control default-select" name="semester" id="semesterSelect">
                                        <option value="">-- Select Semester --</option>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block mt-2">
                                <i class="fa fa-file-pdf-o me-2"></i> {{ __('reports.generate_bulletin') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- 2. Transcript Card --}}
            <div class="col-xl-6 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('reports.transcript') }}</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Generate a comprehensive academic history report (Cumulative).</p>
                        
                        <form action="{{ route('reports.transcript') }}" method="GET" target="_blank">
                            <div class="mb-3">
                                <label class="form-label">{{ __('reports.select_student') }}</label>
                                <select class="form-control default-select select2" name="student_id" required>
                                    <option value="">{{ __('reports.select_student') }}</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block mt-5">
                                <i class="fa fa-history me-2"></i> {{ __('reports.generate_transcript') }}
                            </button>
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
    $(document).ready(function() {
        // Initialize Select2
        if ($.fn.select2) {
            $('.select2').select2();
        }

        // Dynamic Form Handling
        $('#reportScope').on('change', function() {
            const scope = $(this).val();
            
            // Reset visibility
            $('#periodGroup, #trimesterGroup, #semesterGroup').addClass('d-none');
            $('#periodSelect, #trimesterSelect, #semesterSelect').prop('required', false).val('');
            
            // Reset Type Input
            $('#typeInput').val('term'); // Default to term

            if (scope === 'period') {
                $('#periodGroup').removeClass('d-none');
                $('#periodSelect').prop('required', true);
                $('#typeInput').val('period'); // Set type to period
            } 
            else if (scope === 'trimester') {
                $('#trimesterGroup').removeClass('d-none');
                $('#trimesterSelect').prop('required', true);
            } 
            else if (scope === 'semester') {
                $('#semesterGroup').removeClass('d-none');
                $('#semesterSelect').prop('required', true);
            }
            
            // Refresh select picker if using NiceSelect/BootstrapSelect
            if ($.fn.selectpicker) {
                $('.default-select').selectpicker('refresh');
            }
        });
    });
</script>
@endsection