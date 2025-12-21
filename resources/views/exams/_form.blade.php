<form action="{{ isset($exam) ? route('exams.update', $exam->id) : route('exams.store') }}" method="POST" id="examForm">
    @csrf
    @if(isset($exam))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('exam.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            {{-- Session --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.select_session') }} <span class="text-danger">*</span></label>
                                <select name="academic_session_id" class="form-control default-select" required>
                                    <option value="">{{ __('exam.select_session') }}</option>
                                    @if(isset($sessions) && count($sessions) > 0)
                                        @foreach($sessions as $id => $name)
                                            <option value="{{ $id }}" {{ (old('academic_session_id', $exam->academic_session_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- Name --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $exam->name ?? '') }}" placeholder="{{ __('exam.enter_name') }}" required>
                            </div>

                            {{-- Dates (Fixed: Using 'datepicker' class instead of type='date') --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.start_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="start_date" class="form-control datepicker" value="{{ old('start_date', isset($exam) ? $exam->start_date->format('Y-m-d') : '') }}" placeholder="YYYY-MM-DD" required>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.end_date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="end_date" class="form-control datepicker" value="{{ old('end_date', isset($exam) ? $exam->end_date->format('Y-m-d') : '') }}" placeholder="YYYY-MM-DD" required>
                            </div>

                            {{-- Status --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('exam.status_label') }}</label>
                                <select name="status" class="form-control default-select">
                                    <option value="scheduled" {{ (old('status', $exam->status ?? '') == 'scheduled') ? 'selected' : '' }}>{{ __('exam.scheduled') }}</option>
                                    <option value="ongoing" {{ (old('status', $exam->status ?? '') == 'ongoing') ? 'selected' : '' }}>{{ __('exam.ongoing') }}</option>
                                    <option value="completed" {{ (old('status', $exam->status ?? '') == 'completed') ? 'selected' : '' }}>{{ __('exam.completed') }}</option>
                                    <option value="published" {{ (old('status', $exam->status ?? '') == 'published') ? 'selected' : '' }}>{{ __('exam.published') }}</option>
                                </select>
                            </div>

                            {{-- Description --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('exam.description') }}</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description ?? '') }}</textarea>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ isset($exam) ? __('exam.update_exam') : __('exam.save_exam') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>