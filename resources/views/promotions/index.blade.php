@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('promotion.page_title') }}</h4>
                    <p class="mb-0">{{ __('promotion.manage_subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Promotion Filter/Criteria --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white">
                        <h4 class="card-title">{{ __('promotion.select_criteria') }}</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="GET" action="{{ route('promotions.index') }}">
                            <div class="row align-items-end">
                                {{-- FROM (Current) --}}
                                <div class="col-md-5">
                                    <h5 class="text-primary mb-3 fw-bold">{{ __('promotion.promote_from') }}</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('promotion.current_session') }}</label>
                                            <select name="from_session_id" class="form-control default-select" required>
                                                <option value="">Select Session</option>
                                                @foreach($sessions as $id => $name)
                                                    <option value="{{ $id }}" {{ (request('from_session_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('promotion.current_class') }}</label>
                                            <select name="from_class_id" class="form-control default-select" required>
                                                <option value="">Select Class</option>
                                                @foreach($classes as $id => $name)
                                                    <option value="{{ $id }}" {{ (request('from_class_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- ARROW ICON --}}
                                <div class="col-md-1 text-center d-none d-md-block mb-4">
                                    <i class="fa fa-arrow-right fa-2x text-muted mt-4"></i>
                                </div>

                                {{-- TO (Target) --}}
                                <div class="col-md-5">
                                    <h5 class="text-success mb-3 fw-bold">{{ __('promotion.promote_to') }}</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('promotion.target_session') }}</label>
                                            <select name="to_session_id" class="form-control default-select" form="promotionForm" required>
                                                <option value="">Select Session</option>
                                                @foreach($sessions as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ __('promotion.target_class') }}</label>
                                            <select name="to_class_id" class="form-control default-select" form="promotionForm" required>
                                                <option value="">Select Class</option>
                                                @foreach($classes as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Load Button --}}
                                <div class="col-md-1 mb-3">
                                    <button type="submit" class="btn btn-primary btn-block shadow-sm"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Student List --}}
        @if(request('from_session_id') && count($students) > 0)
        <form action="{{ route('promotions.store') }}" method="POST" id="promotionForm">
            @csrf
            {{-- Hidden inputs for FROM data --}}
            <input type="hidden" name="from_session_id" value="{{ request('from_session_id') }}">
            <input type="hidden" name="from_class_id" value="{{ request('from_class_id') }}">

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="border-radius: 15px;">
                        <div class="card-header border-0 pb-0 pt-4 px-4 bg-white d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0 fw-bold fs-18">{{ __('promotion.student_list') }}</h4>
                            <div class="form-check custom-checkbox">
                                <input type="checkbox" class="form-check-input" id="checkAll">
                                <label class="form-check-label fw-bold" for="checkAll">{{ __('promotion.select_all') }}</label>
                            </div>
                        </div>
                        <div class="card-body p-0 pt-3">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">#</th>
                                            <th>{{ __('promotion.student_name') }}</th>
                                            <th>{{ __('promotion.admission_no') }}</th>
                                            <th>{{ __('promotion.current_result') }}</th>
                                            <th class="text-end pe-4">{{ __('promotion.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $index => $enrollment)
                                            <tr>
                                                <td class="ps-4">{{ $index + 1 }}</td>
                                                <td class="fw-bold text-primary">{{ $enrollment->student->full_name }}</td>
                                                <td>{{ $enrollment->student->admission_number }}</td>
                                                <td>
                                                    <span class="badge badge-info light">Pending</span> 
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="form-check custom-checkbox d-inline-block">
                                                        <input type="checkbox" name="promote[]" value="{{ $enrollment->student_id }}" class="form-check-input student-check">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer border-0 bg-white text-end pb-4 pe-4">
                            <button type="submit" class="btn btn-success btn-lg shadow-sm px-5">
                                <i class="fa fa-level-up me-2"></i> {{ __('promotion.promote_students') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @elseif(request('from_session_id'))
            <div class="alert alert-warning text-center shadow-sm">{{ __('promotion.no_students_found') }}</div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        
        // Initialize Select2 manually if needed (ensure default-select class targets it)
        if ($.fn.select2) {
            $('.default-select').select2();
        }

        // Check All Logic
        $('#checkAll').on('change', function() {
            $('.student-check').prop('checked', $(this).prop('checked'));
        });

        $('#promotionForm').submit(function(e){
            e.preventDefault();
            
            // Basic Frontend Validation: Ensure TO fields are selected
            // We select elements by name attribute, checking if they exist and have value
            let toSession = $('[name="to_session_id"]').val();
            let toClass = $('[name="to_class_id"]').val();

            if(!toSession || !toClass) {
                Swal.fire({
                    icon: 'warning', 
                    title: 'Missing Information', 
                    text: 'Please select Target Session and Target Class in the "Promote To" section.'
                });
                return;
            }

            let formData = new FormData(this);
            // Append the selects outside the form manually
            formData.append('to_session_id', toSession);
            formData.append('to_class_id', toClass);

            let btn = $(this).find('button[type="submit"]');
            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

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
                        title: 'Success',
                        text: response.message
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                },
                error: function(xhr){
                    btn.prop('disabled', false).html(originalText);
                    let msg = 'Error Occurred';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', html: msg });
                }
            });
        });
    });
</script>
@endsection