@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('medical.record_for', ['name' => $student->full_name]) }}</h4>
                    <p class="mb-0">
                        {{ $student->admission_number ?? '—' }}
                        @if($student->classSection) &middot; {{ class_section_label($student->classSection) }} @endif
                    </p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <a href="{{ route('medical-records.index') }}" class="btn btn-light shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i> {{ __('medical.back_to_log') }}
                </a>
                @if($canCreateVisit)
                <a href="{{ route('medical-records.visits.create', ['student_id' => $student->id]) }}" class="btn btn-primary shadow-sm">
                    <i class="fa fa-plus me-2"></i> {{ __('medical.record_visit') }}
                </a>
                @endif
                @if($canUpdate)
                <a href="{{ route('medical-records.edit', $student->id) }}" class="btn btn-outline-primary shadow-sm">
                    <i class="fa fa-edit me-2"></i> {{ __('medical.edit_record') }}
                </a>
                @endif
            </div>
        </div>

        @if($profile->hasCriticalInfo())
        <div class="alert alert-danger">
            <h5 class="mb-2"><i class="fa fa-triangle-exclamation me-2"></i>{{ __('medical.critical_alert') }}</h5>
            @if(filled($profile->allergies))
                <div><strong>{{ __('medical.allergies') }}:</strong> {{ $profile->allergies }}</div>
            @endif
            @if(filled($profile->chronic_conditions))
                <div><strong>{{ __('medical.chronic_conditions') }}:</strong> {{ $profile->chronic_conditions }}</div>
            @endif
            @if(filled($profile->current_medication))
                <div><strong>{{ __('medical.current_medication') }}:</strong> {{ $profile->current_medication }}</div>
            @endif
        </div>
        @endif

        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">{{ __('medical.essentials') }}</h4></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">{{ __('medical.blood_group') }}</span>
                            <span class="badge badge-danger fs-6">{{ $profile->blood_group ?? $student->blood_group ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">{{ __('medical.consent_first_aid') }}</span>
                            <span class="badge badge-{{ $profile->consent_first_aid ? 'success' : 'warning' }}">
                                {{ $profile->consent_first_aid ? __('medical.yes') : __('medical.no') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">{{ __('medical.information_date') }}</span>
                            <span>{{ $profile->information_date?->format('d M Y') ?? '—' }}</span>
                        </div>
                        <hr>
                        <h6 class="fw-bold">{{ __('medical.emergency_contact') }}</h6>
                        <div>{{ $emergency['name'] ?? '—' }}
                            @if(!empty($emergency['relation']))
                                <small class="text-muted">({{ $emergency['relation'] }})</small>
                            @endif
                        </div>
                        @if(!empty($emergency['phone']))
                            <a href="tel:{{ $emergency['phone'] }}" class="btn btn-sm btn-success mt-2">
                                <i class="fa fa-phone me-2"></i>{{ $emergency['phone'] }}
                            </a>
                        @else
                            <div class="text-danger small mt-1">{{ __('medical.no_emergency_contact') }}</div>
                        @endif
                        @if(filled($profile->emergency_contact_alt_phone))
                            <div class="small text-muted mt-2">{{ __('medical.alt_phone') }}: {{ $profile->emergency_contact_alt_phone }}</div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h4 class="card-title">{{ __('medical.doctor_insurance') }}</h4></div>
                    <div class="card-body">
                        <div class="mb-2"><span class="text-muted">{{ __('medical.doctor_name') }}:</span> {{ $profile->doctor_name ?? '—' }}</div>
                        <div class="mb-2"><span class="text-muted">{{ __('medical.doctor_phone') }}:</span> {{ $profile->doctor_phone ?? '—' }}</div>
                        <div class="mb-2"><span class="text-muted">{{ __('medical.insurance_provider') }}:</span> {{ $profile->insurance_provider ?? '—' }}</div>
                        <div><span class="text-muted">{{ __('medical.insurance_number') }}:</span> {{ $profile->insurance_number ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">{{ __('medical.medical_information') }}</h4></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">{{ __('medical.allergies') }}</dt>
                            <dd class="col-sm-8">{{ $profile->allergies ?: __('medical.none_reported') }}</dd>
                            <dt class="col-sm-4 text-muted">{{ __('medical.chronic_conditions') }}</dt>
                            <dd class="col-sm-8">{{ $profile->chronic_conditions ?: __('medical.none_reported') }}</dd>
                            <dt class="col-sm-4 text-muted">{{ __('medical.current_medication') }}</dt>
                            <dd class="col-sm-8">{{ $profile->current_medication ?: __('medical.none_reported') }}</dd>
                            <dt class="col-sm-4 text-muted">{{ __('medical.medical_notes') }}</dt>
                            <dd class="col-sm-8">{{ $profile->medical_notes ?: '—' }}</dd>
                        </dl>
                        @if($profile->exists && $profile->editor)
                            <hr>
                            <small class="text-muted">
                                {{ __('medical.last_updated_by', [
                                    'name' => $profile->editor->name,
                                    'date' => $profile->updated_at->format('d M Y H:i'),
                                ]) }}
                            </small>
                        @elseif(! $profile->exists)
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fa fa-info-circle me-2"></i>{{ __('medical.no_profile_yet') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('medical.visit_history') }}</h4>
                    </div>
                    <div class="card-body">
                        @if($visits->isEmpty())
                            <p class="text-muted mb-0">{{ __('medical.no_visits') }}</p>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('medical.visited_at') }}</th>
                                        <th>{{ __('medical.reason') }}</th>
                                        <th>{{ __('medical.observation') }}</th>
                                        <th>{{ __('medical.action_taken') }}</th>
                                        <th>{{ __('medical.outcome') }}</th>
                                        <th>{{ __('medical.recorded_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($visits as $visit)
                                    <tr>
                                        <td class="text-nowrap">{{ $visit->visited_at->format('d M Y H:i') }}</td>
                                        <td>{{ $visit->reason }}</td>
                                        <td>{{ $visit->observation ?: '—' }}</td>
                                        <td>{{ $visit->action_taken ?: '—' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $visit->outcomeBadgeClass() }}">{{ $visit->outcomeLabel() }}</span>
                                            @if($visit->parent_informed)
                                                <i class="fa fa-comment-dots text-primary ms-1" title="{{ __('medical.parent_informed') }}"></i>
                                            @endif
                                        </td>
                                        <td>{{ $visit->recorder->name ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $visits->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
