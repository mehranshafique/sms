@extends('layout.layout')

@section('styles')
<style>
    .state-exam-hero {
        background: linear-gradient(135deg, #083366 0%, #6a73fa 100%);
        border-radius: 16px;
        color: #fff;
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
    }
    .state-exam-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="state-exam-hero">
            <h4 class="text-white mb-1">{{ __('state_exam.heading') }}</h4>
            <p class="mb-0 opacity-75">{{ __('state_exam.subtitle') }}</p>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card state-exam-card">
                    <div class="card-header border-0 pb-0"><h5 class="mb-0">{{ __('state_exam.create_exam') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('state-exams.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.name') }}</label>
                                <input type="text" name="name" class="form-control" required placeholder="EXETAT 2025-2026">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.level') }}</label>
                                <select name="level" class="form-control default-select" required>
                                    <option value="primary_6">{{ __('state_exam.level_primary_6') }}</option>
                                    <option value="secondary_8">{{ __('state_exam.level_secondary_8') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.session') }}</label>
                                <select name="academic_session_id" class="form-control default-select" required>
                                    @foreach($sessions as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.exam_date') }}</label>
                                <input type="text" name="exam_date" class="form-control datepicker-default" placeholder="YYYY-MM-DD">
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
                <div class="card state-exam-card">
                    <div class="card-header border-0 pb-0"><h5 class="mb-0">{{ __('state_exam.page_title') }}</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('state_exam.name') }}</th>
                                        <th>{{ __('state_exam.level') }}</th>
                                        <th>{{ __('state_exam.session') }}</th>
                                        <th>{{ __('state_exam.exam_date') }}</th>
                                        <th>{{ __('state_exam.candidates') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($exams as $exam)
                                        <tr>
                                            <td><strong>{{ $exam->name }}</strong><br><small class="text-muted">{{ $exam->centre }}</small></td>
                                            <td>{{ $exam->level === 'primary_6' ? __('state_exam.level_primary_6') : __('state_exam.level_secondary_8') }}</td>
                                            <td>{{ $exam->academicSession->name ?? '—' }}</td>
                                            <td>{{ $exam->exam_date?->format('d/m/Y') ?? '—' }}</td>
                                            <td><span class="badge badge-primary">{{ $exam->candidates->count() }}</span></td>
                                            <td><a href="{{ route('state-exams.show', $exam) }}" class="btn btn-sm btn-outline-primary">{{ __('state_exam.candidates') }}</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('state_exam.no_exams') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $exams->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.digitexReinitSelectPickers === 'function') {
            window.digitexReinitSelectPickers();
        }
    });
</script>
@endsection
