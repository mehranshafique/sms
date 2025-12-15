<form action="{{ isset($campus) ? route('campuses.update', $campus->id) : route('campuses.store') }}" method="POST" id="campusForm">
    @csrf
    @if(isset($campus))
        @method('PUT')
    @endif

    <div class="row">
        <!-- Basic Information -->
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('campus.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('campus.select_institution') }} <span class="text-danger">*</span></label>
                                <select name="institution_id" class="form-control default-select" required>
                                    <option value="">-- {{ __('campus.select_institution') }} --</option>
                                    @foreach($institutions as $id => $name)
                                        <option value="{{ $id }}" {{ (old('institution_id', $campus->institution_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('campus.campus_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $campus->name ?? '') }}" class="form-control" placeholder="{{ __('campus.enter_campus_name') }}" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('campus.campus_code') }} <span class="text-danger">*</span></label>
                                <input type="text" name="code" value="{{ old('code', $campus->code ?? '') }}" class="form-control" placeholder="{{ __('campus.enter_campus_code') }}" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('campus.status') }}</label>
                                <select name="is_active" class="form-control default-select">
                                    <option value="1" {{ (old('is_active', $campus->is_active ?? 1) == 1) ? 'selected' : '' }}>{{ __('campus.active') }}</option>
                                    <option value="0" {{ (old('is_active', $campus->is_active ?? 1) == 0) ? 'selected' : '' }}>{{ __('campus.inactive') }}</option>
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
                    <h4 class="card-title">{{ __('campus.contact_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('campus.email') }}</label>
                                <input type="email" name="email" value="{{ old('email', $campus->email ?? '') }}" class="form-control" placeholder="{{ __('campus.enter_email') }}">
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('campus.phone_number') }}</label>
                                <input type="text" name="phone" value="{{ old('phone', $campus->phone ?? '') }}" class="form-control" placeholder="{{ __('campus.enter_phone_number') }}">
                            </div>
                            <div class="mb-3 col-md-4">
                                <label class="form-label">{{ __('campus.enter_country') }}</label>
                                <input type="text" name="country" value="{{ old('country', $campus->country ?? '') }}" class="form-control">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('campus.enter_city') }}</label>
                                <input type="text" name="city" value="{{ old('city', $campus->city ?? '') }}" class="form-control" placeholder="{{ __('campus.enter_city') }}">
                            </div>
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('campus.full_address') }}</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="{{ __('campus.enter_full_address') }}">{{ old('address', $campus->address ?? '') }}</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($campus) ? __('campus.update_campus') : __('campus.save_campus') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>