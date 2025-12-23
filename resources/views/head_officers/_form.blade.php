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
                            {{-- Profile Picture --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('head_officers.profile_picture') }}</label>
                                <div class="input-group">
                                    <div class="form-file">
                                        <input type="file" name="profile_picture" class="form-file-input form-control">
                                    </div>
                                    <span class="input-group-text">{{ __('head_officers.upload') }}</span>
                                </div>
                                @if(isset($head_officer) && $head_officer->profile_picture)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $head_officer->profile_picture) }}" alt="Profile Picture" width="80" class="rounded-circle">
                                    </div>
                                @endif
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
                            <div class="mb-3 col-md-12"> {{-- Expanded to full width since role is hidden --}}
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
                                <input type="text" name="phone" value="{{ old('phone', $head_officer->phone ?? '') }}" class="form-control" placeholder="{{ __('head_officers.enter_phone') }}" required>
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
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($head_officer) ? __('head_officers.update') : __('head_officers.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>