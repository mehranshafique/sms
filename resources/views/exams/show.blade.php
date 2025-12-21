@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        {{-- Header --}}
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

        {{-- Actions Toolbar (Finalize / Print) --}}
        <div class="row">
            <div class="col-12 text-end mb-3">
                @if(!$exam->finalized_at && auth()->user()->can('update', $exam))
                    <form action="{{ route('exams.finalize', $exam->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('exam.lock_warning') }}')">
                        @csrf
                        <button type="submit" class="btn btn-warning"><i class="fa fa-lock"></i> {{ __('exam.finalize_publish') }}</button>
                    </form>
                @endif
                
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#printModal">
                    <i class="fa fa-print"></i> {{ __('exam.print_results') }}
                </button>
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
                            @if(!$exam->finalized_at || auth()->user()->hasRole('Super Admin'))
                            <a href="{{ route('exams.edit', $exam->id) }}" class="btn btn-outline-primary m-1">
                                <i class="fa fa-pencil me-1"></i> {{ __('exam.edit_exam') }}
                            </a>
                            @endif
                            
                            {{-- Enter Marks Button --}}
                            @can('exam_mark.create')
                            <a href="{{ route('marks.create', ['exam_id' => $exam->id]) }}" class="btn btn-primary m-1">
                                <i class="fa fa-list-alt me-1"></i> {{ __('exam.enter_marks') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Stats --}}
            <div class="col-xl-8 col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('exam.statistics') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-sm-4 border-end">
                                <h3 class="mb-1 text-primary">{{ $exam->records()->distinct('subject_id')->count() }}</h3>
                                <span>{{ __('exam.subjects') }}</span>
                            </div>
                            <div class="col-sm-4 border-end">
                                <h3 class="mb-1 text-warning">{{ $exam->records()->distinct('student_id')->count() }}</h3>
                                <span>{{ __('exam.students') }}</span>
                            </div>
                            <div class="col-sm-4">
                                <h3 class="mb-1 text-success">{{ $exam->status == 'published' ? '100' : '0' }}%</h3>
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

{{-- Print Modal --}}
<div class="modal fade" id="printModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('exam.print_class_result') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('exams.print_result', $exam->id) }}" method="GET" target="_blank">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">{{ __('exam.select_class') }}</label>
                         <select name="class_section_id" class="form-control default-select" required>
                             <option value="">{{ __('exam.select_class') }}</option>
                             @if(isset($classes))
                                @foreach($classes as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                             @endif
                         </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ __('exam.print') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection