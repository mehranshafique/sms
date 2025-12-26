<form action="{{ isset($institute) ? route('institutes.update', $institute->id) : route('institutes.store') }}" method="POST" id="instituteForm" enctype="multipart/form-data">
    @csrf
    @if(isset($institute))
        @method('PUT')
    @endif

    <div class="row">
        <!-- Basic Information -->
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('institute.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            {{-- Logo Upload with Preview --}}
                            <div class="col-md-12 mb-4 text-center">
                                <label class="form-label d-block">{{ __('institute.logo') }}</label>
                                <div class="avatar-upload d-inline-block position-relative">
                                    <div class="position-relative">
                                        <div class="change-btn d-flex align-items-center justify-content-center">
                                            <input type='file' class="form-control d-none" name="logo" id="logoUpload" accept=".png, .jpg, .jpeg" />
                                            <label for="logoUpload" class="btn btn-primary btn-sm rounded-circle p-2 mb-0"><i class="fa fa-camera"></i></label>
                                        </div>
                                        <div class="avatar-preview rounded-circle" style="width: 100px; height: 100px; overflow: hidden; border: 3px solid #eee;">
                                            @if(isset($institute) && $institute->logo)
                                                <img id="logoPreview" src="{{ asset('storage/'.$institute->logo) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <img id="logoPreview" src="{{ asset('images/no-image.png') }}" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.5;">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.institute_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $institute->name ?? '') }}" class="form-control" placeholder="{{ __('institute.enter_institute_name') }}" required>
                            </div>
                            
                            {{-- Acronym Field --}}
                            <div class="mb-3 col-md-3">
                                <label class="form-label">{{ __('institute.acronym') }}</label>
                                <input type="text" name="acronym" value="{{ old('acronym', $institute->acronym ?? '') }}" class="form-control" placeholder="e.g. DIS">
                            </div>

                            <div class="mb-3 col-md-3">
                                <label class="form-label">{{ __('institute.code') }}</label>
                                {{-- Code is auto-generated, so it's read-only or a placeholder --}}
                                <input type="text" name="code" value="{{ old('code', $institute->code ?? __('institute.auto_generated')) }}" class="form-control bg-light" readonly>
                                <small class="text-muted fs-10">City + Commune + Seq</small>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.institute_type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-control default-select" required>
                                    <option value="primary" {{ (old('type', $institute->type ?? '') == 'primary') ? 'selected' : '' }}>{{ __('institute.primary_school') }}</option>
                                    <option value="secondary" {{ (old('type', $institute->type ?? '') == 'secondary') ? 'selected' : '' }}>{{ __('institute.secondary_school') }}</option>
                                    <option value="university" {{ (old('type', $institute->type ?? '') == 'university') ? 'selected' : '' }}>{{ __('institute.university') }}</option>
                                    <option value="mixed" {{ (old('type', $institute->type ?? '') == 'mixed') ? 'selected' : '' }}>{{ __('institute.mixed_level') }}</option>
                                </select>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.status') }}</label>
                                <select name="is_active" class="form-control default-select">
                                    <option value="1" {{ (old('is_active', $institute->is_active ?? 1) == 1) ? 'selected' : '' }}>{{ __('institute.active') }}</option>
                                    <option value="0" {{ (old('is_active', $institute->is_active ?? 1) == 0) ? 'selected' : '' }}>{{ __('institute.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location & Contact -->
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('institute.contact_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.admin_email') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $institute->email ?? '') }}" class="form-control" required>
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.phone_number') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone', $institute->phone ?? '') }}" class="form-control" placeholder="+243..." required>
                                <small class="text-muted">{{ __('institute.phone_hint') }}</small>
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.password') }}</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ __('institute.enter_password') }}">
                                @if(isset($institute))
                                    <small class="text-muted">{{ __('institute.leave_blank_to_keep_current') }}</small>
                                @endif
                            </div>
                            
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.enter_country') }} <span class="text-danger">*</span></label>
                                <input type="text" name="country" value="{{ old('country', $institute->country ?? '') }}" class="form-control" required>
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.city') }} <span class="text-danger">*</span></label>
                                <input type="text" name="city" value="{{ old('city', $institute->city ?? '') }}" class="form-control" required>
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.commune') }} <span class="text-danger">*</span></label>
                                <input type="text" name="commune" value="{{ old('commune', $institute->commune ?? '') }}" class="form-control" required>
                            </div>
                            
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('institute.full_address') }}</label>
                                <textarea name="address" class="form-control" rows="3">{{ old('address', $institute->address ?? '') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($institute) ? __('institute.update_institute') : __('institute.save_institute') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Inline JS for Logo Preview --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoUpload = document.getElementById('logoUpload');
        const logoPreview = document.getElementById('logoPreview');

        if(logoUpload && logoPreview) {
            logoUpload.onchange = function (evt) {
                const [file] = logoUpload.files;
                if (file) {
                    logoPreview.src = URL.createObjectURL(file);
                    logoPreview.style.opacity = '1';
                }
            };
        }
    });
</script>