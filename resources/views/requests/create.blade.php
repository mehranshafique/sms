@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('requests.create_new') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <a href="{{ route('requests.index') }}" class="btn btn-light">
                    {{ __('requests.cancel') }}
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>{{ __('requests.validation_error') }}</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($isAdmin && empty($students))
                            <div class="alert alert-warning">
                                {{ __('requests.no_students_available') }}
                            </div>
                        @endif

                        {{-- novalidate: bootstrap-select hides native <select>, so HTML5 "required" fails silently --}}
                        <form id="requestCreateForm" action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" class="ajax-form" novalidate>
                            @csrf
                            
                            {{-- ADMIN: Select Student --}}
                            @if($isAdmin && isset($students))
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('requests.request_for') }} <span class="text-danger">*</span></label>
                                    <select name="student_id" id="student_id" class="form-control default-select @error('student_id') is-invalid @enderror" data-live-search="true">
                                        <option value="">{{ __('requests.select_student') }}</option>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}" @selected((string) old('student_id') === (string) $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('student_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block">{{ __('requests.request_for_help') }}</small>
                                    <small class="text-muted">
                                        {{ __('requests.staff_leave_hint') }}
                                        <a href="{{ route('staff-leaves.index') }}">{{ __('requests.staff_leave_link') }}</a>
                                    </small>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ __('requests.request_type') }} <span class="text-danger">*</span></label>
                                <select name="type" id="request_type" class="form-control default-select @error('type') is-invalid @enderror">
                                    @foreach(\App\Models\StudentRequest::STUDENT_TYPES as $type)
                                        <option value="{{ $type }}" @selected(old('type', 'absence') === $type)>{{ __('requests.type_' . $type) }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('requests.start_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" autocomplete="off">
                                    @error('start_date')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('requests.days') }}</label>
                                    <input type="number" name="days" id="days" class="form-control @error('days') is-invalid @enderror" min="1" max="365" placeholder="{{ __('requests.days_placeholder') }}" value="{{ old('days') }}">
                                    <small class="text-muted">{{ __('requests.days_help') }}</small>
                                    @error('days')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('requests.end_date') }}</label>
                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" autocomplete="off">
                                    @error('end_date')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('requests.reason') }} <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" rows="4">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('requests.attachment') }}</label>
                                <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
                                @error('attachment')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary" @disabled($isAdmin && empty($students))>
                                    {{ __('requests.save') }}
                                </button>
                            </div>
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
(function () {
    function syncEndDate() {
        const start = document.getElementById('start_date')?.value;
        const days = parseInt(document.getElementById('days')?.value || '', 10);
        const endInput = document.getElementById('end_date');
        if (!start || !days || days < 1 || !endInput || typeof moment === 'undefined') return;
        endInput.value = moment(start, 'YYYY-MM-DD').add(days, 'days').format('YYYY-MM-DD');
    }
    document.getElementById('days')?.addEventListener('input', syncEndDate);
    document.getElementById('start_date')?.addEventListener('change', syncEndDate);
})();
</script>
@endsection
