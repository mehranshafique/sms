<form action="{{ isset($academic_session) ? route('academic-sessions.update', $academic_session->id) : route('academic-sessions.store') }}" method="POST" id="sessionForm">
    @csrf
    @if(isset($academic_session))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('academic_session.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            
                            {{-- LOGIC: Auto-Assign vs Select Institute --}}
                            @php
                                $hasContext = isset($institutionId) && $institutionId;
                                $isSuperAdmin = auth()->user()->hasRole('Super Admin');
                            @endphp

                            @if($hasContext && !$isSuperAdmin)
                                <input type="hidden" name="institution_id" value="{{ $institutionId }}">
                            @else
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">{{ __('academic_session.select_institution') }} <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control default-select" required>
                                        <option value="">-- {{ __('academic_session.select_institution') }} --</option>
                                        @foreach($institutions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('institution_id', $academic_session->institution_id ?? ($hasContext ? $institutionId : '')) == $id) ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Name --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('academic_session.session_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $academic_session->name ?? '') }}" class="form-control" placeholder="{{ __('academic_session.enter_session_name') }}" required>
                            </div>

                            {{-- Dates (Updated Class to 'datepicker') --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('academic_session.start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="start_date" 
                                       value="{{ old('start_date', isset($academic_session) && $academic_session->start_date ? $academic_session->start_date->format('Y-m-d') : '') }}" 
                                       class="datepicker form-control" placeholder="YYYY-MM-DD" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('academic_session.end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="end_date" 
                                       value="{{ old('end_date', isset($academic_session) && $academic_session->end_date ? $academic_session->end_date->format('Y-m-d') : '') }}" 
                                       class="datepicker form-control" placeholder="YYYY-MM-DD" required>
                            </div>

                            {{-- Status --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('academic_session.status') }}</label>
                                <select name="status" class="form-control default-select">
                                    <option value="planned" {{ (old('status', $academic_session->status ?? '') == 'planned') ? 'selected' : '' }}>Planned</option>
                                    <option value="active" {{ (old('status', $academic_session->status ?? '') == 'active') ? 'selected' : '' }}>Active</option>
                                    <option value="closed" {{ (old('status', $academic_session->status ?? '') == 'closed') ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>

                            {{-- Is Current --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('academic_session.is_current') }}</label>
                                <select name="is_current" class="form-control default-select">
                                    <option value="0" {{ (old('is_current', $academic_session->is_current ?? 0) == 0) ? 'selected' : '' }}>{{ __('academic_session.no') }}</option>
                                    <option value="1" {{ (old('is_current', $academic_session->is_current ?? 0) == 1) ? 'selected' : '' }}>{{ __('academic_session.yes') }}</option>
                                </select>
                                <small class="text-muted">Setting this to "Yes" will automatically deactivate other sessions for this institution.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($academic_session) ? __('academic_session.update_session') : __('academic_session.save_session') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>