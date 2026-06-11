@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h4>{{ __('state_exam.heading') }}</h4>
                <p class="text-muted">{{ __('state_exam.subtitle') }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('state_exam.create_exam') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('state-exams.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.name') }}</label>
                                <input type="text" name="name" class="form-control" required placeholder="EXETAT 2025-2026">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.level') }}</label>
                                <select name="level" class="form-control" required>
                                    <option value="primary_6">{{ __('state_exam.level_primary_6') }}</option>
                                    <option value="secondary_8">{{ __('state_exam.level_secondary_8') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.session') }}</label>
                                <select name="academic_session_id" class="form-control" required>
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.exam_date') }}</label>
                                <input type="date" name="exam_date" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.centre') }}</label>
                                <input type="text" name="centre" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{{ __('state_exam.create_exam') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('state_exam.page_title') }}</h5></div>
                    <div class="card-body">
                        @forelse($exams as $exam)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                <div>
                                    <strong>{{ $exam->name }}</strong>
                                    <span class="badge bg-primary ms-2">{{ $exam->level === 'primary_6' ? __('state_exam.level_primary_6') : __('state_exam.level_secondary_8') }}</span>
                                    <div class="text-muted small">{{ $exam->academicSession->name ?? '' }} · {{ $exam->candidates->count() }} {{ __('state_exam.candidates') }}</div>
                                </div>
                                <a href="{{ route('state-exams.show', $exam) }}" class="btn btn-sm btn-outline-primary">{{ __('dashboard.view_details') }}</a>
                            </div>
                        @empty
                            <p class="text-muted mb-0">{{ __('state_exam.no_exams') }}</p>
                        @endforelse
                        {{ $exams->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
