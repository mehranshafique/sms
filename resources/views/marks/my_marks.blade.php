@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- Header --}}
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary fw-bold">{{ __('marks.page_title') ?? 'My Marks' }}</h4>
                    <p class="mb-0 text-muted">{{ __('marks.view_your_results') ?? 'View your published exam results' }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <span class="badge badge-light text-dark fs-14 border"><i class="fa fa-user me-2 text-primary"></i> {{ $student->full_name ?? 'Student' }}</span>
            </div>
        </div>

        {{-- Error State (e.g., Not Enrolled) --}}
        @if(isset($error))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center p-4" style="border-radius: 12px;">
                        <i class="fa fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1 text-white">{{ __('marks.error') ?? 'Notice' }}</h5>
                            <p class="mb-0">{{ $error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @else

            {{-- Results Display --}}
            @if(isset($records) && $records->count() > 0)
                <div class="row">
                    @foreach($records as $examId => $examRecords)
                        @php 
                            // Extract the Exam model from the first record in the group
                            $exam = $examRecords->first()->exam; 
                        @endphp
                        <div class="col-xl-6 col-lg-12 mb-4">
                            <div class="card shadow-sm border-0 h-100" style="border-radius: 12px; overflow: hidden;">
                                <div class="card-header bg-primary text-white border-0 py-3">
                                    <h4 class="card-title text-white mb-0">
                                        <i class="fa fa-file-text-o me-2"></i> {{ $exam->name }}
                                    </h4>
                                    <span class="badge bg-white text-primary rounded-pill">{{ $exam->academicSession->name ?? '' }}</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">{{ __('marks.subject') }}</th>
                                                    <th class="text-center">{{ __('marks.marks_obtained') }}</th>
                                                    <th class="text-center pe-4">{{ __('marks.status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($examRecords as $record)
                                                <tr>
                                                    <td class="ps-4 font-w600 text-dark">{{ $record->subject->name ?? 'N/A' }}</td>
                                                    <td class="text-center">
                                                        @if($record->is_absent)
                                                            <span class="text-danger fw-bold"><i class="fa fa-times-circle me-1"></i> {{ __('marks.absent') }}</span>
                                                        @else
                                                            <span class="fw-bold fs-16 text-primary">{{ $record->marks_obtained }}</span>
                                                            <span class="text-muted small">/ {{ $record->subject->total_marks ?? 100 }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center pe-4">
                                                        @if($record->is_absent)
                                                            <span class="badge badge-danger light">{{ __('marks.fail') ?? 'Fail' }}</span>
                                                        @else
                                                            @php
                                                                $max = $record->subject->total_marks ?? 100;
                                                                $pass = $record->subject->passing_marks ?? ($max * 0.4);
                                                                $isPass = $record->marks_obtained >= $pass;
                                                            @endphp
                                                            @if($isPass)
                                                                <span class="badge badge-success light"><i class="fa fa-check me-1"></i> {{ __('marks.pass') ?? 'Pass' }}</span>
                                                            @else
                                                                <span class="badge badge-danger light"><i class="fa fa-times me-1"></i> {{ __('marks.fail') ?? 'Fail' }}</span>
                                                            @endif
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State (Enrolled but no published marks yet) --}}
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info text-center p-5 shadow-sm border-0" style="border-radius: 12px; background-color: #e8f4fd; color: #0d6efd;">
                            <i class="fa fa-info-circle fa-3x mb-3"></i>
                            <h4>{{ __('marks.no_records_found') ?? 'No Published Results' }}</h4>
                            <p class="text-muted mb-0">Your exam marks will appear here once they have been finalized and published by the administration.</p>
                        </div>
                    </div>
                </div>
            @endif

        @endif
    </div>
</div>
@endsection