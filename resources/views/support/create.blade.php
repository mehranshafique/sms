@extends('layout.layout')

@section('content')
@include('support.partials.support-styles')
<div class="content-body">
    <div class="container-fluid">

        <div class="row mb-4">
            <div class="col-12">
                <div class="sp-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <h3 class="text-white fw-bold mb-1">{{ __('support.new_ticket') }}</h3>
                            <p class="mb-0 text-white opacity-75">{{ __('support.create_subtitle') }}</p>
                        </div>
                        <a href="{{ route('support.index') }}" class="btn btn-light fw-bold text-primary">
                            <i class="la la-arrow-left"></i> {{ __('support.back_to_list') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="sp-panel p-4">
                    <form action="{{ route('support.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('support.field_subject') }}</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" maxlength="160"
                                   class="form-control @error('subject') is-invalid @enderror"
                                   placeholder="{{ __('support.subject_placeholder') }}" required>
                            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">{{ __('support.field_category') }}</label>
                                <select name="category" class="form-control @error('category') is-invalid @enderror">
                                    @foreach(\App\Models\SupportTicket::CATEGORIES as $cat)
                                        <option value="{{ $cat }}" @selected(old('category')===$cat)>{{ __('support.category_'.$cat) }}</option>
                                    @endforeach
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">{{ __('support.field_priority') }}</label>
                                <select name="priority" class="form-control @error('priority') is-invalid @enderror">
                                    @foreach(\App\Models\SupportTicket::PRIORITIES as $prio)
                                        <option value="{{ $prio }}" @selected(old('priority', 'medium')===$prio)>{{ __('support.priority_'.$prio) }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('support.field_message') }}</label>
                            <textarea name="message" rows="6" maxlength="5000"
                                      class="form-control @error('message') is-invalid @enderror"
                                      placeholder="{{ __('support.message_placeholder') }}" required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ __('support.field_attachment') }} <span class="text-muted fw-normal">({{ __('support.optional') }})</span></label>
                            <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
                            <small class="text-muted">{{ __('support.attachment_hint') }}</small>
                            @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('support.index') }}" class="btn btn-light">{{ __('support.cancel') }}</a>
                            <button type="submit" class="btn btn-primary fw-bold"><i class="la la-paper-plane"></i> {{ __('support.submit_ticket') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
