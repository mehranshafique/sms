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
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            {{-- ADMIN: Select Student --}}
                            @if($isAdmin && isset($students))
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Request For (Student)</label>
                                    <select name="student_id" class="form-control default-select" data-live-search="true">
                                        <option value="">-- Myself (Staff Leave) --</option>
                                        @foreach($students as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Select a student to create a request on their behalf, or leave empty for your own leave request.</small>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">{{ __('requests.request_type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-control default-select" required>
                                    <option value="absence">{{ __('requests.type_absence') }}</option>
                                    <option value="late">{{ __('requests.type_late') }}</option>
                                    <option value="sick">{{ __('requests.type_sick') }}</option>
                                    <option value="early_exit">{{ __('requests.type_early_exit') }}</option>
                                    
                                    {{-- Show 'Leave' option only for Staff/Admin --}}
                                    @if($isStaff || $isAdmin)
                                        <option value="leave">Staff Leave</option>
                                    @endif
                                    
                                    <option value="other">{{ __('requests.type_other') }}</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('requests.start_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('requests.end_date') }}</label>
                                    <input type="date" name="end_date" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('requests.reason') }} <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('requests.attachment') }}</label>
                                <input type="file" name="attachment" class="form-control">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{ __('requests.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection