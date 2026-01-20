@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('sidebar.exam_schedules.view_schedule') }}</h4>
                    <p class="mb-0">{{ __('exam_schedule.subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Teacher/Admin Filter --}}
        @if(!auth()->user()->hasRole('Student'))
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('exam-schedules.index') }}">
                            <div class="row align-items-end">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('exam_schedule.select_class') }}</label>
                                    <select name="class_section_id" class="form-control default-select" onchange="this.form.submit()">
                                        <option value="">-- {{ __('invoice.select_all') }} --</option>
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}" {{ (request('class_section_id') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Schedule Display --}}
        @if($schedules->isNotEmpty())
            @foreach($schedules as $examName => $subjects)
                @php $firstItem = $subjects->first(); @endphp
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <div>
                                    <h4 class="card-title">{{ $examName }}</h4>
                                    @if(isset($selectedClass))
                                        <span class="badge badge-info">{{ $selectedClass->name }}</span>
                                    @endif
                                </div>
                                <div>
                                    <form action="{{ route('exam-schedules.download-admit-cards') }}" method="POST" target="_blank" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="exam_id" value="{{ $firstItem->exam_id }}">
                                        <input type="hidden" name="class_section_id" value="{{ $firstItem->class_section_id }}">
                                        @if(auth()->user()->hasRole('Student'))
                                            <input type="hidden" name="student_id" value="{{ auth()->user()->student->id ?? '' }}">
                                        @endif
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <i class="fa fa-download me-2"></i> {{ __('exam_schedule.download_admit_card') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered verticle-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>{{ __('exam_schedule.date') }}</th>
                                                <th>{{ __('exam_schedule.time') }}</th>
                                                <th>{{ __('exam_schedule.subject') }}</th>
                                                <th>{{ __('exam_schedule.room') }}</th>
                                                <th>{{ __('exam_schedule.max_marks') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($subjects as $item)
                                            <tr>
                                                <td>{{ $item->exam_date->format('d M, Y (l)') }}</td>
                                                <td>{{ $item->start_time->format('h:i A') }} - {{ $item->end_time->format('h:i A') }}</td>
                                                <td><strong>{{ $item->subject->name }}</strong></td>
                                                <td>{{ $item->room_number ?? '-' }}</td>
                                                <td>{{ $item->max_marks ?? $item->subject->total_marks }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif(isset($selectedClass) || auth()->user()->hasRole('Student'))
            <div class="alert alert-warning text-center">
                {{ __('exam_schedule.no_schedules_found') }}
            </div>
        @else
            <div class="alert alert-info text-center">
                {{ __('invoice.select_class_first_msg') }}
            </div>
        @endif
    </div>
</div>
@endsection