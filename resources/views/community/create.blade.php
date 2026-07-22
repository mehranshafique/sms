@extends('layouts.help')

@section('title', __('community.new_thread'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-3">
                <a href="{{ route('community.index') }}" class="text-muted small">
                    <i class="fa fa-arrow-left me-1"></i>{{ __('community.back_to_forum') }}
                </a>
            </div>
            <div class="help-card">
                <h1 class="h4 mb-4">{{ __('community.new_thread') }}</h1>
                <form method="POST" action="{{ route('community.store') }}" class="ajax-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('community.category') }}</label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('community.title') }}</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" required maxlength="200">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('community.body') }}</label>
                        <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="8"
                            required minlength="20">{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('community.body_hint') }}</div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('community.publish') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
