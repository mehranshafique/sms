<!-- Add intl-tel-input CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<style>
    /* Fix for intl-tel-input dropdown width */
    .iti { width: 100%; }
    .valid-feedback-custom { color: #28a745; font-size: 0.875em; display: none; margin-top: 0.25rem; }
    .invalid-feedback-custom { color: #dc3545; font-size: 0.875em; display: none; margin-top: 0.25rem; }
</style>

<form action="{{ isset($institute) ? route('institutes.update', $institute->id) : route('institutes.store') }}" method="POST" id="instituteForm" enctype="multipart/form-data">
    @csrf
    @if(isset($institute))
        @method('PUT')
        {{-- Pass ID for JS check --}}
        <input type="hidden" id="editInstituteId" value="{{ $institute->id }}">
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
                            {{-- Logo Upload --}}
                            <div class="col-md-12 mb-4 text-center">
                                <label class="form-label d-block">{{ __('institute.logo') }}</label>
                                <div class="avatar-upload d-inline-block position-relative">
                                    <div class="position-relative">
                                        <div class="change-btn d-flex align-items-center justify-content-center">
                                            <input type='file' class="form-control d-none" name="logo" id="logoUpload" accept=".png, .jpg, .jpeg" />
                                            <label for="logoUpload" class="btn btn-primary btn-sm rounded-circle p-2 mb-0 cursor-pointer" style="cursor: pointer;"><i class="fa fa-camera"></i></label>
                                        </div>
                                        <div class="avatar-preview rounded-circle" style="width: 100px; height: 100px; overflow: hidden; border: 3px solid #eee; margin: 0 auto;">
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
                                <input type="text" name="name" value="{{ old('name', $institute->name ?? '') }}" class="form-control" placeholder="{{ __('institute.institute_name_placeholder') }}" required>
                            </div>
                            
                            <div class="mb-3 col-md-3">
                                <label class="form-label">{{ __('institute.acronym') }}</label>
                                <input type="text" name="acronym" value="{{ old('acronym', $institute->acronym ?? '') }}" class="form-control" placeholder="{{ __('institute.acronym_placeholder') }}">
                            </div>

                            <div class="mb-3 col-md-3">
                                <label class="form-label">{{ __('institute.code') }}</label>
                                <input type="text" name="code" value="{{ old('code', $institute->code ?? __('institute.auto_generated')) }}" class="form-control bg-light" readonly>
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
                            
                            {{-- 1. Country Select --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('locations.country') }} <span class="text-danger">*</span></label>
                                <select name="country" id="countrySelect" class="form-control default-select" required>
                                    <option value="">{{ __('locations.select_country') }}</option>
                                    {{-- Populated via AJAX --}}
                                </select>
                            </div>

                            {{-- 2. State Select --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('locations.state') ?? 'State' }} <span class="text-danger">*</span></label>
                                <select name="state" id="stateSelect" class="form-control default-select" required disabled>
                                    <option value="">{{ __('locations.select_state') ?? 'Select State' }}</option>
                                </select>
                            </div>

                            {{-- 3. City Select --}}
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('locations.city') }} <span class="text-danger">*</span></label>
                                <select name="city" id="citySelect" class="form-control default-select" required disabled>
                                    <option value="">{{ __('locations.select_city') }}</option>
                                </select>
                            </div>

                            {{-- Email with Live Check --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.admin_email') }} <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="email" name="email" id="emailInput" value="{{ old('email', $institute->email ?? '') }}" class="form-control" placeholder="{{ __('institute.email_placeholder') }}" required autocomplete="off">
                                    <div class="valid-feedback-custom" id="emailValid"><i class="fa fa-check-circle"></i> Email available</div>
                                    <div class="invalid-feedback-custom" id="emailInvalid"><i class="fa fa-times-circle"></i> Email already exists</div>
                                    <div id="emailLoading" class="spinner-border text-primary spinner-border-sm position-absolute" style="top: 10px; right: 10px; display: none;" role="status"></div>
                                </div>
                            </div>
                            
                            {{-- Phone with intl-tel-input --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.phone_number') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="hidden" name="full_phone" id="fullPhoneInput">
                                    <input type="tel" id="phoneInput" class="form-control" value="{{ old('phone', $institute->phone ?? '') }}" required>
                                </div>
                                @error('phone')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('institute.password') }}</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ __('institute.password_placeholder') }}">
                                @if(isset($institute))
                                    <small class="text-muted">{{ __('institute.leave_blank_to_keep_current') }}</small>
                                @endif
                            </div>
                            
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('institute.full_address') }}</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="{{ __('institute.address_placeholder') }}">{{ old('address', $institute->address ?? '') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" id="submitBtn" class="btn btn-primary mt-3">{{ isset($institute) ? __('institute.update_btn') : __('institute.save_btn') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Add intl-tel-input JS -->
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Helper to refresh NiceSelect/Select2 ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if($.fn.niceSelect) {
                    $(element).niceSelect('update');
                } else if ($.fn.selectpicker) { // Bootstrap select
                     $(element).selectpicker('refresh');
                }
            }
        }

        // --- 1. Email Availability Check ---
        const emailInput = document.getElementById('emailInput');
        const emailValid = document.getElementById('emailValid');
        const emailInvalid = document.getElementById('emailInvalid');
        const emailLoading = document.getElementById('emailLoading');
        const submitBtn = document.getElementById('submitBtn');
        const editId = document.getElementById('editInstituteId') ? document.getElementById('editInstituteId').value : null;
        let typingTimer;

        if(emailInput) {
            emailInput.addEventListener('keyup', function() {
                clearTimeout(typingTimer);
                emailValid.style.display = 'none';
                emailInvalid.style.display = 'none';
                
                if(this.value.length > 5 && this.value.includes('@')) {
                    emailLoading.style.display = 'block';
                    typingTimer = setTimeout(checkEmail, 800);
                }
            });

            function checkEmail() {
                const email = emailInput.value;
                const url = "{{ route('institutes.check_email') }}";
                
                let checkUrl = `${url}?email=${encodeURIComponent(email)}`;
                if(editId) {
                    checkUrl += `&id=${editId}`;
                }
                
                fetch(checkUrl)
                    .then(response => response.json())
                    .then(data => {
                        emailLoading.style.display = 'none';
                        if(data.exists) {
                            emailInput.classList.add('is-invalid');
                            emailInput.classList.remove('is-valid');
                            emailInvalid.style.display = 'block';
                            submitBtn.disabled = true;
                        } else {
                            emailInput.classList.add('is-valid');
                            emailInput.classList.remove('is-invalid');
                            emailValid.style.display = 'block';
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        emailLoading.style.display = 'none';
                    });
            }
        }

        // --- 2. Phone Input Integration (intl-tel-input) ---
        const phoneInput = document.querySelector("#phoneInput");
        const fullPhoneInput = document.querySelector("#fullPhoneInput");
        const form = document.querySelector("#instituteForm");

        if (phoneInput) {
            const iti = window.intlTelInput(phoneInput, {
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                initialCountry: "cd",
                separateDialCode: true,
                preferredCountries: ['cd', 'us', 'fr'],
            });

            form.addEventListener('submit', function() {
                if (iti.isValidNumber()) {
                    fullPhoneInput.value = iti.getNumber();
                } else {
                    fullPhoneInput.value = iti.getNumber(); 
                }
            });
        }

        // --- 3. Location Logic (Country -> State -> City) ---
        const countrySelect = document.getElementById('countrySelect');
        const stateSelect = document.getElementById('stateSelect');
        const citySelect = document.getElementById('citySelect');
        
        // Old Values
        const oldCountryId = "{{ old('country', $institute->country ?? '') }}";
        const oldStateId = "{{ old('state', $institute->state ?? '') }}";
        const oldCityId = "{{ old('city', $institute->city ?? '') }}";

        // Fetch Countries
        if (countrySelect) {
            fetch("{{ route('locations.countries') }}")
                .then(response => response.json())
                .then(data => {
                    countrySelect.innerHTML = '<option value="">{{ __('locations.select_country') }}</option>';
                    data.forEach(country => {
                        let option = new Option(country.name, country.id);
                        if (String(country.id) === String(oldCountryId)) option.selected = true;
                        countrySelect.add(option);
                    });
                    if (oldCountryId) triggerChangeEvent(countrySelect);
                    
                    refreshSelect(countrySelect);
                });

            // Country Change -> Load States
            countrySelect.addEventListener('change', function() {
                stateSelect.innerHTML = '<option value="">{{ __('locations.select_state') ?? 'Select State' }}</option>';
                citySelect.innerHTML = '<option value="">{{ __('locations.select_city') }}</option>';
                stateSelect.disabled = true;
                citySelect.disabled = true;
                
                refreshSelect(stateSelect);
                refreshSelect(citySelect);

                if (this.value) {
                    // Fetch States using country_id
                    fetch(`{{ route('locations.states') }}?country_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(state => {
                                let option = new Option(state.name, state.id);
                                if (String(state.id) === String(oldStateId)) option.selected = true;
                                stateSelect.add(option);
                            });
                            stateSelect.disabled = false;
                            
                            refreshSelect(stateSelect);

                            if (oldStateId && String(this.value) === String(oldCountryId)) triggerChangeEvent(stateSelect);
                        });
                }
            });
        }

        // State Change -> Load Cities
        if (stateSelect) {
            stateSelect.addEventListener('change', function() {
                citySelect.innerHTML = '<option value="">{{ __('locations.select_city') }}</option>';
                citySelect.disabled = true;
                refreshSelect(citySelect);

                if (this.value) {
                    // Fetch Cities using state_id
                    fetch(`{{ route('locations.cities') }}?state_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(city => {
                                let option = new Option(city.name, city.id);
                                if (String(city.id) === String(oldCityId)) option.selected = true;
                                citySelect.add(option);
                            });
                            citySelect.disabled = false;
                            
                            refreshSelect(citySelect);
                        });
                }
            });
        }

        function triggerChangeEvent(element) {
            element.dispatchEvent(new Event('change'));
        }
        
        // Logo Preview
        const logoUpload = document.getElementById('logoUpload');
        const logoPreview = document.getElementById('logoPreview');
        if(logoUpload && logoPreview) {
            logoUpload.onchange = function () {
                const [file] = logoUpload.files;
                if (file) {
                    logoPreview.src = URL.createObjectURL(file);
                    logoPreview.style.opacity = '1';
                }
            };
        }
    });
</script>