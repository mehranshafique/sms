<!-- Add intl-tel-input CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/css/intlTelInput.css">
<style>
    .iti { width: 100%; display: block; }
    .nav-tabs .nav-link.active { font-weight: bold; border-bottom: 3px solid var(--primary); color: var(--primary); }
    .scan-btn { cursor: pointer; }
    .auto-filled { background-color: #e8f0fe !important; border-color: #4285f4; transition: background-color 0.5s; }
    .parent-status-msg { font-size: 0.85rem; margin-top: 5px; font-weight: 500; }
    /* Hide spin buttons for number inputs */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<form action="{{ isset($student) ? route('students.update', $student->id) : route('students.store') }}" method="POST" id="studentForm" enctype="multipart/form-data" novalidate>
    @csrf
    @if(isset($student))
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header border-bottom">
            <h4 class="card-title">{{ isset($student) ? __('student.edit_student') : __('student.admission_form') }}</h4>
        </div>
        <div class="card-body">
            
            {{-- TABS --}}
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab"><i class="fa fa-user me-2"></i> {{ __('student.personal_details') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="official-tab" data-bs-toggle="tab" data-bs-target="#official" type="button" role="tab"><i class="fa fa-building me-2"></i> {{ __('student.official_details') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="parents-tab" data-bs-toggle="tab" data-bs-target="#parents" type="button" role="tab"><i class="fa fa-users me-2"></i> {{ __('student.parents_guardian') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="identity-tab" data-bs-toggle="tab" data-bs-target="#identity" type="button" role="tab"><i class="fa fa-id-card me-2"></i> {{ __('student.identity_access') }}</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: Personal Details -->
                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                    <div class="row">
                        {{-- Photo Upload --}}
                        <div class="col-md-12 mb-4 text-center">
                            <label class="form-label d-block fw-bold">{{ __('student.photo') }}</label>
                            <div class="avatar-upload d-inline-block position-relative">
                                <div class="position-relative">
                                    <div class="change-btn d-flex align-items-center justify-content-center">
                                        <input type='file' class="form-control d-none" name="student_photo" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                        <label for="imageUpload" class="btn btn-primary btn-sm rounded-circle p-2 mb-0 shadow"><i class="fa fa-camera"></i></label>
                                    </div>
                                    <div class="avatar-preview rounded-circle" style="width: 120px; height: 120px; overflow: hidden; border: 4px solid #f0f0f0; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                        @if(isset($student) && $student->student_photo)
                                            <img id="imagePreview" src="{{ asset('storage/'.$student->student_photo) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div id="imagePreview" style="width: 100%; height: 100%; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa fa-user text-secondary fa-3x"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Personal Identifiers --}}
                        <!-- 1. First Name -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.first_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $student->first_name ?? '') }}" required>
                            <div class="invalid-feedback">{{ __('student.validation_error') }}</div>
                        </div>
                        <!-- 2. Last Name -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.last_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $student->last_name ?? '') }}" required>
                            <div class="invalid-feedback">{{ __('student.validation_error') }}</div>
                        </div>
                        <!-- 3. Post Name -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.post_name') }}</label>
                            <input type="text" name="post_name" class="form-control" value="{{ old('post_name', $student->post_name ?? '') }}">
                        </div>

                        <!-- 4. Place of Birth -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.place_of_birth') }}</label>
                            <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $student->place_of_birth ?? '') }}">
                        </div>
                        <!-- 5. Date of Birth -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.dob') }} <span class="text-danger">*</span></label>
                            <input type="text" name="dob" class="datepicker form-control" value="{{ old('dob', isset($student) ? $student->dob->format('Y-m-d') : '') }}" placeholder="YYYY-MM-DD" required>
                            <div class="invalid-feedback">{{ __('student.validation_error') }}</div>
                        </div>
                        <!-- 6. Gender -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.gender') }} <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control default-select" required>
                                <option value="male" {{ (old('gender', $student->gender ?? '') == 'male') ? 'selected' : '' }}>{{ __('student.male') ?? 'Male' }}</option>
                                <option value="female" {{ (old('gender', $student->gender ?? '') == 'female') ? 'selected' : '' }}>{{ __('student.female') ?? 'Female' }}</option>
                            </select>
                        </div>

                        {{-- Additional Details --}}
                        <!-- 7. Blood Group -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.blood_group') }}</label>
                            <select name="blood_group" class="form-control default-select">
                                <option value="">{{ __('student.select_option') }}</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                                    <option value="{{ $bg }}" {{ (old('blood_group', $student->blood_group ?? '') == $bg) ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- 8. Religion -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.religion') }}</label>
                            <select name="religion" class="form-control default-select">
                                <option value="">{{ __('student.select_option') }}</option>
                                @foreach(['Christian', 'Muslim', 'Hindu', 'Buddhist', 'Other'] as $rel)
                                    <option value="{{ $rel }}" {{ (old('religion', $student->religion ?? '') == $rel) ? 'selected' : '' }}>{{ $rel }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Contact --}}
                        <div class="col-md-12"><hr class="my-3"></div>
                        
                        <!-- 9. Mobile Number -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mobile_no') }}</label>
                            <div class="input-group">
                                <input type="hidden" name="mobile_number" id="hidden_mobile_number" value="{{ old('mobile_number', $student->mobile_number ?? '') }}">
                                <input type="tel" id="mobile_number_input" class="form-control phone-input" value="{{ old('mobile_number', $student->mobile_number ?? '') }}">
                            </div>
                        </div>
                        <!-- 10. Email -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $student->email ?? '') }}" placeholder="student@example.com">
                        </div>

                        {{-- Address --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.country') }}</label>
                            <select name="country" id="countrySelect" class="form-control default-select">
                                <option value="">{{ __('student.select_country') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.state') }}</label>
                            <select name="state" id="stateSelect" class="form-control default-select" disabled>
                                <option value="">{{ __('student.select_state') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.city') }}</label>
                            <select name="city" id="citySelect" class="form-control default-select" disabled>
                                <option value="">{{ __('student.select_city') }}</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('student.avenue_address') }}</label>
                            <input type="text" name="avenue" class="form-control" value="{{ old('avenue', $student->avenue ?? '') }}" placeholder="Street address, Apt, etc.">
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Official Details -->
                <div class="tab-pane fade" id="official" role="tabpanel">
                    <div class="row">
                        @php
                            $hasContext = isset($institutionId) && $institutionId;
                            $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                            $enrollment = isset($student) ? $student->enrollments()->latest()->first() : null;
                            
                            $currentGradeId = old('grade_level_id', $student->grade_level_id ?? $enrollment->classSection->grade_level_id ?? '');
                            $currentSectionId = old('class_section_id', $student->class_section_id ?? $enrollment->class_section_id ?? '');
                            
                            $savedCountry = old('country', $student->country ?? '');
                            $savedState = old('state', $student->state ?? '');
                            $savedCity = old('city', $student->city ?? '');
                        @endphp

                        @if($hasContext && !$isSuperAdmin)
                            <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                        @else
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('student.select_institute') }} <span class="text-danger">*</span></label>
                                <select name="institution_id" class="form-control default-select" required>
                                    <option value="">{{ __('student.select_institute') }}</option>
                                    @foreach($institutes as $id => $name)
                                        <option value="{{ $id }}" {{ (old('institution_id', $student->institution_id ?? ($hasContext ? $institutionId : '')) == $id) ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Campus --}}
                        @if(isset($campuses) && count($campuses) > 0)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.select_campus') }}</label>
                            <select name="campus_id" class="form-control default-select">
                                <option value="">{{ __('student.select_campus') }}</option>
                                @foreach($campuses as $id => $name)
                                    <option value="{{ $id }}" {{ (old('campus_id', $student->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.academic_year') }}</label>
                            <input type="text" class="form-control" value="{{ isset($currentSession) ? $currentSession->name : 'N/A' }}" readonly disabled>
                        </div>

                        {{-- Grade Level (Main) --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.select_class') }} <span class="text-danger">*</span></label>
                            <select name="grade_level_id" id="gradeLevelSelect" class="form-control default-select" required>
                                <option value="">{{ __('student.select_class') }}</option>
                                @foreach($gradeLevels as $id => $name)
                                    <option value="{{ $id }}" {{ ($currentGradeId == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Section (Dependent) --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.select_section') }}</label>
                            <select name="class_section_id" id="sectionSelect" class="form-control default-select" disabled>
                                <option value="">{{ __('student.select_class_first') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.admission_date') }} <span class="text-danger">*</span></label>
                            <input type="text" name="admission_date" 
                                   value="{{ old('admission_date', (isset($student) && $student->admission_date) ? $student->admission_date->format('Y-m-d') : date('Y-m-d')) }}" 
                                   class="datepicker form-control" placeholder="YYYY-MM-DD" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.payment_mode') }}</label>
                            <select name="payment_mode" class="form-control default-select">
                                <option value="installment" {{ (old('payment_mode', $student->payment_mode ?? '') == 'installment') ? 'selected' : '' }}>{{ __('student.payment_installment') }}</option>
                                <option value="global" {{ (old('payment_mode', $student->payment_mode ?? '') == 'global') ? 'selected' : '' }}>{{ __('student.payment_global') }}</option>
                            </select>
                        </div>

                        {{-- Scholarship --}}
                        <div class="col-12 mt-2">
                            <div class="p-3 border rounded bg-light">
                                <h5 class="text-primary mb-3"><i class="fa fa-percent me-2"></i> {{ __('student.scholarship_discount') }}</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('student.discount_amount') }}</label>
                                        <input type="number" name="discount_amount" class="form-control" 
                                               value="{{ old('discount_amount', $enrollment->discount_amount ?? 0) }}" 
                                               min="0" step="0.01" placeholder="0.00">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('student.discount_type') }}</label>
                                        <select name="discount_type" class="form-control default-select">
                                            <option value="fixed" {{ (old('discount_type', $enrollment->discount_type ?? '') == 'fixed') ? 'selected' : '' }}>{{ __('student.fixed_amount') }}</option>
                                            <option value="percentage" {{ (old('discount_type', $enrollment->discount_type ?? '') == 'percentage') ? 'selected' : '' }}>{{ __('student.percentage') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('student.reason_remark') }}</label>
                                        <input type="text" name="scholarship_reason" class="form-control" 
                                               value="{{ old('scholarship_reason', $enrollment->scholarship_reason ?? '') }}" 
                                               placeholder="{{ __('student.reason_placeholder') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Parents/Guardian -->
                <div class="tab-pane fade" id="parents" role="tabpanel">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="alert alert-primary light border-primary shadow-sm">
                                <i class="fa fa-info-circle me-2 fs-5"></i> 
                                <strong>Auto-Fetch:</strong> Enter a <u>Phone Number</u> or <u>Email</u> to automatically load existing parent details.
                            </div>
                        </div>

                        {{-- Father --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.father_name') }}</label>
                            <input type="text" name="father_name" id="father_name" class="form-control" value="{{ old('father_name', $student->parent->father_name ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.father_phone') }}</label>
                            <div class="input-group">
                                <input type="hidden" name="father_phone" id="hidden_father_phone" value="{{ old('father_phone', $student->parent->father_phone ?? '') }}">
                                <input type="tel" id="father_phone_input" class="form-control phone-input parent-lookup" data-type="father" value="{{ old('father_phone', $student->parent->father_phone ?? '') }}">
                            </div>
                            <div id="father_status" class="parent-status-msg d-none"></div>
                        </div>

                        {{-- Mother --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.mother_name') }}</label>
                            <input type="text" name="mother_name" id="mother_name" class="form-control" value="{{ old('mother_name', $student->parent->mother_name ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.mother_phone') }}</label>
                            <div class="input-group">
                                <input type="hidden" name="mother_phone" id="hidden_mother_phone" value="{{ old('mother_phone', $student->parent->mother_phone ?? '') }}">
                                <input type="tel" id="mother_phone_input" class="form-control phone-input parent-lookup" data-type="mother" value="{{ old('mother_phone', $student->parent->mother_phone ?? '') }}">
                            </div>
                            <div id="mother_status" class="parent-status-msg d-none"></div>
                        </div>

                        <div class="col-md-12 my-3"><hr class="border-secondary opacity-25"></div>

                        {{-- Guardian --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.guardian_name') }}</label>
                            <input type="text" name="guardian_name" id="guardian_name" class="form-control" value="{{ old('guardian_name', $student->parent->guardian_name ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.guardian_email') }}</label>
                            <input type="email" name="guardian_email" id="guardian_email" class="form-control parent-lookup-email" value="{{ old('guardian_email', $student->parent->guardian_email ?? '') }}" placeholder="For login access">
                            <div id="guardian_email_status" class="parent-status-msg d-none"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.guardian_phone') }}</label>
                            <div class="input-group">
                                <input type="hidden" name="guardian_phone" id="hidden_guardian_phone" value="{{ old('guardian_phone', $student->parent->guardian_phone ?? '') }}">
                                <input type="tel" id="guardian_phone_input" class="form-control phone-input parent-lookup" data-type="guardian" value="{{ old('guardian_phone', $student->parent->guardian_phone ?? '') }}">
                            </div>
                            <div id="guardian_status" class="parent-status-msg d-none"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('student.primary_guardian') }} <span class="text-danger">*</span></label>
                            <select name="primary_guardian" class="form-control default-select" required>
                                <option value="father" {{ (old('primary_guardian') == 'father') ? 'selected' : '' }}>{{ __('student.father_name') }}</option>
                                <option value="mother" {{ (old('primary_guardian') == 'mother') ? 'selected' : '' }}>{{ __('student.mother_name') }}</option>
                                <option value="guardian" {{ (old('primary_guardian') == 'guardian') ? 'selected' : '' }}>{{ __('student.guardian_name') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Identity -->
                <div class="tab-pane fade" id="identity" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.nfc_tag_uid') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-wifi"></i></span>
                                <input type="text" name="nfc_tag_uid" id="nfc_input" class="form-control" value="{{ old('nfc_tag_uid', $student->nfc_tag_uid ?? '') }}" placeholder="{{ __('student.enter_nfc_uid') }}" autocomplete="off">
                                <button class="btn btn-secondary" type="button" id="btnScanNFC"><i class="fa fa-mobile me-1"></i> Scan</button>
                            </div>
                            <small class="text-muted d-block mt-1">Tap card on reader (Desktop) or click Scan (Mobile).</small>
                            <div id="nfcStatus" class="small mt-1 text-info d-none"><i class="fa fa-spinner fa-spin"></i> Scanning... Tap card on back of phone.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.qr_code_token') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-qrcode"></i></span>
                                <input type="text" name="qr_code_token" id="qr_token" class="form-control" value="{{ old('qr_code_token', $student->qr_code_token ?? '') }}" placeholder="{{ __('student.enter_qr_token') }}">
                                <button class="btn btn-outline-primary" type="button" onclick="generateQR()"><i class="fa fa-refresh me-1"></i> Generate</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary btn-lg shadow">
                        <i class="fa fa-save me-2"></i> {{ isset($student) ? __('student.update_student') : __('student.save_student') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/intlTelInput.min.js"></script>

<script>
    // --- LOCALIZED CONSTANTS (Safely escaped via json to prevent Syntax Errors)
   
     const LANG = {
        loading: @json(__('student.loading')),
        selectOption: @json(__('student.select_option')),
        selectClassFirst: @json(__('student.select_class_first')),
        noOptions: @json(__('student.no_options')),
        errorLoading: @json(__('student.error_loading')),
        successTitle: @json(__('student.messages.success')),
        errorTitle: @json(__('student.messages.error')),
        btnOk: @json(__('student.ok')),
        linkedTo: @json(__('student.linked_to')),
        parentFound: @json(__('student.parent_found')),
        recordsAutofilled: @json(__('student.records_autofilled')),
        notSupported: @json(__('student.not_supported')),
        webNfcNotSupported: @json(__('student.web_nfc_not_supported')),
        scanned: @json(__('student.scanned')),
        nfcReadError: @json(__('student.nfc_read_error')),
        selectCountry: @json(__('student.select_country')),
        selectState: @json(__('student.select_state')),
        selectCity: @json(__('student.select_city')),
        validationError: @json(__('student.validation_error')),
        checkForm: @json(__('student.messages.check_form')),
        errorOccurred: @json(__('student.error_occurred')),
        somethingWentWrong: @json(__('student.something_went_wrong'))
    };

    // --- HELPER FUNCTIONS ---
    function generateQR() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < 16; i++) result += chars.charAt(Math.floor(Math.random() * chars.length));
        const token = result.match(/.{1,4}/g).join('-');
        document.getElementById('qr_token').value = token;
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 0. Tab Switching for HTML5 Validation ---
        // If a required field is empty in a hidden tab, the browser triggers 'invalid'.
        // We catch it and switch to that tab so the user can see what's missing.
        document.getElementById('studentForm').addEventListener('invalid', function(e) {
            let invalidInput = e.target;
            let pane = invalidInput.closest('.tab-pane');
            if (pane) {
                let tabId = pane.id;
                let tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                if (tabButton && !tabButton.classList.contains('active')) {
                    tabButton.click(); // Switch to the specific tab
                    
                    // Small delay to allow the tab transition before focusing
                    setTimeout(() => invalidInput.focus(), 100);
                }
            }
        }, true); // Capture phase is required because 'invalid' events do not bubble up natively

        // --- 1. Phone Input Setup ---
        const phoneOptions = {
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js",
            initialCountry: "cd",
            separateDialCode: true,
            showSelectedDialCode: true, // Specific for v20+ compatibility
            countrySearch: true, // Automatically provides the search field inside the dropdown
            preferredCountries: ['cd', 'us', 'fr'],
        };

        const inputs = [
            { input: document.querySelector("#mobile_number_input"), hidden: document.querySelector("#hidden_mobile_number") },
            { input: document.querySelector("#father_phone_input"), hidden: document.querySelector("#hidden_father_phone"), type: 'father' },
            { input: document.querySelector("#mother_phone_input"), hidden: document.querySelector("#hidden_mother_phone"), type: 'mother' },
            { input: document.querySelector("#guardian_phone_input"), hidden: document.querySelector("#hidden_guardian_phone"), type: 'guardian' }
        ];

        inputs.forEach(item => {
            if (item.input) {
                const iti = window.intlTelInput(item.input, phoneOptions);
                item.instance = iti; // Store instance for later use
                
                // Update on blur (Legacy fallback)
                item.input.addEventListener('blur', function() {
                    if (iti.isValidNumber()) {
                        const number = iti.getNumber();
                        if(item.hidden) item.hidden.value = number;
                        if(item.type) checkParent(number, item.type, 'phone');
                    }
                });
            }
        });

        // --- 2. Email Lookup ---
        const emailInput = document.querySelector('.parent-lookup-email');
        if(emailInput) {
            emailInput.addEventListener('blur', function() {
                if(this.value && this.value.includes('@')) {
                    checkParent(this.value, 'guardian', 'email');
                }
            });
        }

        // --- 3. AJAX CHECK PARENT ---
        function checkParent(value, type, method) {
            const url = "{{ route('parents.check') }}"; 
            const params = new URLSearchParams();
            if(method === 'email') params.append('email', value);
            else params.append('phone', value);

            fetch(`${url}?${params.toString()}`, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest', 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.exists) {
                    fillParentData(data);
                    const statusId = type === 'guardian' && method === 'email' ? 'guardian_email_status' : type + '_status';
                    const statusDiv = document.getElementById(statusId);
                    if(statusDiv) {
                        statusDiv.innerHTML = `<span class="text-success"><i class="fa fa-check-circle"></i> ${LANG.linkedTo} ${data.name}</span>`;
                        statusDiv.classList.remove('d-none');
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: LANG.parentFound,
                        text: LANG.recordsAutofilled + " " + data.name,
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            })
            .catch(err => console.error('Lookup Error:', err));
        }

        function fillParentData(data) {
            const map = {
                'father_name': data.father_name,
                'father_phone_input': data.father_phone,
                'hidden_father_phone': data.father_phone,
                'mother_name': data.mother_name,
                'mother_phone_input': data.mother_phone,
                'hidden_mother_phone': data.mother_phone,
                'guardian_name': data.guardian_name,
                'guardian_email': data.guardian_email,
                'guardian_phone_input': data.guardian_phone,
                'hidden_guardian_phone': data.guardian_phone,
            };

            for (const [id, val] of Object.entries(map)) {
                const field = document.getElementById(id);
                if (field && val) {
                    field.value = val;
                    field.classList.add('auto-filled');
                }
            }
        }

        // --- 4. NFC Reader Logic ---
        const nfcInput = document.getElementById('nfc_input');
        const btnScanNFC = document.getElementById('btnScanNFC');
        const nfcStatus = document.getElementById('nfcStatus');

        if (nfcInput) {
            nfcInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault(); 
                    this.blur(); 
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'NFC Tag Scanned',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        }

        if (btnScanNFC) {
            btnScanNFC.addEventListener('click', async () => {
                if (!('NDEFReader' in window)) {
                    Swal.fire(LANG.notSupported, LANG.webNfcNotSupported, 'warning');
                    return;
                }
                try {
                    const ndef = new NDEFReader();
                    await ndef.scan();
                    if(nfcStatus) nfcStatus.classList.remove('d-none');
                    btnScanNFC.disabled = true;
                    btnScanNFC.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                    ndef.onreading = event => {
                        const serialNumber = event.serialNumber;
                        nfcInput.value = serialNumber;
                        if(nfcStatus) nfcStatus.classList.add('d-none');
                        btnScanNFC.disabled = false;
                        btnScanNFC.innerHTML = '<i class="fa fa-mobile me-1"></i> Scan';
                        Swal.fire({
                            icon: 'success',
                            title: LANG.scanned,
                            text: 'UID: ' + serialNumber,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    };
                    ndef.onreadingerror = () => {
                        Swal.fire(LANG.errorTitle, LANG.nfcReadError, 'error');
                        if(nfcStatus) nfcStatus.classList.add('d-none');
                        btnScanNFC.disabled = false;
                        btnScanNFC.innerHTML = '<i class="fa fa-mobile me-1"></i> Scan';
                    };
                } catch (error) {
                    Swal.fire(LANG.errorTitle, 'NFC Scan failed: ' + error, 'error');
                    btnScanNFC.disabled = false;
                    btnScanNFC.innerHTML = '<i class="fa fa-mobile me-1"></i> Scan';
                }
            });
        }

        // --- 5. DROPDOWNS (Native Fetch) ---
        function refreshSelect(element) {
            if (typeof $ !== 'undefined' && $(element).is('select')) {
                if ($.fn.selectpicker) {
                     $(element).selectpicker('refresh');
                } else if($.fn.niceSelect) {
                    $(element).niceSelect('update');
                }
            }
        }

        function triggerChangeEvent(element) {
            element.dispatchEvent(new Event('change'));
        }

        // LOCATIONS
        const countrySelect = document.getElementById('countrySelect');
        const stateSelect = document.getElementById('stateSelect');
        const citySelect = document.getElementById('citySelect');
        const savedCountry = "{{ $savedCountry ?? '' }}";
        const savedState = "{{ $savedState ?? '' }}";
        const savedCity = "{{ $savedCity ?? '' }}";

        if (countrySelect) {
            fetch("{{ route('locations.countries') }}")
                .then(res => res.json())
                .then(data => {
                    countrySelect.innerHTML = `<option value="">${LANG.selectCountry}</option>`;
                    data.forEach(item => {
                        let option = new Option(item.name, item.id);
                        if(String(item.id) === String(savedCountry)) option.selected = true;
                        countrySelect.add(option);
                    });
                    refreshSelect(countrySelect);
                    if(savedCountry) triggerChangeEvent(countrySelect);
                });

            countrySelect.addEventListener('change', function() {
                stateSelect.innerHTML = `<option value="">${LANG.loading}</option>`;
                stateSelect.disabled = true;
                citySelect.innerHTML = `<option value="">${LANG.selectCity}</option>`;
                citySelect.disabled = true;
                refreshSelect(stateSelect);
                refreshSelect(citySelect);

                if(this.value) {
                    fetch(`{{ route('locations.states') }}?country_id=${this.value}`)
                        .then(res => res.json())
                        .then(data => {
                            stateSelect.innerHTML = `<option value="">${LANG.selectState}</option>`;
                            data.forEach(item => {
                                let option = new Option(item.name, item.id);
                                if(String(item.id) === String(savedState)) option.selected = true;
                                stateSelect.add(option);
                            });
                            stateSelect.disabled = false;
                            refreshSelect(stateSelect);
                            if(savedState) triggerChangeEvent(stateSelect);
                        });
                } else {
                    stateSelect.innerHTML = `<option value="">${LANG.selectState}</option>`;
                    refreshSelect(stateSelect);
                }
            });
        }

        if (stateSelect) {
            stateSelect.addEventListener('change', function() {
                citySelect.innerHTML = `<option value="">${LANG.loading}</option>`;
                citySelect.disabled = true;
                refreshSelect(citySelect);

                if(this.value) {
                    fetch(`{{ route('locations.cities') }}?state_id=${this.value}`)
                        .then(res => res.json())
                        .then(data => {
                            citySelect.innerHTML = `<option value="">${LANG.selectCity}</option>`;
                            data.forEach(item => {
                                let option = new Option(item.name, item.id);
                                if(String(item.id) === String(savedCity)) option.selected = true;
                                citySelect.add(option);
                            });
                            citySelect.disabled = false;
                            refreshSelect(citySelect);
                        });
                } else {
                    citySelect.innerHTML = `<option value="">${LANG.selectCity}</option>`;
                    refreshSelect(citySelect);
                }
            });
        }

        // ACADEMIC (Grade -> Section)
        const gradeSelect = document.getElementById('gradeLevelSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const savedSectionId = "{{ $currentSectionId }}";

        if(gradeSelect) {
            gradeSelect.addEventListener('change', function() {
                sectionSelect.innerHTML = `<option value="">${LANG.loading}</option>`;
                sectionSelect.disabled = true;
                refreshSelect(sectionSelect);

                if(this.value) {
                    fetch(`{{ route('students.get_sections') }}?grade_id=${this.value}`)
                        .then(res => res.json())
                        .then(data => {
                            sectionSelect.innerHTML = `<option value="">${LANG.selectOption}</option>`;
                            Object.entries(data).forEach(([id, name]) => {
                                let option = new Option(name, id);
                                if(String(id) === String(savedSectionId)) option.selected = true;
                                sectionSelect.add(option);
                            });
                            sectionSelect.disabled = (Object.keys(data).length === 0);
                            if(sectionSelect.disabled) sectionSelect.innerHTML = `<option value="">${LANG.noOptions}</option>`;
                            refreshSelect(sectionSelect);
                        });
                } else {
                    sectionSelect.innerHTML = `<option value="">${LANG.selectClassFirst}</option>`;
                    refreshSelect(sectionSelect);
                }
            });

            if(gradeSelect.value) {
                triggerChangeEvent(gradeSelect);
            }
        }

        // --- 6. Image Preview ---
        const imageUpload = document.getElementById('imageUpload');
        if(imageUpload){
            imageUpload.onchange = function (evt) {
                const [file] = imageUpload.files;
                if (file) {
                    const preview = document.getElementById('imagePreview');
                    if(preview) {
                        if(preview.tagName === 'IMG') {
                            preview.src = URL.createObjectURL(file);
                        } else {
                            const img = document.createElement('img');
                            img.id = 'imagePreview';
                            img.src = URL.createObjectURL(file);
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'cover';
                            preview.parentNode.replaceChild(img, preview);
                        }
                    }
                }
            }
        }

        // --- 7. Submit Handler ---
        $('#studentForm').on('submit', function(e) {
            e.preventDefault();
            let form = this;
            
            // Force update hidden fields from intl-tel-input before FormData creation
            inputs.forEach(item => {
                if (item.input && item.hidden && item.instance) {
                    if (item.instance.isValidNumber()) {
                        item.hidden.value = item.instance.getNumber();
                    } else {
                         // Fallback to raw value if invalid or empty to let server validate
                         item.hidden.value = item.instance.getNumber() || item.input.value;
                    }
                }
            });

            let formData = new FormData(form);
            let btn = $(form).find('button[type="submit"]');
            let originalBtnHtml = btn.html();

            $('.invalid-feedback').remove();
            $('.is-invalid').removeClass('is-invalid');

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: $(form).attr('action'),
                method: $(form).attr('method'),
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    btn.prop('disabled', false).html(originalBtnHtml);
                    if (response.redirect) {
                        Swal.fire({
                            icon: 'success',
                            title: LANG.successTitle,
                            text: response.message,
                            showConfirmButton: true,
                            confirmButtonText: LANG.btnOk
                        }).then((result) => {
                            if (result.isConfirmed || result.isDismissed) {
                                window.location.href = response.redirect;
                            }
                        });
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalBtnHtml);
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        Swal.fire({
                            icon: 'error',
                            title: LANG.validationError,
                            text: LANG.checkForm,
                        });
                        
                        let firstErrorInput = null;
                        
                        $.each(errors, function(field, messages) {
                            let input = $('[name="' + field + '"]');
                            if(input.length) {
                                // Track the very first error encountered to snap to that tab
                                if(!firstErrorInput) firstErrorInput = input;
                                
                                input.addClass('is-invalid');
                                if(input.next('.invalid-feedback').length === 0) {
                                    input.after('<div class="invalid-feedback d-block">' + messages[0] + '</div>');
                                }
                            }
                        });
                        
                        // Switch to the tab containing the server-side validation error
                        if(firstErrorInput) {
                            let pane = firstErrorInput.closest('.tab-pane');
                            if (pane && pane.length) {
                                let tabId = pane.attr('id');
                                let tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                                if (tabButton && !tabButton.classList.contains('active')) {
                                    tabButton.click();
                                }
                                setTimeout(() => firstErrorInput.focus(), 100);
                            }
                        }
                        
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: LANG.errorOccurred,
                            text: xhr.responseJSON?.message || LANG.somethingWentWrong,
                        });
                    }
                }
            });
        });
    });
</script>