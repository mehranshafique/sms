<form action="{{ isset($head_officer) ? route('header-officers.update', $head_officer->id) : route('header-officers.store') }}" method="POST" id="officerForm" enctype="multipart/form-data">
    @csrf
    @if(isset($head_officer))
        @method('PUT')
    @endif

    <div class="row">
        <!-- Basic Information -->
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('head_officers.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            {{-- Profile Picture with Preview --}}
                            <div class="mb-4 col-md-12 text-center">
                                <label class="form-label d-block">{{ __('head_officers.profile_picture') }}</label>
                                <div class="avatar-upload d-inline-block position-relative">
                                    <div class="position-relative">
                                        <div class="change-btn d-flex align-items-center justify-content-center">
                                            <input type="file" class="form-control d-none" name="profile_picture" id="profile_picture" accept=".png, .jpg, .jpeg">
                                            <label for="profile_picture" class="btn btn-primary btn-sm rounded-circle p-2 mb-0 cursor-pointer" style="cursor: pointer;"><i class="fa fa-camera"></i></label>
                                        </div>
                                        <div class="avatar-preview rounded-circle" style="width: 120px; height: 120px; overflow: hidden; border: 3px solid #eee; margin: 0 auto;">
                                            @if(isset($head_officer) && $head_officer->profile_picture)
                                                <img id="preview_image" src="{{ asset('storage/' . $head_officer->profile_picture) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <img id="preview_image" src="{{ asset('images/no-image.png') }}" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.6;">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('head_officers.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $head_officer->name ?? '') }}" class="form-control" placeholder="{{ __('head_officers.enter_name') }}" required>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('head_officers.status') }}</label>
                                <select name="is_active" class="form-control default-select">
                                    <option value="1" {{ (old('is_active', $head_officer->is_active ?? 1) == 1) ? 'selected' : '' }}>{{ __('head_officers.active') }}</option>
                                    <option value="0" {{ (old('is_active', $head_officer->is_active ?? 1) == 0) ? 'selected' : '' }}>{{ __('head_officers.inactive_status') }}</option>
                                </select>
                            </div>

                            {{-- Role Selection (DISABLED/HIDDEN) --}}
                            {{-- Auto-assigning 'Head Officer' role --}}
                            <input type="hidden" name="role" value="Head Officer">
                            
                            {{-- Institute Assignment (Select2 Multi) --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('head_officers.select_institutes') }}</label>
                                <select name="institute_ids[]" class="form-control multi-select" multiple="multiple">
                                    @if(isset($institutes) && count($institutes) > 0)
                                        @foreach($institutes as $id => $name)
                                            <option value="{{ $id }}" {{ (isset($assignedIds) && in_array($id, $assignedIds)) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('head_officers.contact_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('head_officers.email') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $head_officer->email ?? '') }}" class="form-control" placeholder="{{ __('head_officers.enter_email') }}" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('head_officers.phone') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phoneInput" value="{{ old('phone', $head_officer->phone ?? '') }}" class="form-control" placeholder="+243..." required>
                                <small class="text-danger d-none" id="phoneError">Phone number must start with a country code (+)</small>
                            </div>
                            
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('head_officers.password') }}</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ __('head_officers.enter_password') }}" {{ isset($head_officer) ? '' : 'required' }}>
                                @if(isset($head_officer))
                                    <small class="text-muted">{{ __('head_officers.leave_blank_to_keep_current') }}</small>
                                @endif
                            </div>

                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('head_officers.address') }}</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="{{ __('head_officers.enter_address') }}">{{ old('address', $head_officer->address ?? '') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3" id="submitBtn">{{ isset($head_officer) ? __('head_officers.update') : __('head_officers.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image Preview Logic
        const profileInput = document.getElementById('profile_picture');
        const previewImage = document.getElementById('preview_image');

        if(profileInput && previewImage) {
            profileInput.onchange = function (evt) {
                const [file] = profileInput.files;
                if (file) {
                    previewImage.src = URL.createObjectURL(file);
                    previewImage.style.opacity = '1';
                }
            };
        }

        // Phone Validation Logic
        const phoneInput = document.getElementById('phoneInput');
        const phoneError = document.getElementById('phoneError');
        const officerForm = document.getElementById('officerForm');

        function validatePhone() {
            const val = phoneInput.value.trim();
            if(val.length > 0 && !val.startsWith('+')) {
                phoneInput.classList.add('is-invalid');
                phoneError.classList.remove('d-none');
                return false;
            } else {
                phoneInput.classList.remove('is-invalid');
                phoneError.classList.add('d-none');
                return true;
            }
        }

        if(phoneInput) {
            phoneInput.addEventListener('input', validatePhone);
            phoneInput.addEventListener('blur', validatePhone);
        }

        if(officerForm) {
            officerForm.addEventListener('submit', function(e) {
                if(!validatePhone()) {
                    e.preventDefault();
                    phoneInput.focus();
                }
            });
        }
    });
</script>