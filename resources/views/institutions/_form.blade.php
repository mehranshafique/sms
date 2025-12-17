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
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.institute_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $institute->name ?? '') }}" class="form-control" placeholder="{{ __('institute.enter_institute_name') }}" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" value="{{ old('code', $institute->code ?? '') }}" class="form-control" placeholder="EX: INST001" required>
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
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.logo') }}</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text">Upload</span>
                                    <div class="form-file">
                                        <input type="file" name="logo" class="form-file-input form-control">
                                    </div>
                                </div>
                                @if(isset($institute) && $institute->logo)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/'.$institute->logo) }}" width="50" class="rounded-circle" alt="Logo">
                                    </div>
                                @endif
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
                                <label class="form-label">{{ __('institute.phone_number') }}</label>
                                <input type="text" name="phone" value="{{ old('phone', $institute->phone ?? '') }}" class="form-control">
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('institute.password') }}</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ __('institute.enter_password') }}">
                                @if(isset($institute))
                                    <small class="text-muted">{{ __('institute.leave_blank_to_keep_current') }}</small>
                                @endif
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.enter_country') }}</label>
                                <input type="text" name="country" value="{{ old('country', $institute->country ?? '') }}" class="form-control">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.city') }}</label>
                                <input type="text" name="city" value="{{ old('city', $institute->city ?? '') }}" class="form-control">
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