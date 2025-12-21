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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.phone') }}</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->user->phone ?? '') }}">
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
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.select_role') }} <span class="text-danger">*</span></label>
                            <select name="role" class="form-control default-select" required>
                                <option value="">{{ __('staff.select_role') }}</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ (isset($staff) && $staff->user->hasRole($role->name)) ? 'selected' : '' }}>{{ $role->name }}</option>
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

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('staff.select_campus') }}</label>
                            <select name="campus_id" class="form-control default-select">
                                <option value="">Select Campus</option>
                                @foreach($campuses as $id => $name)
                                    <option value="{{ $id }}" {{ (old('campus_id', $staff->campus_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
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
                                <option value="active" {{ (old('status', $staff->status ?? '') == 'active') ? 'selected' : '' }}>Active</option>
                                <option value="on_leave" {{ (old('status', $staff->status ?? '') == 'on_leave') ? 'selected' : '' }}>On Leave</option>
                                <option value="resigned" {{ (old('status', $staff->status ?? '') == 'resigned') ? 'selected' : '' }}>Resigned</option>
                                <option value="terminated" {{ (old('status', $staff->status ?? '') == 'terminated') ? 'selected' : '' }}>Terminated</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('staff.address') }}</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $staff->address ?? '') }}</textarea>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">{{ isset($staff) ? __('staff.update_staff') : __('staff.save_staff') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>