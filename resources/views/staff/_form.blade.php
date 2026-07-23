<!-- Add intl-tel-input CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<style>
    /* Fix for intl-tel-input dropdown width */
    .iti { width: 100%; z-index: 99; }
</style>

<form action="{{ isset($staff) ? route('staff.update', $staff->id) : route('staff.store') }}" method="POST" id="staffForm" enctype="multipart/form-data">
    @csrf
    @if(isset($staff))
        @method('PUT')
    @endif

    <div class="row">
        <!-- Basic Info -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('staff.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Profile Picture --}}
                        <div class="col-md-12 mb-3 text-center">
                            <label class="form-label d-block">{{ __('staff.profile_picture') }}</label>
                            <div class="d-inline-block position-relative">
                                @if(isset($staff) && $staff->user->profile_picture)
                                    <img src="{{ asset('storage/'.$staff->user->profile_picture) }}" class="rounded-circle" width="100" height="100" style="object-fit:cover;">
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:100px; height:100px;">
                                        <i class="fa fa-user fa-2x text-muted"></i>
                                    </div>
                                @endif
                                <div class="mt-2">
                                    <input type="file" name="profile_picture" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.full_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $staff->user->name ?? '') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $staff->user->email ?? '') }}" required>
                        </div>
                        
                        {{-- Phone with intl-tel-input --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.phone') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="hidden" name="phone" id="fullPhoneInput">
                                <input type="tel" id="phoneInput" class="form-control" value="{{ old('phone', $staff->user->phone ?? '') }}" required>
                            </div>
                            @error('phone')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.gender') }} <span class="text-danger">*</span></label>
                            <select name="gender" class="form-control default-select" required>
                                <option value="male" {{ (old('gender', $staff->gender ?? '') == 'male') ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ (old('gender', $staff->gender ?? '') == 'female') ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ (old('gender', $staff->gender ?? '') == 'other') ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.password') }}</label>
                            <input type="password" name="password" class="form-control" placeholder="{{ isset($staff) ? __('staff.leave_blank_password') : '' }}" {{ isset($staff) ? '' : 'required' }}>
                        </div>
                        
                        {{-- Role Selection — School Admin may assign any school role (except Super Admin / Head Officer) --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.select_role') }} <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-control default-select" required>
                                <option value="">{{ __('staff.select_role') }}</option>
                                @foreach($roles as $role)
                                    @php
                                        $authUser = auth()->user();
                                        
                                        if ($role->name === 'Super Admin') continue;
                                        if (is_null($role->institution_id)) continue;
                                        if (in_array($role->name, ['Student', 'Parent', 'Guardian'], true)) continue;

                                        // Head Officer is platform-managed
                                        if ($role->name === 'Head Officer' && !$authUser->hasRole(['Super Admin', 'Head Officer'])) continue;

                                        $isSelected = false;
                                        if (old('role_id')) {
                                            $isSelected = (int) old('role_id') === (int) $role->id;
                                        } elseif (isset($staff) && $staff->user) {
                                            $isSelected = $staff->user->roles->contains('id', $role->id)
                                                || $staff->user->hasRole($role->name);
                                        }
                                    @endphp
                                    <option value="{{ $role->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professional Details -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('staff.professional_details') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        
                        {{-- LOGIC: Auto-Assign vs Select Institute --}}
                        @php
                            $hasContext = isset($institutionId) && $institutionId;
                            $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                        @endphp

                        @if($hasContext && !$isSuperAdmin)
                            {{-- Standard User or Head Officer with Context Set: Hide & Auto-Fill --}}
                            <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                        @else
                            {{-- Super Admin or No Context Set: Show Dropdown --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('staff.select_institution') }} <span class="text-danger">*</span></label>
                                <select name="institution_id" class="form-control default-select" required {{ isset($staff) ? 'disabled' : '' }}>
                                    <option value="">{{ __('staff.select_institution') }}</option>
                                    @foreach($institutions as $id => $name)
                                        <option value="{{ $id }}" {{ (old('institution_id', $staff->institution_id ?? ($hasContext ? $institutionId : '')) == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Hide Campus dropdown if no campuses available --}}
                        @if(isset($campuses) && count($campuses) > 0)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.select_campus') }}</label>
                            <select name="campus_id" class="form-control default-select">
                                <option value="">Select Campus</option>
                                @foreach($campuses as $id => $name)
                                    <option value="{{ $id }}" {{ (old('campus_id', $staff->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('staff.employee_id') }}</label>
                            <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $staff->employee_id ?? '') }}" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('staff.designation') }}</label>
                            <input type="text" name="designation" class="form-control" value="{{ old('designation', $staff->designation ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('staff.department') }}</label>
                            <input type="text" name="department" class="form-control" value="{{ old('department', $staff->department ?? '') }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.joining_date') }}</label>
                            <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', isset($staff) && $staff->joining_date ? $staff->joining_date->format('Y-m-d') : '') }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.status_label') }}</label>
                            <select name="status" class="form-control default-select">
                                <option value="active" {{ (old('status', $staff->status ?? '') == 'active') ? 'selected' : '' }}>{{ __('staff.status_active') }}</option>
                                <option value="on_leave" {{ (old('status', $staff->status ?? '') == 'on_leave') ? 'selected' : '' }}>{{ __('staff.status_on_leave') }}</option>
                                <option value="resigned" {{ (old('status', $staff->status ?? '') == 'resigned') ? 'selected' : '' }}>{{ __('staff.status_resigned') }}</option>
                                <option value="terminated" {{ (old('status', $staff->status ?? '') == 'terminated') ? 'selected' : '' }}>{{ __('staff.status_terminated') }}</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('staff.address') }}</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $staff->address ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identity & Access -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('staff.identity_access') ?? 'Identity & Access' }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- NFC -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.nfc_uid') ?? 'NFC UID' }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-wifi"></i></span>
                                <input type="text" name="nfc_uid" id="nfc_input" class="form-control" value="{{ old('nfc_uid', $staff->nfc_uid ?? '') }}" placeholder="{{ __('staff.enter_nfc_uid') ?? 'Scan or enter NFC UID' }}" autocomplete="off">
                                <button class="btn btn-secondary" type="button" id="btnScanNFC"><i class="fa fa-mobile me-1"></i> Scan</button>
                            </div>
                            <small class="text-muted d-block mt-1">Tap card on reader (Desktop) or click Scan (Mobile).</small>
                            <div id="nfcStatus" class="small mt-1 text-info d-none"><i class="fa fa-spinner fa-spin"></i> Scanning... Tap card on back of phone.</div>
                        </div>

                        <!-- RFID -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.rfid_uid') ?? 'RFID UID' }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-id-badge"></i></span>
                                <input type="text" name="rfid_uid" class="form-control" value="{{ old('rfid_uid', $staff->rfid_uid ?? '') }}" placeholder="{{ __('staff.enter_rfid_uid') ?? 'Scan or enter RFID UID' }}" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-end pt-3 border-top">
                        <button type="submit" class="btn btn-success btn-lg shadow btn-submit"><i class="fa fa-save me-2"></i> {{ isset($staff) ? __('staff.update_staff') : __('staff.save_staff') }}</button>
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
        // --- Phone Input Integration (intl-tel-input) ---
        const phoneInput = document.querySelector("#phoneInput");
        const fullPhoneInput = document.querySelector("#fullPhoneInput");
        const form = document.querySelector("#staffForm");

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

        // --- NFC Reader Logic ---
        const nfcInput = document.getElementById('nfc_input');
        const btnScanNFC = document.getElementById('btnScanNFC');
        const nfcStatus = document.getElementById('nfcStatus');

        if (nfcInput) {
            nfcInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault(); 
                    this.blur(); 
                }
            });
        }

        if (btnScanNFC) {
            btnScanNFC.addEventListener('click', async () => {
                if (!('NDEFReader' in window)) {
                    alert('Web NFC is not supported on this device/browser.');
                    return;
                }
                try {
                    const ndef = new NDEFReader();
                    await ndef.scan();
                    if(nfcStatus) nfcStatus.classList.remove('d-none');
                    btnScanNFC.disabled = true;
                    btnScanNFC.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                    ndef.onreading = event => {
                        nfcInput.value = event.serialNumber;
                        if(nfcStatus) nfcStatus.classList.add('d-none');
                        btnScanNFC.disabled = false;
                        btnScanNFC.innerHTML = '<i class="fa fa-mobile me-1"></i> Scan';
                    };
                    ndef.onreadingerror = () => {
                        alert('Cannot read data from the NFC tag. Try again.');
                        if(nfcStatus) nfcStatus.classList.add('d-none');
                        btnScanNFC.disabled = false;
                        btnScanNFC.innerHTML = '<i class="fa fa-mobile me-1"></i> Scan';
                    };
                } catch (error) {
                    alert('NFC Scan failed: ' + error);
                    btnScanNFC.disabled = false;
                    btnScanNFC.innerHTML = '<i class="fa fa-mobile me-1"></i> Scan';
                }
            });
        }
    });
</script>