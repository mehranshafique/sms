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
            
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="official-tab" data-bs-toggle="tab" data-bs-target="#official" type="button" role="tab">{{ __('student.official_details') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">{{ __('student.personal_details') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="parents-tab" data-bs-toggle="tab" data-bs-target="#parents" type="button" role="tab">{{ __('student.parents_guardian') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="identity-tab" data-bs-toggle="tab" data-bs-target="#identity" type="button" role="tab">Identity & Access</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: Official Details -->
                <div class="tab-pane fade show active" id="official" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.select_institute') }} <span class="text-danger">*</span></label>
                            <select name="institution_id" class="form-control default-select" required>
                                <option value="">{{ __('student.select_institute') }}</option>
                                @foreach($institutes as $id => $name)
                                    <option value="{{ $id }}" {{ (old('institution_id', $student->institution_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.select_campus') }}</label>
                            <select name="campus_id" class="form-control default-select">
                                <option value="">{{ __('student.select_campus') }}</option>
                                @foreach($campuses as $id => $name)
                                    <option value="{{ $id }}" {{ (old('campus_id', $student->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.admission_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', isset($student) ? $student->admission_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.select_class') }}</label>
                            <select name="grade_level_id" class="form-control default-select">
                                <option value="">{{ __('student.select_class') }}</option>
                                @foreach($gradeLevels as $id => $name)
                                    <option value="{{ $id }}" {{ (old('grade_level_id', $student->grade_level_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Personal Details -->
                <div class="tab-pane fade" id="personal" role="tabpanel">
                    <div class="row">
                        <div class="col-md-12 mb-3 text-center">
                            <label class="form-label d-block">{{ __('student.photo') }}</label>
                            <div class="avatar-upload d-inline-block position-relative">
                                <div class="position-relative">
                                    <div class="change-btn d-flex align-items-center justify-content-center">
                                        <input type='file' class="form-control d-none" name="student_photo" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                        <label for="imageUpload" class="btn btn-primary btn-sm rounded-circle p-2 mb-0"><i class="fa fa-camera"></i></label>
                                    </div>
                                    <div class="avatar-preview rounded-circle" style="width: 100px; height: 100px; overflow: hidden; border: 3px solid #f0f0f0;">
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.first_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $student->first_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.last_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $student->last_name ?? '') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.dob') }} <span class="text-danger">*</span></label>
                            <input type="date" name="dob" class="form-control" value="{{ old('dob', isset($student) ? $student->dob->format('Y-m-d') : '') }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.gender') }} <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control default-select" required>
                                <option value="male" {{ (old('gender', $student->gender ?? '') == 'male') ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ (old('gender', $student->gender ?? '') == 'female') ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ (old('gender', $student->gender ?? '') == 'other') ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('student.blood_group') }}</label>
                            <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $student->blood_group ?? '') }}">
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Parents/Guardian -->
                <div class="tab-pane fade" id="parents" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.father_name') }}</label>
                            <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $student->father_name ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.father_phone') }}</label>
                            <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $student->father_phone ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mother_name') }}</label>
                            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $student->mother_name ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('student.mother_phone') }}</label>
                            <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $student->mother_phone ?? '') }}">
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Identity & Access -->
                <div class="tab-pane fade" id="identity" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">QR Code Token</label>
                            <input type="text" name="qr_code_token" class="form-control" value="{{ old('qr_code_token', $student->qr_code_token ?? '') }}" placeholder="Scan or enter QR Code Token">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NFC Tag UID</label>
                            <input type="text" name="nfc_tag_uid" class="form-control" value="{{ old('nfc_tag_uid', $student->nfc_tag_uid ?? '') }}" placeholder="Scan or enter NFC UID">
                        </div>
                    </div>
                </div>

            </div> <!-- End Tab Content -->

            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{{ isset($student) ? __('student.update_student') : __('student.save_student') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Simple Image Preview Script --}}
<script>
    document.getElementById('imageUpload').onchange = function (evt) {
        var tgt = evt.target || window.event.srcElement,
            files = tgt.files;

        if (FileReader && files && files.length) {
            var fr = new FileReader();
            fr.onload = function () {
                var preview = document.getElementById('imagePreview');
                if(preview.tagName === 'IMG') {
                    preview.src = fr.result;
                } else {
                    // Replace div with img if it was a placeholder
                    var img = document.createElement('img');
                    img.id = 'imagePreview';
                    img.src = fr.result;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    preview.parentNode.replaceChild(img, preview);
                }
            }
            fr.readAsDataURL(files[0]);
        }
    }
</script>