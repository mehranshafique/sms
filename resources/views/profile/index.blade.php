@extends('layout.layout')

@section('styles')
<style>
    .profile-photo {
        width: 140px;
        height: 140px;
        margin: 0 auto 20px;
        position: relative;
    }
    .profile-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .profile-tab .nav-link {
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        color: #7e7e7e;
        padding: 15px 25px;
    }
    .profile-tab .nav-link.active {
        border-bottom: 2px solid var(--primary);
        color: var(--primary);
        background: transparent;
    }
    .upload-btn-wrapper {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: var(--primary);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .upload-btn-wrapper:hover {
        transform: scale(1.1);
    }
    .upload-btn-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    .form-control[readonly] {
        background-color: #f8f9fa;
        opacity: 1;
        cursor: not-allowed;
    }
    
    /* Left Card Styling Update */
    .info-list-item {
        border-bottom: 1px dashed #eee;
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    .info-list-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .info-label {
        font-size: 12px;
        color: #888;
        display: block;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .info-value {
        font-size: 15px;
        font-weight: 600;
        color: #3d4465;
        display: block;
        padding-left: 24px; /* Align with text not icon */
    }
    .info-icon {
        width: 20px;
        display: inline-block;
        text-align: center;
        margin-right: 5px;
        color: var(--primary);
    }
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <!-- Breadcrumb -->
        <div class="row page-titles mx-0 mb-4 p-4 bg-white rounded shadow-sm">
            <div class="col-sm-6 p-0">
                <div class="welcome-text">
                    <h4 class="text-primary">{{ __('profile.my_profile') }}</h4>
                    <p class="mb-0 text-muted">{{ __('profile.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('profile.dashboard') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('profile.profile') }}</a></li>
                </ol>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="me-2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="me-2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <strong>Error!</strong> Please check the form below for validation errors.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Left Column: Profile Card -->
            <div class="col-xl-3 col-lg-4">
                <div class="clearfix">
                    <div class="card card-bx profile-card author-profile m-b30 shadow-sm border-0">
                        <div class="card-body">
                            <div class="p-4">
                                <div class="author-profile text-center">
                                    <div class="author-media position-relative mb-4">
                                        @if($user->profile_picture)
                                            <img src="{{ asset('storage/'.$user->profile_picture) }}" alt="Profile" style="width:130px; height:130px; object-fit:cover; border-radius:50%; border:4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                                        @else
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width:130px; height:130px; font-size:40px; font-weight:bold; border:4px solid white;">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="author-info">
                                        <h5 class="title mb-1">{{ $user->name }}</h5>
                                        <p class="text-muted mb-0">{{ $user->roles->pluck('name')->first() ?? __('profile.user') }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Info List (Updated Layout) --}}
                            <div class="info-list border-top pt-4">
                                
                                <div class="info-list-item">
                                    <span class="info-label"><i class="fa fa-user info-icon"></i> Username</span>
                                    <span class="info-value">{{ $user->username ?? '-' }}</span>
                                </div>

                                <div class="info-list-item">
                                    <span class="info-label"><i class="fa fa-envelope info-icon"></i> Email</span>
                                    <span class="info-value text-break">{{ $user->email }}</span>
                                </div>

                                <div class="info-list-item">
                                    <span class="info-label"><i class="fa fa-id-badge info-icon"></i> ID / Shortcode</span>
                                    <span class="info-value">
                                        <span class="badge badge-light text-primary">{{ $user->shortcode ?? '-' }}</span>
                                    </span>
                                </div>

                                <div class="info-list-item">
                                    <span class="info-label"><i class="fa fa-calendar info-icon"></i> Joined</span>
                                    <span class="info-value">{{ $user->created_at->format('d M, Y') }}</span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details & Edit -->
            <div class="col-xl-9 col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 pb-0 bg-transparent">
                        <h4 class="card-title">{{ __('profile.profile_settings') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-tab">
                            <div class="custom-tab-1">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item"><a href="#my-posts" data-bs-toggle="tab" class="nav-link active show"><i class="fa fa-user me-2"></i> {{ __('profile.tab_overview') }}</a></li>
                                    <li class="nav-item"><a href="#about-me" data-bs-toggle="tab" class="nav-link"><i class="fa fa-edit me-2"></i> {{ __('profile.tab_edit_profile') }}</a></li>
                                    <li class="nav-item"><a href="#profile-settings" data-bs-toggle="tab" class="nav-link"><i class="fa fa-lock me-2"></i> {{ __('profile.tab_security') }}</a></li>
                                </ul>
                                <div class="tab-content mt-4">
                                    
                                    <!-- TAB 1: OVERVIEW -->
                                    <div id="my-posts" class="tab-pane fade active show">
                                        <div class="pt-3">
                                            <h5 class="text-primary mb-4">{{ __('profile.account_information') }}</h5>
                                            <div class="row mb-4">
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label text-muted small text-uppercase fw-bold">{{ __('profile.full_name') }}</label>
                                                    <h5 class="text-black border-bottom pb-2">{{ $user->name }}</h5>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label text-muted small text-uppercase fw-bold">{{ __('profile.email') }}</label>
                                                    <h5 class="text-black border-bottom pb-2">{{ $user->email }}</h5>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label text-muted small text-uppercase fw-bold">Username</label>
                                                    <h5 class="text-black border-bottom pb-2">{{ $user->username ?? '-' }}</h5>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label text-muted small text-uppercase fw-bold">Shortcode</label>
                                                    <h5 class="text-black border-bottom pb-2">{{ $user->shortcode ?? '-' }}</h5>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label text-muted small text-uppercase fw-bold">{{ __('profile.phone') }}</label>
                                                    <h5 class="text-black border-bottom pb-2">{{ $user->phone ?? __('profile.not_set') }}</h5>
                                                </div>
                                                <div class="col-md-6 mb-4">
                                                    <label class="form-label text-muted small text-uppercase fw-bold">{{ __('profile.address') }}</label>
                                                    <h5 class="text-black border-bottom pb-2">{{ $user->address ?? __('profile.not_set') }}</h5>
                                                </div>
                                            </div>

                                            {{-- If linked to Student/Staff, show extra info --}}
                                            @if($user->student)
                                                <h5 class="text-primary mt-4 mb-3">{{ __('profile.academic_details') }}</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label text-muted small text-uppercase fw-bold">{{ __('profile.admission_no') }}</label>
                                                        <h5 class="text-black border-bottom pb-2">{{ $user->student->admission_number }}</h5>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- TAB 2: EDIT PROFILE -->
                                    <div id="about-me" class="tab-pane fade">
                                        <div class="pt-3">
                                            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                
                                                <h5 class="text-primary mb-4">Edit Personal Details</h5>

                                                {{-- Profile Image Edit --}}
                                                <div class="row mb-5 justify-content-center">
                                                    <div class="col-auto text-center">
                                                        <div class="profile-photo">
                                                            @if($user->profile_picture)
                                                                <img id="editProfilePreview" src="{{ asset('storage/'.$user->profile_picture) }}">
                                                            @else
                                                                <img id="editProfilePreview" src="{{ asset('images/profile/17.jpg') }}" style="opacity: 0.5">
                                                            @endif
                                                            <div class="upload-btn-wrapper shadow-lg">
                                                                <i class="fa fa-camera"></i>
                                                                <input type="file" name="profile_picture" id="profileUpload" accept="image/*">
                                                            </div>
                                                        </div>
                                                        <span class="text-muted small">{{ __('profile.upload_hint') }}</span>
                                                        @error('profile_picture')
                                                            <div class="text-danger small mt-2">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="mb-4 col-md-6">
                                                        <label class="form-label">{{ __('profile.full_name') }} <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                    
                                                    <div class="mb-4 col-md-6">
                                                        <label class="form-label">{{ __('profile.email') }} <span class="text-danger">*</span></label>
                                                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>

                                                    {{-- Editable Username --}}
                                                    <div class="mb-4 col-md-6">
                                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                                        <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control @error('username') is-invalid @enderror" required>
                                                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                    
                                                    {{-- Read-Only Shortcode --}}
                                                    <div class="mb-4 col-md-6">
                                                        <label class="form-label text-muted">Shortcode / ID (Read Only)</label>
                                                        <input type="text" class="form-control bg-light" value="{{ $user->shortcode }}" readonly title="Cannot be changed">
                                                    </div>

                                                    <div class="mb-4 col-md-6">
                                                        <label class="form-label">{{ __('profile.phone') }}</label>
                                                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </div>
                                                    
                                                    <div class="mb-4 col-md-6">
                                                        <label class="form-label">{{ __('profile.address') }}</label>
                                                        <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-control">
                                                    </div>
                                                </div>
                                                
                                                <div class="text-end">
                                                    <button class="btn btn-primary px-5 shadow" type="submit">{{ __('profile.save_changes') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- TAB 3: SECURITY (PASSWORD) -->
                                    <div id="profile-settings" class="tab-pane fade">
                                        <div class="pt-3">
                                            <div class="settings-form">
                                                <h5 class="text-primary mb-4">{{ __('profile.change_password') }}</h5>
                                                <form action="{{ route('profile.password') }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    
                                                    <div class="row">
                                                        <div class="mb-4 col-md-12">
                                                            <label class="form-label">{{ __('profile.current_password') }} <span class="text-danger">*</span></label>
                                                            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                                                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                        </div>
                                                        <div class="mb-4 col-md-6">
                                                            <label class="form-label">{{ __('profile.new_password') }} <span class="text-danger">*</span></label>
                                                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                        </div>
                                                        <div class="mb-4 col-md-6">
                                                            <label class="form-label">{{ __('profile.confirm_password') }} <span class="text-danger">*</span></label>
                                                            <input type="password" name="password_confirmation" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <button class="btn btn-danger px-5 shadow" type="submit">{{ __('profile.update_password') }}</button>
                                                    </div>
                                                </form>
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
    </div>
</div>
@endsection

@section('js')
<script>
    // Image Preview Script
    document.getElementById('profileUpload').addEventListener('change', function(event){
        var output = document.getElementById('editProfilePreview');
        if(event.target.files && event.target.files[0]) {
            output.src = URL.createObjectURL(event.target.files[0]);
            output.style.opacity = 1;
        }
    });
</script>
@endsection