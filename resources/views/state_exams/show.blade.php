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
    .state-exam-card .card-header { background: transparent; border-bottom: 1px solid #eef2f7; padding: 1.25rem 1.5rem; }
    .candidate-status-form .bootstrap-select { min-width: 120px; }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="state-exam-hero d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="text-white mb-1">{{ $stateExam->name }}</h4>
                <p class="mb-0 opacity-75">
                    {{ $stateExam->centre ?: __('state_exam.centre') }}
                    @if($stateExam->exam_date)
                        · {{ $stateExam->exam_date->format('d/m/Y') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('state-exams.index') }}" class="btn btn-light btn-sm">{{ __('institute.cancel') }}</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="card state-exam-card h-100">
                    <div class="card-header"><h5 class="mb-0">{{ __('state_exam.register_candidate') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('state-exams.candidates.store', $stateExam) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('transport.student') }}</label>
                                <select name="student_id" class="form-control default-select" data-live-search="true" required>
                                    @foreach($students as $s)
                                        <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->admission_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('state_exam.candidate_number') }}</label>
                                <input type="text" name="candidate_number" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('state_exam.register_candidate') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card state-exam-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('state_exam.candidates') }}</h5>
                        <span class="badge badge-primary">{{ $stateExam->candidates->count() }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('transport.student') }}</th>
                                    <th>{{ __('state_exam.candidate_number') }}</th>
                                    <th>{{ __('state_exam.score') }}</th>
                                    <th>{{ __('state_exam.mention') }}</th>
                                    <th>{{ __('state_exam.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stateExam->candidates as $c)
                                    <tr>
                                        <td>{{ $c->student->full_name ?? '—' }}</td>
                                        <td>{{ $c->candidate_number ?? '—' }}</td>
                                        <td>{{ $c->score ?? '—' }}</td>
                                        <td>{{ $c->mention ?? '—' }}</td>
                                        <td><span class="badge badge-light text-dark">{{ ucfirst($c->status) }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('state-exams.candidates.update', [$stateExam, $c]) }}" class="d-flex flex-wrap gap-2 candidate-status-form align-items-center">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="score" class="form-control form-control-sm" step="0.01" placeholder="%" value="{{ $c->score }}" style="width:72px">
                                                <select name="status" class="form-select form-select-sm" required>
                                                    @foreach(['registered','passed','failed','absent'] as $st)
                                                        <option value="{{ $st }}" @selected($c->status === $st)>{{ __('state_exam.status_'.$st) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success">{{ __('state_exam.save') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('state_exam.no_candidates') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
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
