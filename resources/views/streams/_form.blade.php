<form action="{{ isset($stream) ? route('streams.update', $stream->id) : route('streams.store') }}" method="POST" id="streamForm">
    @csrf
    @if(isset($stream))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h4 class="card-title">{{ __('stream.basic_information') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            {{-- Institution Selection --}}
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('stream.select_institution') }} <span class="text-danger">*</span></label>
                                <select name="institution_id" class="form-control default-select" required>
                                    <option value="">{{ __('stream.select_institution') }}</option>
                                    @foreach($institutes as $id => $name)
                                        <option value="{{ $id }}" {{ (old('institution_id', $stream->institution_id ?? auth()->user()->institute_id) == $id) ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Name --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('stream.stream_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $stream->name ?? '') }}" placeholder="{{ __('stream.enter_name') }}" required>
                            </div>

                            {{-- Code --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('stream.stream_code') }}</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $stream->code ?? '') }}" placeholder="{{ __('stream.enter_code') }}">
                            </div>

                            {{-- Status --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('stream.status') }}</label>
                                <select name="is_active" class="form-control default-select">
                                    <option value="1" {{ (old('is_active', $stream->is_active ?? 1) == 1) ? 'selected' : '' }}>{{ __('stream.active') }}</option>
                                    <option value="0" {{ (old('is_active', $stream->is_active ?? 1) == 0) ? 'selected' : '' }}>{{ __('stream.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">{{ isset($stream) ? __('stream.update') : __('stream.save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>