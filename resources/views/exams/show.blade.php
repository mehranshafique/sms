@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('exam.page_title') }}</h4>
                    <p class="mb-0">{{ $exam->name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('exams.index') }}">{{ __('exam.exam_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $exam->name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            {{-- Left Column: Exam Info --}}
            <div class="col-xl-4 col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="profile-photo">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 30px;">
                                    <i class="fa fa-file-text-o"></i>
                                </div>
                            </div>
                            <h3 class="mt-3 mb-1">{{ $exam->name }}</h3>
                            <p class="text-muted mb-0">{{ $exam->institution->name }}</p>
                            <span class="badge badge-primary mt-2">{{ $exam->academicSession->name }}</span>
                        </div>
                        
                        <div class="profile-personal-info">
                            <h5 class="text-primary mb-3">{{ __('exam.basic_information') }}</h5>
                            <div class="row mb-2">
                                <div class="col-5"><span class="fw-bold">{{ __('exam.start_date') }}:</span></div>
                                <div class="col-7"><span>{{ $exam->start_date->format('d M, Y') }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5"><span class="fw-bold">{{ __('exam.end_date') }}:</span></div>
                                <div class="col-7"><span>{{ $exam->end_date->format('d M, Y') }}</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5"><span class="fw-bold">{{ __('exam.status') }}:</span></div>
                                <div class="col-7">
                                    <span class="badge badge-{{ $exam->status == 'published' ? 'success' : ($exam->status == 'ongoing' ? 'warning' : 'info') }}">
                                        {{ ucfirst($exam->status) }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($exam->description)
                            <div class="row mb-2">
                                <div class="col-12 mt-3">
                                    <span class="fw-bold">{{ __('exam.description') }}:</span>
                                    <p class="text-muted mt-1">{{ $exam->description }}</p>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-top text-center d-flex flex-wrap justify-content-center">
                            {{-- Edit Button --}}
                            <a href="{{ route('exams.edit', $exam->id) }}" class="btn btn-outline-primary m-1">
                                <i class="fa fa-pencil me-1"></i> {{ __('exam.edit_exam') }}
                            </a>
                            
                            {{-- Enter Marks Button (Functional Link) --}}
                            @can('exam_mark.create')
                            <a href="{{ route('marks.create', ['exam_id' => $exam->id]) }}" class="btn btn-primary m-1">
                                <i class="fa fa-list-alt me-1"></i> {{ __('exam.enter_marks') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Stats or Guidelines --}}
            <div class="col-xl-8 col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('exam.statistics') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-sm-4 border-end">
                                <h3 class="mb-1 text-primary">0</h3>
                                <span>{{ __('exam.subjects') }}</span>
                            </div>
                            <div class="col-sm-4 border-end">
                                <h3 class="mb-1 text-warning">0</h3>
                                <span>{{ __('exam.students') }}</span>
                            </div>
                            <div class="col-sm-4">
                                <h3 class="mb-1 text-success">0%</h3>
                                <span>{{ __('exam.published_results') }}</span>
                            </div>
                        </div>
                        
                        <div class="mt-5 text-center">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486831.png" alt="Exam" width="150" class="opacity-50">
                            <p class="mt-3 text-muted">{{ __('exam.marks_placeholder') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection