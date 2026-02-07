@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('class_subject.page_title') }}</h4>
                    <p class="mb-0">{{ __('class_subject.subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Class Selector --}}
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('class-subjects.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('class_subject.select_class_label') }}</label>
                            <select name="class_section_id" class="form-control default-select" onchange="this.form.submit()">
                                <option value="">{{ __('class_subject.select_class_placeholder') }}</option>
                                @foreach($classes as $cls)
                                    <option value="{{ $cls->id }}" {{ (isset($selectedClass) && $selectedClass->id == $cls->id) ? 'selected' : '' }}>
                                        {{ $cls->gradeLevel->name }} - {{ $cls->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($selectedClass))
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('class_subject.header_title', ['class' => $selectedClass->name]) }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('class-subjects.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="class_section_id" value="{{ $selectedClass->id }}">

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50" class="text-center">{{ __('class_subject.active') }}</th>
                                    <th>{{ __('class_subject.subject_name') }}</th>
                                    <th>{{ __('lmd.ue_title') }}</th> {{-- NEW COLUMN --}}
                                    <th width="150">{{ __('class_subject.weekly_periods') }}</th>
                                    <th width="150">{{ __('class_subject.exam_weight') }}</th>
                                    <th width="250">{{ __('class_subject.assigned_teacher') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gradeSubjects as $subject)
                                    @php
                                        $alloc = $allocations->get($subject->id);
                                        $isEnabled = $alloc ? true : false;
                                        // Check if linked to UE
                                        $ueName = $subject->academicUnit ? ($subject->academicUnit->code . ' - ' . $subject->academicUnit->name) : '-';
                                    @endphp
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check custom-checkbox">
                                                <input type="checkbox" name="assignments[{{ $subject->id }}][enabled]" value="1" class="form-check-input" {{ $isEnabled ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ $subject->name }}</strong>
                                            <br><small class="text-muted">{{ $subject->code }}</small>
                                        </td>
                                        <td class="text-muted small">
                                            {{ $ueName }}
                                            @if($subject->coefficient > 0)
                                                <br><span class="badge badge-xs badge-light">Coeff: {{ $subject->coefficient }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" name="assignments[{{ $subject->id }}][weekly_periods]" class="form-control form-control-sm" value="{{ $alloc->weekly_periods ?? 0 }}" min="0">
                                        </td>
                                        <td>
                                            <input type="number" name="assignments[{{ $subject->id }}][exam_weight]" class="form-control form-control-sm" value="{{ $alloc->exam_weight ?? 100 }}" min="0" max="100">
                                        </td>
                                        <td>
                                            <select name="assignments[{{ $subject->id }}][teacher_id]" class="form-control form-control-sm default-select" data-live-search="true">
                                                <option value="">{{ __('class_subject.select_teacher') }}</option>
                                                @foreach($teachers as $id => $name)
                                                    <option value="{{ $id }}" {{ ($alloc && $alloc->teacher_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('class_subject.no_subjects_defined') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary">{{ __('class_subject.save_configuration') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection