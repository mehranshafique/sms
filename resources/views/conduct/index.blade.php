@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-12 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('conduct.page_title') }}</h4>
                    <p class="mb-0">{{ __('conduct.subtitle') }}</p>
                </div>
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

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('conduct.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('conduct.class_section') }}</label>
                        <select name="class_section_id" class="form-control default-select" required onchange="this.form.submit()">
                            <option value="">{{ __('conduct.select_class') }}</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((int)$classSectionId === (int)$section->id)>
                                    {{ class_section_label($section) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('conduct.scope_type') }}</label>
                        <select name="scope_type" class="form-control default-select" onchange="this.form.submit()">
                            <option value="period" @selected($scopeType === 'period')>{{ __('conduct.scope_period') }}</option>
                            <option value="trimester" @selected($scopeType === 'trimester')>{{ __('conduct.scope_trimester') }}</option>
                            <option value="semester" @selected($scopeType === 'semester')>{{ __('conduct.scope_semester') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('conduct.scope_key') }}</label>
                        <select name="scope_key" class="form-control default-select" onchange="this.form.submit()">
                            @if($scopeType === 'period')
                                @foreach(['p1','p2','p3','p4','p5','p6'] as $p)
                                    <option value="{{ $p }}" @selected($scopeKey === $p)>{{ strtoupper($p) }}</option>
                                @endforeach
                            @elseif($scopeType === 'trimester')
                                @foreach([1,2,3] as $t)
                                    <option value="{{ $t }}" @selected((string)$scopeKey === (string)$t)>{{ __('conduct.trimester_n', ['n' => $t]) }}</option>
                                @endforeach
                            @else
                                @foreach([1,2] as $s)
                                    <option value="{{ $s }}" @selected((string)$scopeKey === (string)$s)>{{ __('conduct.semester_n', ['n' => $s]) }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-rounded w-100">
                            <i class="fa fa-rotate-right me-2"></i>{{ __('conduct.load') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($classSectionId && $session && $students->isNotEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-header border-0 pb-0 d-block">
                    <h4 class="card-title mb-1">{{ __('conduct.enter_values') }}</h4>
                    <p class="text-muted small mb-0">{{ $isPrimary ? __('conduct.primary_hint') : __('conduct.secondary_hint') }}</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('conduct.store') }}" class="ajax-form">
                        @csrf
                        <input type="hidden" name="class_section_id" value="{{ $classSectionId }}">
                        <input type="hidden" name="academic_session_id" value="{{ $session->id }}">
                        <input type="hidden" name="scope_type" value="{{ $scopeType }}">
                        <input type="hidden" name="scope_key" value="{{ $scopeKey }}">

                        <div class="table-responsive">
                            <table class="table table-hover align-middle card-table">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>{{ __('conduct.student') }}</th>
                                        <th>{{ __('conduct.conduct') }}</th>
                                        @if($isPrimary)
                                            <th>{{ __('conduct.suggested') }}</th>
                                        @endif
                                        <th>{{ __('conduct.notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $i => $row)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td class="fw-bold">{{ $row['student']->full_name ?? ($row['student']->first_name.' '.$row['student']->last_name) }}</td>
                                            <td style="max-width:120px;">
                                                <input type="text" name="conducts[{{ $row['student']->id }}][conduct]" class="form-control form-control-sm" value="{{ $row['conduct'] }}" placeholder="A / B / AB / C">
                                            </td>
                                            @if($isPrimary)
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary apply-suggestion" data-value="{{ $row['suggested'] }}">
                                                        {{ $row['suggested'] ?? '—' }}
                                                    </button>
                                                </td>
                                            @endif
                                            <td>
                                                <input type="text" name="conducts[{{ $row['student']->id }}][notes]" class="form-control form-control-sm" value="{{ $row['notes'] }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary btn-rounded">
                                <i class="fa fa-save me-2"></i>{{ __('conduct.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @elseif($classSectionId && $session)
            <div class="alert alert-info">{{ __('conduct.no_students') }}</div>
        @elseif(!$session)
            <div class="alert alert-warning">{{ __('conduct.no_session') }}</div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    $('.apply-suggestion').on('click', function () {
        const val = $(this).data('value');
        if (!val) return;
        $(this).closest('tr').find('input[name$="[conduct]"]').val(val);
    });
});
</script>
@endsection
