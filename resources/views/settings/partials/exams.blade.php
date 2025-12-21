<form action="{{ route('settings.update') }}" method="POST">
    @csrf
    
    <div class="row">
        <div class="col-md-6">
            <h4 class="text-primary mb-4">{{ __('settings.exam_settings') }}</h4>

            {{-- Global Lock --}}
            <div class="mb-4">
                <label class="form-label font-w600">{{ __('settings.lock_exams') }}</label>
                <select name="exams_locked" class="form-control default-select">
                    <option value="0" {{ $examsLocked == 0 ? 'selected' : '' }}>{{ __('settings.disabled') }}</option>
                    <option value="1" {{ $examsLocked == 1 ? 'selected' : '' }}>{{ __('settings.enabled') }}</option>
                </select>
                <small class="text-muted d-block mt-1">{{ __('settings.lock_exams_help') }}</small>
            </div>

            {{-- Grace Period --}}
            <div class="mb-4">
                <label class="form-label font-w600">{{ __('settings.exam_grace_period') }}</label>
                <div class="input-group">
                    <input type="number" name="exams_grace_period" class="form-control" value="{{ $examsGracePeriod }}" min="0" max="365" required>
                    <span class="input-group-text">Days</span>
                </div>
                <small class="text-muted d-block mt-1">{{ __('settings.exam_grace_help') }}</small>
            </div>

            <button type="submit" class="btn btn-primary mt-3">{{ __('settings.save_changes') }}</button>
        </div>
    </div>
</form>