@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        @php
            $statusBadge = match($candidate->status) {
                \App\Models\PreEnrollment::STATUS_ADMITTED => 'success',
                \App\Models\PreEnrollment::STATUS_FINALIZED => 'primary',
                \App\Models\PreEnrollment::STATUS_INVITED => 'info',
                \App\Models\PreEnrollment::STATUS_TEST_COMPLETED => 'warning',
                \App\Models\PreEnrollment::STATUS_NOT_ADMITTED => 'danger',
                default => 'secondary',
            };
            $steps = [
                \App\Models\PreEnrollment::STATUS_PRE_ENROLLED,
                \App\Models\PreEnrollment::STATUS_INVITED,
                \App\Models\PreEnrollment::STATUS_TEST_COMPLETED,
                \App\Models\PreEnrollment::STATUS_ADMITTED,
                \App\Models\PreEnrollment::STATUS_FINALIZED,
            ];
            $currentStep = $candidate->status === \App\Models\PreEnrollment::STATUS_NOT_ADMITTED
                ? 2
                : array_search($candidate->status, $steps, true);
            $currentStep = $currentStep === false ? 0 : (int) $currentStep;
            $isFinalized = $candidate->status === \App\Models\PreEnrollment::STATUS_FINALIZED;
        @endphp

        <div class="row page-titles mx-0">
            <div class="col-sm-7 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('pre_enrollment.detail_title') }}</h4>
                    <p class="mb-0">
                        <span class="fw-bold text-dark">{{ $candidate->fullName() }}</span>
                        · <span class="badge badge-light text-dark">{{ $candidate->temporary_id }}</span>
                        · <span class="badge badge-{{ $statusBadge }} light">{{ $candidate->statusLabel() }}</span>
                    </p>
                </div>
            </div>
            <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex flex-wrap gap-2">
                @if(! $isFinalized)
                    <a href="{{ route('pre-enrollments.edit', $candidate) }}" class="btn btn-outline-primary btn-rounded">
                        <i class="fa fa-pen me-2"></i>{{ __('pre_enrollment.edit') }}
                    </a>
                    <form method="POST" action="{{ route('pre-enrollments.destroy', $candidate) }}"
                          onsubmit="return confirm('{{ __('pre_enrollment.confirm_delete') }}');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger btn-rounded">
                            <i class="fa fa-trash me-2"></i>{{ __('pre_enrollment.delete') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('pre-enrollments.index') }}" class="btn btn-light btn-rounded">
                    <i class="fa fa-arrow-left me-2"></i>{{ __('pre_enrollment.back') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- PROGRESS --}}
        <div class="card h-auto shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">{{ __('pre_enrollment.timeline') }}</h6>
                    @if($candidate->status === \App\Models\PreEnrollment::STATUS_NOT_ADMITTED)
                        <span class="badge badge-danger light">{{ __('pre_enrollment.status_not_admitted') }}</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($steps as $i => $step)
                        @php
                            $done = $i <= $currentStep;
                            $stepColor = $candidate->status === \App\Models\PreEnrollment::STATUS_NOT_ADMITTED && $i > 2
                                ? 'light text-muted'
                                : ($done ? 'primary' : 'light text-muted');
                        @endphp
                        <span class="badge badge-{{ $stepColor }} py-2 px-3">
                            @if($done)<i class="fa fa-check me-1"></i>@endif
                            {{ __('pre_enrollment.status_'.$step) }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('pre_enrollment.summary') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0 align-middle">
                                <tbody>
                                    <tr>
                                        <td class="text-muted" style="width:45%">{{ __('pre_enrollment.candidate') }}</td>
                                        <td class="fw-bold">{{ $candidate->fullName() }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.temporary_id') }}</td>
                                        <td><span class="badge badge-light text-dark">{{ $candidate->temporary_id }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.gender') }}</td>
                                        <td>{{ $candidate->gender ? __('pre_enrollment.gender_'.strtolower($candidate->gender)) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.dob') }}</td>
                                        <td>{{ $candidate->dob ? localized_date($candidate->dob, 'd M Y') : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.place_of_birth') }}</td>
                                        <td>{{ $candidate->place_of_birth ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.parent') }}</td>
                                        <td>
                                            {{ $candidate->parent_name ?: '—' }}
                                            <div class="small text-muted">
                                                {{ $candidate->parent_phone }}
                                                @if($candidate->parent_email) · {{ $candidate->parent_email }} @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.requested_class') }}</td>
                                        <td>
                                            {{ $candidate->requestedClassSection->name ?? $candidate->requestedGradeLevel->name ?? '—' }}
                                            @if($candidate->requested_option)
                                                <div class="small text-muted">{{ $candidate->requested_option }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.session') }}</td>
                                        <td>{{ $candidate->academicSession->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('pre_enrollment.source') }}</td>
                                        <td>{{ ucfirst($candidate->source) }}</td>
                                    </tr>
                                    @if($candidate->notes)
                                        <tr>
                                            <td class="text-muted">{{ __('pre_enrollment.notes') }}</td>
                                            <td>{{ $candidate->notes }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if($candidate->test_at || $candidate->test_score !== null || $candidate->test_result)
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('pre_enrollment.record_result') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <div class="text-muted small">{{ __('pre_enrollment.test_at') }}</div>
                                <div class="fw-bold">{{ $candidate->test_at ? localized_date($candidate->test_at, 'd M Y H:i') : '—' }}</div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="text-muted small">{{ __('pre_enrollment.test_location') }}</div>
                                <div class="fw-bold">{{ $candidate->test_location ?: '—' }}</div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="text-muted small">{{ __('pre_enrollment.test_score') }}</div>
                                <div class="fw-bold">{{ $candidate->test_score !== null ? $candidate->test_score : '—' }}</div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="text-muted small">{{ __('pre_enrollment.test_result') }}</div>
                                @if($candidate->test_result)
                                    <span class="badge badge-{{ $candidate->test_result === 'pass' ? 'success' : 'danger' }} light">
                                        {{ $candidate->test_result === 'pass' ? __('pre_enrollment.pass') : __('pre_enrollment.fail') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                            @if($candidate->test_notes)
                                <div class="col-12">
                                    <div class="text-muted small">{{ __('pre_enrollment.test_notes') }}</div>
                                    <div>{{ $candidate->test_notes }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($candidate->convertedStudent)
                <div class="card h-auto shadow-sm border-0 mb-4 bg-success light">
                    <div class="card-body">
                        <div class="media align-items-center">
                            <span class="me-3 text-success"><i class="la la-graduation-cap fs-32"></i></span>
                            <div class="media-body">
                                <p class="mb-1 text-dark">{{ __('pre_enrollment.converted_student') }}</p>
                                <a href="{{ route('students.show', $candidate->convertedStudent) }}" class="fw-bold text-dark">
                                    {{ $candidate->convertedStudent->admission_number }} — {{ $candidate->convertedStudent->full_name }}
                                </a>
                                <div class="small text-dark">{{ __('pre_enrollment.keep_temp_ref') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-xl-5">
                @if(! $isFinalized)
                <div class="row">
                <div class="col-xl-12 col-md-6">
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-envelope text-info me-2"></i>{{ __('pre_enrollment.invite_test') }}</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('pre-enrollments.invite', $candidate) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.test_at') }} <span class="text-danger">*</span></label>
                                <input type="text" name="test_at" class="form-control datetimepicker" value="{{ optional($candidate->test_at)->format('Y-m-d H:i') }}" placeholder="YYYY-MM-DD HH:mm" autocomplete="off" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.test_location') }}</label>
                                <input type="text" name="test_location" class="form-control" value="{{ $candidate->test_location }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.test_notes') }}</label>
                                <textarea name="test_notes" class="form-control" rows="2">{{ $candidate->test_notes }}</textarea>
                            </div>
                            <button class="btn btn-primary btn-rounded w-100 mb-2">
                                <i class="fa fa-paper-plane me-2"></i>{{ __('pre_enrollment.send_invite') }}
                            </button>
                        </form>
                        @if($candidate->test_at)
                        <form method="POST" action="{{ route('pre-enrollments.remind', $candidate) }}">
                            @csrf
                            <button class="btn btn-outline-primary btn-rounded w-100">
                                <i class="fa fa-bell me-2"></i>{{ __('pre_enrollment.send_reminder') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                </div>

                <div class="col-xl-12 col-md-6">
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-edit text-warning me-2"></i>{{ __('pre_enrollment.record_result') }}</h4>
                    </div>
                    <div class="card-body">
                        @if($candidate->status === \App\Models\PreEnrollment::STATUS_PRE_ENROLLED && ! $candidate->test_at)
                            <p class="text-muted mb-0"><i class="fa fa-info-circle me-2"></i>{{ __('pre_enrollment.invite_first_hint') }}</p>
                        @else
                        <form method="POST" action="{{ route('pre-enrollments.result', $candidate) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.test_result') }}</label>
                                <select name="test_result" class="form-control default-select" required>
                                    <option value="pass" @selected($candidate->test_result === 'pass')>{{ __('pre_enrollment.pass') }}</option>
                                    <option value="fail" @selected($candidate->test_result === 'fail')>{{ __('pre_enrollment.fail') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.test_score') }}</label>
                                <input type="number" step="0.01" name="test_score" class="form-control" value="{{ $candidate->test_score }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.test_notes') }}</label>
                                <textarea name="test_notes" class="form-control" rows="2">{{ $candidate->test_notes }}</textarea>
                            </div>
                            <button class="btn btn-warning btn-rounded w-100">
                                <i class="fa fa-save me-2"></i>{{ __('pre_enrollment.save_result') }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                </div>

                @php
                    $canFinalize = in_array($candidate->status, [
                        \App\Models\PreEnrollment::STATUS_ADMITTED,
                        \App\Models\PreEnrollment::STATUS_NOT_ADMITTED,
                        \App\Models\PreEnrollment::STATUS_TEST_COMPLETED,
                    ], true);
                    $needsForce = $candidate->status !== \App\Models\PreEnrollment::STATUS_ADMITTED;
                @endphp
                @if($canFinalize)
                <div class="col-xl-12 col-md-6">
                <div class="card h-auto shadow-sm border-0 mb-4">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title"><i class="la la-check-circle text-success me-2"></i>{{ __('pre_enrollment.finalize') }}</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('pre-enrollments.finalize', $candidate) }}">
                            @csrf
                            @if(! $candidate->gender || ! $candidate->dob)
                                <div class="alert alert-warning py-2 small">
                                    <i class="fa fa-exclamation-triangle me-1"></i>{{ __('pre_enrollment.identity_missing_hint') }}
                                </div>
                                @if(! $candidate->gender)
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('pre_enrollment.gender') }} <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-control default-select" required>
                                            <option value="">—</option>
                                            <option value="male">{{ __('pre_enrollment.gender_male') }}</option>
                                            <option value="female">{{ __('pre_enrollment.gender_female') }}</option>
                                        </select>
                                    </div>
                                @endif
                                @if(! $candidate->dob)
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('pre_enrollment.dob') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="dob" class="form-control datepicker" placeholder="YYYY-MM-DD" autocomplete="off" required>
                                    </div>
                                @endif
                            @endif
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.assign_class') }} <span class="text-danger">*</span></label>
                                <select name="class_section_id" class="form-control default-select" required>
                                    <option value="">—</option>
                                    @foreach($classes as $id => $label)
                                        <option value="{{ $id }}" @selected(($candidate->requested_class_section_id)==$id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('pre_enrollment.session') }}</label>
                                <select name="academic_session_id" class="form-control default-select">
                                    <option value="">—</option>
                                    @foreach($sessions as $id => $name)
                                        <option value="{{ $id }}" @selected(($candidate->academic_session_id)==$id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if($needsForce)
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="force" value="1" id="forceFinalize" required>
                                    <label class="form-check-label" for="forceFinalize">{{ __('pre_enrollment.force_finalize') }}</label>
                                </div>
                            @endif
                            <button class="btn btn-success btn-rounded w-100">
                                <i class="fa fa-user-plus me-2"></i>{{ __('pre_enrollment.finalize_btn') }}
                            </button>
                        </form>
                    </div>
                </div>
                </div>
                @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || !jQuery().bootstrapMaterialDatePicker) return;
    jQuery('.datetimepicker').each(function () {
        const $el = jQuery(this);
        if ($el.data('plugin_bootstrapMaterialDatePicker')) {
            $el.bootstrapMaterialDatePicker('destroy');
        }
        $el.bootstrapMaterialDatePicker({
            weekStart: 0,
            time: true,
            format: 'YYYY-MM-DD HH:mm'
        });
    });
});
</script>
@endsection
