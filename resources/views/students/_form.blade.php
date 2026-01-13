<form action="{{ isset($student) ? route('students.update', $student->id) : route('students.store') }}" method="POST" id="studentForm" enctype="multipart/form-data" novalidate>
    @csrf
    @if(isset($student))
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">{{ __('student.admission_form') }}</h4>
        </div>
        <div class="card-body">
            
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="official-tab" data-bs-toggle="tab" data-bs-target="#official" type="button" role="tab" aria-controls="official" aria-selected="true">{{ __('student.official_details') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="false">{{ __('student.personal_details') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="parents-tab" data-bs-toggle="tab" data-bs-target="#parents" type="button" role="tab" aria-controls="parents" aria-selected="false">{{ __('student.parents_guardian') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="identity-tab" data-bs-toggle="tab" data-bs-target="#identity" type="button" role="tab" aria-controls="identity" aria-selected="false">{{ __('student.identity_access') }}</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: Official Details -->
                <div class="tab-pane fade show active" id="official" role="tabpanel" aria-labelledby="official-tab">
                    <div class="row">
                        
                        @php
                            $hasContext = isset($institutionId) && $institutionId;
                            $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                            $enrollment = isset($student) ? $student->enrollments()->latest()->first() : null;
                            
                            // Safe Accessors for Edit Mode
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
                                <div class="invalid-feedback">Please select an institute.</div>
                            </div>
                        @endif

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.select_campus') }}</label>
                            <select name="campus_id" class="form-control default-select">
                                <option value="">{{ __('student.select_campus') }}</option>
                                @foreach($campuses as $id => $name)
                                    <option value="{{ $id }}" {{ (old('campus_id', $student->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.academic_year') }}</label>
                            <input type="text" class="form-control" value="{{ isset($currentSession) ? $currentSession->name : 'N/A' }}" readonly disabled>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.select_class') }} <span class="text-danger">*</span></label>
                            <select name="grade_level_id" id="gradeLevelSelect" class="form-control default-select" required>
                                <option value="">{{ __('student.select_class') }}</option>
                                @foreach($gradeLevels as $id => $name)
                                    <option value="{{ $id }}" {{ ($currentGradeId == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a class.</div>
                        </div>

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
                            <div class="invalid-feedback">Admission date is required.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.payment_mode') }}</label>
                            <select name="payment_mode" class="form-control default-select">
                                <option value="installment" {{ (old('payment_mode', $student->payment_mode ?? '') == 'installment') ? 'selected' : '' }}>{{ __('student.payment_installment') }}</option>
                                <option value="global" {{ (old('payment_mode', $student->payment_mode ?? '') == 'global') ? 'selected' : '' }}>{{ __('student.payment_global') }}</option>
                            </select>
                        </div>

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

                <!-- Tab 2: Personal Details -->
                <div class="tab-pane fade" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <div class="row">
                        <div class="col-md-12 mb-3 text-center">
                            <label class="form-label d-block">{{ __('student.photo') }}</label>
                            <div class="avatar-upload d-inline-block position-relative">
                                <div class="position-relative">
                                    <div class="change-btn d-flex align-items-center justify-content-center">
                                        <input type='file' class="form-control d-none" name="student_photo" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                        <label for="imageUpload" class="btn btn-primary btn-sm rounded-circle p-2 mb-0"><i class="fa fa-camera"></i></label>
                                    </div>
                                    <div class="avatar-preview rounded-circle" style="width: 100px; height: 100px; overflow: hidden; border: 3px solid var(--border-color);">
                                        @if(isset($student) && $student->student_photo)
                                            <img id="imagePreview" src="{{ asset('storage/'.$student->student_photo) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div id="imagePreview" style="width: 100%; height: 100%; background: #e1e1e1; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa fa-user text-white fa-2x"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.first_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $student->first_name ?? '') }}" required>
                            <div class="invalid-feedback">First name is required.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.last_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $student->last_name ?? '') }}" required>
                            <div class="invalid-feedback">Last name is required.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.post_name') }}</label>
                            <input type="text" name="post_name" class="form-control" value="{{ old('post_name', $student->post_name ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.dob') }} <span class="text-danger">*</span></label>
                            <input type="text" name="dob" 
                                   value="{{ old('dob', (isset($student) && $student->dob) ? $student->dob->format('Y-m-d') : '') }}" 
                                   class="datepicker form-control" placeholder="YYYY-MM-DD" required>
                            <div class="invalid-feedback">Date of birth is required.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.place_of_birth') }} <span class="text-danger">*</span></label>
                            <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $student->place_of_birth ?? '') }}" required>
                             <div class="invalid-feedback">Place of birth is required.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.gender') }} <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control default-select" required>
                                <option value="male" {{ (old('gender', $student->gender ?? '') == 'male') ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ (old('gender', $student->gender ?? '') == 'female') ? 'selected' : '' }}>Female</option>
                            </select>
                             <div class="invalid-feedback">Gender is required.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.religion') }}</label>
                            <select name="religion" class="form-control default-select">
                                <option value="">{{ __('student.select_option') }}</option>
                                @foreach(['Christian', 'Muslim', 'Hindu', 'Buddhist', 'Other'] as $rel)
                                    <option value="{{ $rel }}" {{ (old('religion', $student->religion ?? '') == $rel) ? 'selected' : '' }}>{{ $rel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.blood_group') }}</label>
                            <select name="blood_group" class="form-control default-select">
                                <option value="">{{ __('student.select_option') }}</option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                                    <option value="{{ $bg }}" {{ (old('blood_group', $student->blood_group ?? '') == $bg) ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $student->email ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mobile_no') }}</label>
                            <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $student->mobile_number ?? '') }}">
                        </div>

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
                            <label class="form-label">{{ __('student.avenue_address') }} <span class="text-danger">*</span></label>
                            <input type="text" name="avenue" class="form-control" value="{{ old('avenue', $student->avenue ?? '') }}" required>
                             <div class="invalid-feedback">Address is required.</div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Parents/Guardian -->
                <div class="tab-pane fade" id="parents" role="tabpanel" aria-labelledby="parents-tab">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('student.primary_guardian') }} <span class="text-danger">*</span></label>
                            <select name="primary_guardian" class="form-control default-select" required>
                                <option value="father" {{ (old('primary_guardian', $student->primary_guardian ?? '') == 'father') ? 'selected' : '' }}>{{ __('student.father_name') }}</option>
                                <option value="mother" {{ (old('primary_guardian', $student->primary_guardian ?? '') == 'mother') ? 'selected' : '' }}>{{ __('student.mother_name') }}</option>
                                <option value="guardian" {{ (old('primary_guardian', $student->primary_guardian ?? '') == 'guardian') ? 'selected' : '' }}>{{ __('student.guardian_name') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.father_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $student->father_name ?? '') }}" required>
                             <div class="invalid-feedback">Father name is required.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.father_phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $student->father_phone ?? '') }}" required>
                             <div class="invalid-feedback">Phone is required.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mother_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $student->mother_name ?? '') }}" required>
                             <div class="invalid-feedback">Mother name is required.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mother_phone') }}</label>
                            <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $student->mother_phone ?? '') }}">
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Identity -->
                <div class="tab-pane fade" id="identity" role="tabpanel" aria-labelledby="identity-tab">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.nfc_tag_uid') }}</label>
                            <div class="input-group">
                                <input type="text" name="nfc_tag_uid" class="form-control" value="{{ old('nfc_tag_uid', $student->nfc_tag_uid ?? '') }}" placeholder="{{ __('student.enter_nfc_uid') }}">
                                <span class="input-group-text"><i class="fa fa-id-card"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.qr_code_token') }}</label>
                            <input type="text" name="qr_code_token" class="form-control" value="{{ old('qr_code_token', $student->qr_code_token ?? '') }}" placeholder="{{ __('student.enter_qr_token') }}">
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" id="saveStudentBtn">{{ isset($student) ? __('student.update_student') : __('student.save_student') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- REQUIRED LIBRARY: SWEETALERT2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- CENTRALIZED JAVASCRIPT LOGIC --}}
<script>
    // 1. Localization Keys
    const LANG_LOADING = "{{ __('student.loading') }}";
    const LANG_SELECT_OPTION = "{{ __('student.select_option_placeholder') }}";
    const LANG_NO_OPTIONS = "{{ __('student.no_options') }}";
    const LANG_ERROR = "{{ __('student.error_loading') }}";
    const LANG_SELECT_CLASS_FIRST = "{{ __('student.select_class_first') }}";

    // 2. Saved Values from Controller (For Edit/Error State)
    const savedGradeId = "{{ $currentGradeId }}";
    const savedSectionId = "{{ $currentSectionId }}";
    const savedCountry = "{{ $savedCountry }}";
    const savedState = "{{ $savedState }}";
    const savedCity = "{{ $savedCity }}";

    // 3. Image Preview Logic
    const imageUpload = document.getElementById('imageUpload');
    if(imageUpload){
        imageUpload.onchange = function (evt) {
            var tgt = evt.target || window.event.srcElement, files = tgt.files;
            if (FileReader && files && files.length) {
                var fr = new FileReader();
                fr.onload = function () {
                    var preview = document.getElementById('imagePreview');
                    if(preview) {
                        if(preview.tagName === 'IMG') {
                            preview.src = fr.result;
                        } else {
                            var img = document.createElement('img');
                            img.id = 'imagePreview';
                            img.src = fr.result;
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'cover';
                            if(preview.parentNode) preview.parentNode.replaceChild(img, preview);
                        }
                    }
                }
                fr.readAsDataURL(files[0]);
            }
        }
    }

    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                
                // --- A. VALIDATION & SUBMIT HANDLER ---
                $('#studentForm').on('submit', function(e) {
                    // Prevent Standard Submission Immediately
                    e.preventDefault(); 

                    // Browser Validity Check
                    if (!this.checkValidity()) {
                        e.stopPropagation();
                        $(this).addClass('was-validated');

                        // Find first invalid field & Switch Tab
                        let $invalid = $(this).find(':invalid').first();
                        let $tabPane = $invalid.closest('.tab-pane');
                        let tabId = $tabPane.attr('id');
                        
                        if(tabId) {
                            let triggerEl = document.querySelector(`button[data-bs-target="#${tabId}"]`);
                            if(triggerEl) bootstrap.Tab.getOrCreateInstance(triggerEl).show();
                        }
                        $invalid.focus();

                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("student.validation_error") }}',
                            text: '{{ __("student.fill_required_fields") }}'
                        });
                        return; // STOP EXECUTION
                    }

                    // Proceed to AJAX Submission
                    let formData = new FormData(this);
                    $.ajax({
                        url: $(this).attr('action'),
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        success: function(response){
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("student.success") }}',
                                text: response.message
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        },
                        error: function(xhr){
                            let msg = '{{ __("student.error_occurred") }}';
                            if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                            if(xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("student.validation_error") }}',
                                html: msg
                            });
                        }
                    });
                });

                // --- B. CLASS SECTIONS LOGIC ---
                function loadSections(gradeId, selectedId = null) {
                    let $sectionSelect = $('#sectionSelect'); 
                    $sectionSelect.html(`<option value="">${LANG_LOADING}</option>`).prop('disabled', true);
                    if($.fn.selectpicker) $sectionSelect.selectpicker('refresh');

                    $.ajax({
                        url: "{{ route('students.get_sections') }}", 
                        data: { grade_id: gradeId },
                        success: function(data) {
                            $sectionSelect.empty();
                            if($.isEmptyObject(data)) {
                                $sectionSelect.append(`<option value="">${LANG_NO_OPTIONS}</option>`);
                            } else {
                                $sectionSelect.append(`<option value="">${LANG_SELECT_OPTION}</option>`);
                                $.each(data, function(key, value) {
                                    let isSelected = (selectedId && selectedId == key) ? 'selected' : '';
                                    $sectionSelect.append(`<option value="${key}" ${isSelected}>${value}</option>`);
                                });
                                $sectionSelect.prop('disabled', false);
                            }
                            if($.fn.selectpicker) $sectionSelect.selectpicker('refresh');
                        },
                        error: function() {
                            $sectionSelect.empty().append(`<option value="">${LANG_ERROR}</option>`);
                            if($.fn.selectpicker) $sectionSelect.selectpicker('refresh');
                        }
                    });
                }

                $('#gradeLevelSelect').change(function() {
                    let gradeId = $(this).val();
                    if(gradeId) loadSections(gradeId);
                    else {
                        $('#sectionSelect').html(`<option value="">${LANG_SELECT_CLASS_FIRST}</option>`).prop('disabled', true);
                        if($.fn.selectpicker) $('#sectionSelect').selectpicker('refresh');
                    }
                });

                // Init Sections
                if(savedGradeId) loadSections(savedGradeId, savedSectionId);


                // --- C. LOCATION LOGIC (Country > State > City) ---
                const $country = $('#countrySelect');
                const $state = $('#stateSelect');
                const $city = $('#citySelect');

                // 1. Load Countries
                $.get("{{ route('locations.countries') }}", function(data) {
                    $country.empty().append(`<option value="">${LANG_SELECT_OPTION}</option>`);
                    $.each(data, function(i, item) {
                        let selected = (savedCountry && savedCountry == item.name) ? 'selected' : '';
                        $country.append(`<option value="${item.name}" data-id="${item.id}" ${selected}>${item.name}</option>`);
                    });
                    if($.fn.selectpicker) $country.selectpicker('refresh');
                    
                    // Trigger cascade if saved value exists
                    if(savedCountry) $country.trigger('change');
                });

                // 2. Load States
                $country.change(function() {
                    let countryId = $(this).find(':selected').data('id');
                    $state.empty().append(`<option value="">${LANG_LOADING}</option>`).prop('disabled', true);
                    $city.empty().append(`<option value="">${LANG_SELECT_OPTION}</option>`).prop('disabled', true);
                    if($.fn.selectpicker) { $state.selectpicker('refresh'); $city.selectpicker('refresh'); }

                    if(countryId) {
                        $.get("{{ route('locations.states') }}", { country_id: countryId }, function(data) {
                            $state.empty().append(`<option value="">${LANG_SELECT_OPTION}</option>`);
                            $.each(data, function(i, item) {
                                let selected = (savedState && savedState == item.name) ? 'selected' : '';
                                $state.append(`<option value="${item.name}" data-id="${item.id}" ${selected}>${item.name}</option>`);
                            });
                            $state.prop('disabled', false);
                            if($.fn.selectpicker) $state.selectpicker('refresh');
                            
                            // Trigger cascade if saved value exists AND matches current parent
                            if(savedState && $state.find('option[selected]').length > 0) {
                                $state.trigger('change');
                            }
                        });
                    } else {
                        $state.html(`<option value="">${LANG_SELECT_OPTION}</option>`);
                        if($.fn.selectpicker) $state.selectpicker('refresh');
                    }
                });

                // 3. Load Cities
                $state.change(function() {
                    let stateId = $(this).find(':selected').data('id');
                    $city.empty().append(`<option value="">${LANG_LOADING}</option>`).prop('disabled', true);
                    
                    if(stateId) {
                        $.get("{{ route('locations.cities') }}", { state_id: stateId }, function(data) {
                            $city.empty().append(`<option value="">${LANG_SELECT_OPTION}</option>`);
                            $.each(data, function(i, item) {
                                let selected = (savedCity && savedCity == item.name) ? 'selected' : '';
                                $city.append(`<option value="${item.name}" ${selected}>${item.name}</option>`);
                            });
                            $city.prop('disabled', false);
                            if($.fn.selectpicker) $city.selectpicker('refresh');
                        });
                    } else {
                        $city.html(`<option value="">${LANG_SELECT_OPTION}</option>`);
                        if($.fn.selectpicker) $city.selectpicker('refresh');
                    }
                });

            });
        }
    });
</script>