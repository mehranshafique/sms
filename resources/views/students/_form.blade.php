<form action="{{ isset($student) ? route('students.update', $student->id) : route('students.store') }}" method="POST" id="studentForm" enctype="multipart/form-data">
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
                    <button class="nav-link active" id="official-tab" data-bs-toggle="tab" data-bs-target="#official" type="button">{{ __('student.official_details') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button">{{ __('student.personal_details') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="parents-tab" data-bs-toggle="tab" data-bs-target="#parents" type="button">{{ __('student.parents_guardian') }}</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="identity-tab" data-bs-toggle="tab" data-bs-target="#identity" type="button">{{ __('student.identity_access') }}</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: Official Details -->
                <div class="tab-pane fade show active" id="official">
                    <div class="row">
                        
                        {{-- LOGIC: Auto-Assign vs Select Institute --}}
                        @php
                            // Check if a context ID is actively set in controller (passed via create method)
                            $hasContext = isset($institutionId) && $institutionId;
                            
                            // Super Admin Check (Explicit override logic)
                            $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                        @endphp

                        @if($hasContext && !$isSuperAdmin)
                            {{-- Standard User or Head Officer with Context Set: Hide & Auto-Fill --}}
                            <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                        @else
                            {{-- Super Admin or No Context Set: Show Dropdown --}}
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.select_campus') }}</label>
                            <select name="campus_id" class="form-control default-select">
                                <option value="">{{ __('student.select_campus') }}</option>
                                @foreach($campuses as $id => $name)
                                    <option value="{{ $id }}" {{ (old('campus_id', $student->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        {{-- Academic Year --}}
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.academic_year') }}</label>
                            <input type="text" class="form-control" value="{{ isset($currentSession) ? $currentSession->name : 'N/A' }}" readonly disabled>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.select_class') }} <span class="text-danger">*</span></label>
                            <select name="grade_level_id" id="gradeLevelSelect" class="form-control default-select" required>
                                <option value="">{{ __('student.select_class') }}</option>
                                @foreach($gradeLevels as $id => $name)
                                    <option value="{{ $id }}" {{ (old('grade_level_id', $student->grade_level_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
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
                                   value="{{ old('admission_date', isset($student) ? $student->admission_date->format('Y-m-d') : date('Y-m-d')) }}" 
                                   class="datepicker form-control" placeholder="YYYY-MM-DD" required>
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Personal Details --}}
                <div class="tab-pane fade" id="personal">
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
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.last_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $student->last_name ?? '') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.post_name') }}</label>
                            <input type="text" name="post_name" class="form-control" value="{{ old('post_name', $student->post_name ?? '') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.dob') }} <span class="text-danger">*</span></label>
                            <input type="text" name="dob" 
                                   value="{{ old('dob', isset($student) ? $student->dob->format('Y-m-d') : '') }}" 
                                   class="datepicker form-control" placeholder="YYYY-MM-DD" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.place_of_birth') }} <span class="text-danger">*</span></label>
                            <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $student->place_of_birth ?? '') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.gender') }} <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control default-select" required>
                                <option value="male" {{ (old('gender', $student->gender ?? '') == 'male') ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ (old('gender', $student->gender ?? '') == 'female') ? 'selected' : '' }}>Female</option>
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.province') }} <span class="text-danger">*</span></label>
                            <select name="province" class="form-control default-select" required>
                                <option value="">{{ __('student.select_option') }}</option>
                                <option value="Kinshasa" {{ (old('province', $student->province ?? '') == 'Kinshasa') ? 'selected' : '' }}>Kinshasa</option>
                                <option value="North Kivu" {{ (old('province', $student->province ?? '') == 'North Kivu') ? 'selected' : '' }}>North Kivu</option>
                                <option value="South Kivu" {{ (old('province', $student->province ?? '') == 'South Kivu') ? 'selected' : '' }}>South Kivu</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.avenue') }} <span class="text-danger">*</span></label>
                            <input type="text" name="avenue" class="form-control" value="{{ old('avenue', $student->avenue ?? '') }}" required>
                        </div>
                    </div>
                </div>

                {{-- Tab 3: Parents/Guardian --}}
                <div class="tab-pane fade" id="parents">
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
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.father_phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $student->father_phone ?? '') }}" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mother_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $student->mother_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mother_phone') }}</label>
                            <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $student->mother_phone ?? '') }}">
                        </div>
                    </div>
                </div>

                {{-- Tab 4: Identity --}}
                <div class="tab-pane fade" id="identity">
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
                    <button type="submit" class="btn btn-primary">{{ isset($student) ? __('student.update_student') : __('student.save_student') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Image Preview Logic (Pure JS)
    const imageUpload = document.getElementById('imageUpload');
    if(imageUpload){
        imageUpload.onchange = function (evt) {
            var tgt = evt.target || window.event.srcElement,
                files = tgt.files;

            if (FileReader && files && files.length) {
                var fr = new FileReader();
                fr.onload = function () {
                    var preview = document.getElementById('imagePreview');
                    if(!preview) return;

                    if(preview.tagName === 'IMG') {
                        preview.src = fr.result;
                    } else {
                        var img = document.createElement('img');
                        img.id = 'imagePreview';
                        img.src = fr.result;
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        if(preview.parentNode) {
                            preview.parentNode.replaceChild(img, preview);
                        }
                    }
                }
                fr.readAsDataURL(files[0]);
            }
        }
    }

    // Localization Keys
    const LANG_LOADING = "{{ __('student.loading') }}";
    const LANG_SELECT_OPTION = "{{ __('student.select_option_placeholder') }}";
    const LANG_NO_OPTIONS = "{{ __('student.no_options') }}";
    const LANG_ERROR = "{{ __('student.error_loading') }}";
    const LANG_SELECT_CLASS_FIRST = "{{ __('student.select_class_first') }}";

    // Wait for Window Load to ensure jQuery is available
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                $('#gradeLevelSelect').on('change', function() {
                    let gradeId = $(this).val();
                    let $sectionSelect = $('#sectionSelect'); 
                    
                    // Clear options safely for bootstrap-select compatibility
                    $sectionSelect.html('');
                    
                    if(gradeId) {
                        // Add loading state
                        $sectionSelect.append(`<option value="">${LANG_LOADING}</option>`);
                        $sectionSelect.prop('disabled', true);
                        
                        // Refresh if plugin is active
                        if($.fn.selectpicker) {
                            $sectionSelect.selectpicker('refresh');
                        }

                        $.ajax({
                            url: "{{ route('students.get_sections') }}", 
                            data: { grade_id: gradeId },
                            success: function(data) {
                                $sectionSelect.empty();
                                
                                if($.isEmptyObject(data)) {
                                    $sectionSelect.append(`<option value="">${LANG_NO_OPTIONS}</option>`);
                                    $sectionSelect.prop('disabled', true);
                                } else {
                                    $sectionSelect.append(`<option value="">${LANG_SELECT_OPTION}</option>`);
                                    $.each(data, function(key, value) {
                                        $sectionSelect.append(`<option value="${key}">${value}</option>`);
                                    });
                                    $sectionSelect.prop('disabled', false);
                                }
                                
                                if($.fn.selectpicker) {
                                    $sectionSelect.selectpicker('refresh');
                                }
                            },
                            error: function() {
                                $sectionSelect.empty().append(`<option value="">${LANG_ERROR}</option>`);
                                if($.fn.selectpicker) {
                                    $sectionSelect.selectpicker('refresh');
                                }
                            }
                        });
                    } else {
                        $sectionSelect.empty().append(`<option value="">${LANG_SELECT_CLASS_FIRST}</option>`);
                        $sectionSelect.prop('disabled', true);
                        if($.fn.selectpicker) {
                            $sectionSelect.selectpicker('refresh');
                        }
                    }
                });
            });
        }
    });
</script>