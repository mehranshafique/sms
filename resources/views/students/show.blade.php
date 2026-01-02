@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('student.student_details') }}</h4>
                    <p class="mb-0">{{ $student->full_name }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('students.index') }}">{{ __('student.student_list') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ $student->full_name }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            {{-- Left Column: Identity Card Style --}}
            <div class="col-xl-4 col-lg-4">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <div class="text-center">
                            {{-- Profile Photo --}}
                            <div class="profile-photo">
                                @if($student->student_photo)
                                    <img src="{{ asset('storage/'.$student->student_photo) }}" width="100" class="img-fluid rounded-circle" alt="">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 40px; font-weight: bold;">
                                        {{ substr($student->first_name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <h3 class="mt-4 mb-1">{{ $student->full_name }}</h3>
                            <p class="text-muted mb-2">{{ $student->institution->name }}</p>
                            
                            {{-- Permanent ID Display --}}
                            <div class="alert alert-primary light d-inline-block px-4 py-2 mt-2">
                                <span class="d-block text-uppercase fs-12 font-w600 text-primary">{{ __('student.admission_no') }}</span>
                                <h4 class="mb-0 font-w700">{{ $student->admission_number }}</h4>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary btn-sm me-3">
                                        <i class="fa fa-pencil me-1"></i> {{ __('student.edit') }}
                                    </a>
                                    {{-- Added Transfer Button with spacing --}}
                                    @can('student_transfer.create')
                                        @if($student->status === 'active')
                                            <a href="{{ route('transfers.create', $student->id) }}" class="btn btn-danger btn-sm">
                                                <i class="fa fa-exchange-alt me-1"></i> {{ __('transfer.page_title') }}
                                            </a>
                                        @elseif($student->status === 'transferred' || $student->status === 'withdrawn')
                                            <a href="{{ route('transfers.print', $student->id) }}" class="btn btn-warning btn-sm" target="_blank">
                                                <i class="fa fa-print me-1"></i> {{ __('transfer.issue_certificate') }}
                                            </a>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </div>

                        {{-- Identity & Access Section (Merged for visibility) --}}
                        <div class="mt-4 pt-4 border-top">
                            <h5 class="text-primary mb-3">{{ __('student.identity_access') }}</h5>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">{{ __('student.nfc_tag_uid') }}:</span>
                                <span class="font-w600">{{ $student->nfc_tag_uid ?? 'N/A' }}</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">{{ __('student.qr_code_token') }}:</span>
                                <span class="font-w600">{{ $student->qr_code_token ?? 'N/A' }}</span>
                            </div>

                            {{-- Visual QR Code --}}
                            @if($student->qr_code_token)
                                <div class="text-center bg-light p-3 rounded mt-3">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ $student->qr_code_token }}" alt="QR Code" class="img-fluid" style="max-width: 100px;">
                                    <p class="fs-11 text-muted mt-2 mb-0">{{ __('student.scan_for_details') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="card-footer pt-0 pb-0 text-center">
                        <div class="row">
                            <div class="col-4 pt-3 pb-3 border-end">
                                <h3 class="mb-1">{{ $student->enrollments->count() }}</h3>
                                <span>{{ __('student.years') }}</span>
                            </div>
                            <div class="col-4 pt-3 pb-3 border-end">
                                <h3 class="mb-1">{{ ucfirst($student->gender) }}</h3>
                                <span>{{ __('student.gender') }}</span>
                            </div>
                            <div class="col-4 pt-3 pb-3">
                                <h3 class="mb-1">{{ \Carbon\Carbon::parse($student->dob)->age }}</h3>
                                <span>{{ __('student.age') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Right Column: Detailed Info --}}
            <div class="col-xl-8 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('student.profile_details') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-about-me">
                            
                            {{-- Official Info --}}
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('student.official_details') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.select_institute') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->institution->name ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.admission_date') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->admission_date ? $student->admission_date->format('d M, Y') : 'N/A' }}</span></div>
                                </div>
                                
                                {{-- NEW: Payment Mode Display --}}
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.payment_mode') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7">
                                        <span class="badge badge-{{ ($student->payment_mode ?? 'installment') == 'global' ? 'info' : 'primary' }}">
                                            {{ ucfirst($student->payment_mode ?? 'installment') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.status') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7">
                                        <span class="badge badge-{{ $student->status == 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($student->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Personal Info --}}
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('student.personal_details') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.dob') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->dob ? $student->dob->format('d M, Y') : 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.blood_group') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->blood_group ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.current_address') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->current_address ?? 'N/A' }}</span></div>
                                </div>
                            </div>

                            {{-- Parent Info --}}
                            <div class="pt-4 border-bottom-1 pb-4">
                                <h4 class="text-primary mb-4">{{ __('student.parents_guardian') }}</h4>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.father_name') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->father_name ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.father_phone') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->father_phone ?? 'N/A' }}</span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-4 col-5">
                                        <h5 class="f-w-500">{{ __('student.mother_name') }} <span class="pull-right">:</span></h5>
                                    </div>
                                    <div class="col-sm-8 col-7"><span>{{ $student->mother_name ?? 'N/A' }}</span></div>
                                </div>
                            </div>

                            {{-- Academic History (Enrollments) --}}
                            <div class="pt-4">
                                <h4 class="text-primary mb-4">{{ __('student.enrollment_history') }}</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('student.session') }}</th>
                                                <th>{{ __('student.class_grade') }}</th>
                                                {{-- UPDATED Header: Using Admission No as requested --}}
                                                <th>{{ __('student.admission_no') }}</th>
                                                <th>{{ __('student.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($student->enrollments as $enrollment)
                                                <tr>
                                                    <td>{{ $enrollment->academicSession->name ?? 'N/A' }}</td>
                                                    <td>
                                                        {{ $enrollment->classSection->name ?? 'N/A' }}
                                                        <small class="text-muted d-block">
                                                            {{ $enrollment->classSection->gradeLevel->name ?? '' }}
                                                        </small>
                                                    </td>
                                                    {{-- UPDATED Body: Showing Admission No instead of Roll Number --}}
                                                    <td>{{ $student->admission_number ?? '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-sm badge-{{ $enrollment->status == 'active' ? 'success' : 'light' }}">
                                                            {{ ucfirst($enrollment->status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">{{ __('student.no_enrollment_found') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection