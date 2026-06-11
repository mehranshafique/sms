@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4>{{ $stateExam->name }}</h4>
                <p class="text-muted mb-0">{{ $stateExam->centre }} · {{ $stateExam->exam_date?->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('state-exams.index') }}" class="btn btn-light">{{ __('institute.cancel') }}</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row mb-4">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('state_exam.register_candidate') }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('state-exams.candidates.store', $stateExam) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('transport.student') }}</label>
                                <select name="student_id" class="form-control" required>
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
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('state_exam.candidates') }}</h5></div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
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
                                @foreach($stateExam->candidates as $c)
                                    <tr>
                                        <td>{{ $c->student->full_name ?? '—' }}</td>
                                        <td>{{ $c->candidate_number ?? '—' }}</td>
                                        <td>{{ $c->score ?? '—' }}</td>
                                        <td>{{ $c->mention ?? '—' }}</td>
                                        <td>{{ ucfirst($c->status) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('state-exams.candidates.update', [$stateExam, $c]) }}" class="d-flex gap-1">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="score" class="form-control form-control-sm" step="0.01" placeholder="%" value="{{ $c->score }}" style="width:70px">
                                                <select name="status" class="form-control form-control-sm">
                                                    @foreach(['registered','passed','failed','absent'] as $st)
                                                        <option value="{{ $st }}" @selected($c->status === $st)>{{ ucfirst($st) }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm btn-success">OK</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
