@extends('layout.layout')

@section('styles')
<style>
    .profile-photo {
        width: 120px;
        height: 120px;
        margin: 0 auto 20px;
        position: relative;
    }
    .profile-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .profile-tab .nav-link {
        font-weight: 600;
        border: none;
        border-bottom: 2px solid transparent;
        color: #7e7e7e;
    }
    .profile-tab .nav-link.active {
        border-bottom: 2px solid var(--primary);
        color: var(--primary);
        background: transparent;
    }
    .upload-btn-wrapper {
        position: absolute;
        bottom: 0;
        right: 0;
        background: var(--primary);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
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
</style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <!-- Breadcrumb -->
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('profile.my_profile') }}</h4>
                    <p class="mb-0">{{ __('profile.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('profile.dashboard') }}</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ __('profile.profile') }}</a></li>
                </ol>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Profile Card -->
            <div class="col-xl-3 col-lg-4">
                <div class="clearfix">
                    <div class="card card-bx profile-card author-profile m-b30">
                        <div class="card-body">
                            <div class="p-5">
                                <div class="author-profile">
                                    <div class="author-media">
                                        @if($user->profile_picture)
                                            <img src="{{ asset('storage/'.$user->profile_picture) }}" alt="Profile" style="width:130px; height:130px; object-fit:cover; border-radius:50%;">
                                        @else
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width:130px; height:130px; font-size:40px; font-weight:bold;">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="author-info">
                                        <h6 class="title">{{ $user->name }}</h6>
                                        <span>{{ $user->roles->pluck('name')->first() ?? __('profile.user') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="info-list">
                                <ul>
                                    <li><a href="javascript:void(0)">{{ __('profile.email') }}</a><span>{{ $user->email }}</span></li>
                                    <li><a href="javascript:void(0)">{{ __('profile.joined') }}</a><span>{{ $user->created_at->format('d M, Y') }}</span></li>
                                    <li><a href="javascript:void(0)">{{ __('profile.status') }}</a>
                                        <span class="badge badge-success light">{{ __('profile.active') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Details & Edit -->
            <div class="col-xl-9 col-lg-8">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h4 class="card-title">{{ __('profile.profile_settings') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-tab">
                            <div class="custom-tab-1">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item"><a href="#my-posts" data-bs-toggle="tab" class="nav-link active show">{{ __('profile.tab_overview') }}</a></li>
                                    <li class="nav-item"><a href="#about-me" data-bs-toggle="tab" class="nav-link">{{ __('profile.tab_edit_profile') }}</a></li>
                                    <li class="nav-item"><a href="#profile-settings" data-bs-toggle="tab" class="nav-link">{{ __('profile.tab_security') }}</a></li>
                                </ul>
                                <div class="tab-content">
                                    
                                    <!-- TAB 1: OVERVIEW -->
                                    <div id="my-posts" class="tab-pane fade active show">
                                        <div class="pt-3">
                                            <div class="settings-form">
                                                <h4 class="text-primary">{{ __('profile.account_information') }}</h4>
                                                <div class="row mb-4">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small">{{ __('profile.full_name') }}</label>
                                                        <h5 class="text-black">{{ $user->name }}</h5>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small">{{ __('profile.email') }}</label>
                                                        <h5 class="text-black">{{ $user->email }}</h5>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small">{{ __('profile.phone') }}</label>
                                                        <h5 class="text-black">{{ $user->phone ?? __('profile.not_set') }}</h5>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted small">{{ __('profile.address') }}</label>
                                                        <h5 class="text-black">{{ $user->address ?? __('profile.not_set') }}</h5>
                                                    </div>
                                                </div>

                                                {{-- If linked to Student/Staff, show extra info --}}
                                                @if($user->student)
                                                    <h4 class="text-primary mt-4">{{ __('profile.academic_details') }}</h4>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label class="form-label text-muted small">{{ __('profile.admission_no') }}</label>
                                                            <h5>{{ $user->student->admission_number }}</h5>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 2: EDIT PROFILE -->
                                    <div id="about-me" class="tab-pane fade">
                                        <div class="pt-3">
                                            <div class="settings-form">
                                                <h4 class="text-primary">{{ __('profile.update_profile') }}</h4>
                                                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    
                                                    {{-- Profile Image Edit --}}
                                                    <div class="row mb-4 align-items-center">
                                                        <div class="col-auto">
                                                            <div class="profile-photo">
                                                                @if($user->profile_picture)
                                                                    <img id="editProfilePreview" src="{{ asset('storage/'.$user->profile_picture) }}">
                                                                @else
                                                                    <img id="editProfilePreview" src="{{ asset('images/profile/17.jpg') }}" style="opacity: 0.5">
                                                                @endif
                                                                <div class="upload-btn-wrapper">
                                                                    <i class="fa fa-camera"></i>
                                                                    <input type="file" name="profile_picture" id="profileUpload" accept="image/*">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <p class="mb-0 text-muted small">{{ __('profile.upload_hint') }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label">{{ __('profile.full_name') }} <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label">{{ __('profile.email') }} <span class="text-danger">*</span></label>
                                                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label">{{ __('profile.phone') }}</label>
                                                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label">{{ __('profile.address') }}</label>
                                                            <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-control">
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-primary" type="submit">{{ __('profile.save_changes') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TAB 3: SECURITY (PASSWORD) -->
                                    <div id="profile-settings" class="tab-pane fade">
                                        <div class="pt-3">
                                            <div class="settings-form">
                                                <h4 class="text-primary">{{ __('profile.change_password') }}</h4>
                                                <form action="{{ route('profile.password') }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    
                                                    <div class="row">
                                                        <div class="mb-3 col-md-12">
                                                            <label class="form-label">{{ __('profile.current_password') }} <span class="text-danger">*</span></label>
                                                            <input type="password" name="current_password" class="form-control" required>
                                                            @error('current_password') <span class="text-danger small">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label">{{ __('profile.new_password') }} <span class="text-danger">*</span></label>
                                                            <input type="password" name="password" class="form-control" required>
                                                            @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="mb-3 col-md-6">
                                                            <label class="form-label">{{ __('profile.confirm_password') }} <span class="text-danger">*</span></label>
                                                            <input type="password" name="password_confirmation" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-danger" type="submit">{{ __('profile.update_password') }}</button>
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