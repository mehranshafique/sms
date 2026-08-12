@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('ptm.show_title') }}</h4>
                    <p class="mb-0">{{ $meeting->topic }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <a href="{{ route('ptm.index') }}" class="btn btn-light">{{ __('ptm.back') }}</a>
                <form method="POST" action="{{ route('ptm.destroy', $meeting) }}" onsubmit="return confirm('{{ __('ptm.delete_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">{{ __('ptm.delete') }}</button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-7 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_student') }}</dt>
                            <dd class="col-sm-8 fw-semibold">{{ $meeting->student?->full_name ?? '—' }}
                                @if($meeting->student?->admission_number)
                                    <span class="text-muted">({{ $meeting->student->admission_number }})</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_topic') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->topic }}</dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_date') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->preferred_date ? localized_date($meeting->preferred_date, 'd M Y') : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_status') }}</dt>
                            <dd class="col-sm-8">
                                @php
                                    $badge = match($meeting->status) {
                                        'confirmed' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge badge-{{ $badge }} light">{{ __('ptm.status_' . $meeting->status) }}</span>
                            </dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_requester') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->requester?->name ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_handler') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->handler?->name ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_handled_at') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->handled_at ? localized_date($meeting->handled_at, 'd M Y H:i') : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_notes') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->notes ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">{{ __('ptm.field_staff_notes') }}</dt>
                            <dd class="col-sm-8">{{ $meeting->staff_notes ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent border-0 pt-4">
                        <h5 class="fw-bold mb-0">{{ __('ptm.edit_status') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('ptm.update', $meeting) }}" method="POST" class="ajax-form">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('ptm.field_status') }}</label>
                                <select name="status" class="form-control default-select" required>
                                    @foreach(\App\Models\ParentMeeting::STATUSES as $st)
                                        <option value="{{ $st }}" @selected($meeting->status === $st)>{{ __('ptm.status_' . $st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('ptm.field_date') }}</label>
                                <input type="date" name="preferred_date" class="form-control" value="{{ old('preferred_date', optional($meeting->preferred_date)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('ptm.field_topic') }}</label>
                                <input type="text" name="topic" class="form-control" value="{{ old('topic', $meeting->topic) }}" maxlength="200" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('ptm.field_staff_notes') }}</label>
                                <textarea name="staff_notes" class="form-control" rows="4" placeholder="{{ __('ptm.staff_notes_placeholder') }}">{{ old('staff_notes', $meeting->staff_notes) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">{{ __('ptm.update') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
