@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm align-items-center">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary">{{ __('parent.view_parent') }}</h4>
                </div>
            </div>
            <div class="col-sm-6 p-0 text-end">
                <a href="{{ route('parents.edit', $parent->id) }}" class="btn btn-primary btn-sm shadow me-2">
                    <i class="fa fa-pencil me-1"></i> {{ __('parent.edit_parent') }}
                </a>
                <a href="{{ route('parents.index') }}" class="btn btn-light btn-sm shadow">
                    <i class="fa fa-arrow-left"></i> {{ __('parent.cancel') }}
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Left: Details --}}
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">{{ __('parent.contact_info') }}</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between flex-wrap px-0">
                                <span class="text-muted">{{ __('parent.father_name') }}</span>
                                <span class="fw-bold">{{ $parent->father_name ?? '-' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between flex-wrap px-0">
                                <span class="text-muted">{{ __('parent.father_phone') }}</span>
                                <span>{{ $parent->father_phone ?? '-' }}</span>
                            </li>
                            
                            <li class="list-group-item d-flex justify-content-between flex-wrap px-0 mt-2">
                                <span class="text-muted">{{ __('parent.mother_name') }}</span>
                                <span class="fw-bold">{{ $parent->mother_name ?? '-' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between flex-wrap px-0">
                                <span class="text-muted">{{ __('parent.mother_phone') }}</span>
                                <span>{{ $parent->mother_phone ?? '-' }}</span>
                            </li>

                            <li class="list-group-item d-flex justify-content-between flex-wrap px-0 mt-2">
                                <span class="text-muted">{{ __('parent.address_label') }}</span>
                                <span class="text-end" style="max-width: 60%">{{ $parent->family_address ?? '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header border-bottom bg-light">
                        <h5 class="card-title mb-0">{{ __('parent.login_access') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($parent->user)
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $parent->user->name }}</h6>
                                    <small class="text-muted">{{ $parent->user->email }}</small>
                                </div>
                                <span class="badge badge-success">{{ __('parent.active') }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">{{ __('parent.username') }}:</span>
                                <span class="font-w600">{{ $parent->user->username ?? '-' }}</span>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fa fa-exclamation-triangle me-1"></i> No login account linked.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Wards --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">{{ __('parent.linked_students') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Name</th>
                                        <th>Admission No</th>
                                        <th>Class / Grade</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($parent->students as $student)
                                        @php
                                            $enrollment = $student->enrollments()->latest()->first();
                                            $grade = $enrollment->classSection->gradeLevel->name ?? $student->gradeLevel->name ?? '-';
                                            $class = $enrollment->classSection->name ?? '-';
                                        @endphp
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    @if($student->student_photo)
                                                        <img src="{{ asset('storage/'.$student->student_photo) }}" class="rounded-circle me-2" width="35" height="35">
                                                    @else
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:35px; height:35px">
                                                            {{ substr($student->first_name, 0, 1) }}
                                                        </div>
                                                    @endif
                                                    <strong>{{ $student->full_name }}</strong>
                                                </div>
                                            </td>
                                            <td>{{ $student->admission_number }}</td>
                                            <td>{{ $grade }} - {{ $class }}</td>
                                            <td><span class="badge badge-success light">Active</span></td>
                                            <td class="text-end pe-4">
                                                <a href="{{ route('students.show', $student->id) }}" class="btn btn-outline-primary btn-xs sharp"><i class="fa fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">{{ __('parent.no_students') }}</td>
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
@endsection