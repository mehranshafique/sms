@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('settings.page_title') }}</h4>
                    <p class="mb-0">{{ __('settings.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h4 class="card-title">{{ __('settings.settings_management') }}</h4>
                    </div>
                    <div class="card-body">
                        <!-- Nav tabs -->
                        <div class="custom-tab-1">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#attendance">{{ __('settings.tab_attendance') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#exams">{{ __('settings.tab_exams') }}</a>
                                </li>
                                {{-- Academic Tab Link --}}
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#academic">{{ __('settings.tab_academic') ?? 'Academic' }}</a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                
                                {{-- Attendance Tab --}}
                                <div class="tab-pane fade show active" id="attendance" role="tabpanel">
                                    <div class="pt-4">
                                        @include('settings.partials.attendance')
                                    </div>
                                </div>

                                {{-- Exams Tab --}}
                                <div class="tab-pane fade" id="exams">
                                    <div class="pt-4">
                                        @include('settings.partials.exams')
                                    </div>
                                </div>

                                {{-- Academic Tab Content --}}
                                <div class="tab-pane fade" id="academic">
                                    <div class="pt-4">
                                        @include('settings.partials.academic')
                                    </div>
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

@section('js')
{{-- FIXED: Add SweetAlert2 Library --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
