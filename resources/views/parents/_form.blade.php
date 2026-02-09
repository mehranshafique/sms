<form action="{{ isset($parent) ? route('parents.update', $parent->id) : route('parents.store') }}" method="POST">
    @csrf
    @if(isset($parent))
        @method('PUT')
    @endif

    <div class="row">
        {{-- Father --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light border-bottom-0 pb-0">
                    <h5 class="card-title text-primary"><i class="fa fa-male me-2"></i> {{ __('parent.father_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('parent.father_name') }}</label>
                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $parent->father_name ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('parent.father_phone') }}</label>
                        <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone', $parent->father_phone ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('parent.father_occupation') }}</label>
                        <input type="text" name="father_occupation" class="form-control" value="{{ old('father_occupation', $parent->father_occupation ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Mother --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light border-bottom-0 pb-0">
                    <h5 class="card-title text-success"><i class="fa fa-female me-2"></i> {{ __('parent.mother_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('parent.mother_name') }}</label>
                        <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $parent->mother_name ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('parent.mother_phone') }}</label>
                        <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone', $parent->mother_phone ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('parent.mother_occupation') }}</label>
                        <input type="text" name="mother_occupation" class="form-control" value="{{ old('mother_occupation', $parent->mother_occupation ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Guardian / Login Info --}}
        <div class="col-xl-12 col-lg-12 mt-4">
            <div class="card border-primary" style="border-width: 1px;">
                <div class="card-header bg-white">
                    <h5 class="card-title"><i class="fa fa-id-card me-2"></i> {{ __('parent.guardian_details') }} (System Login)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('parent.guardian_name') }}</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $parent->guardian_name ?? '') }}" placeholder="Primary Contact Name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('parent.guardian_email') }}</label>
                            <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email', $parent->guardian_email ?? '') }}" placeholder="Used for Login">
                            <small class="text-muted">A user account will be created if this email is unique.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">{{ __('parent.guardian_phone') }}</label>
                            <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $parent->guardian_phone ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('parent.guardian_relation') }}</label>
                            <input type="text" name="guardian_relation" class="form-control" value="{{ old('guardian_relation', $parent->guardian_relation ?? '') }}" placeholder="e.g. Uncle, Brother">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('parent.address_label') }}</label>
                            <textarea name="family_address" class="form-control" rows="2">{{ old('family_address', $parent->family_address ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end bg-white">
                    <a href="{{ route('parents.index') }}" class="btn btn-light me-2">{{ __('parent.cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ isset($parent) ? __('parent.update') : __('parent.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>