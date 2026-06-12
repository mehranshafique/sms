<form action="{{ isset($notice) ? route('notices.update', $notice->id) : route('notices.store') }}" method="POST" id="mainForm">
    @csrf
    @if(isset($notice))
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow-sm" style="border-radius: 15px;">
                <div class="card-header border-0 pb-0 pt-4 px-4 bg-white">
                    <h4 class="card-title">{{ __('notice.page_title') }}</h4>
                </div>
                <div class="card-body">
                    <div class="basic-form">
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('notice.title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $notice->title ?? '') }}" placeholder="Enter Notice Title" required>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('notice.type') }} <span class="text-danger">*</span></label>
                                <select name="type" class="form-control default-select" required>
                                    <option value="info" {{ (old('type', $notice->type ?? '') == 'info') ? 'selected' : '' }}>{{ __('notice.info') }}</option>
                                    <option value="warning" {{ (old('type', $notice->type ?? '') == 'warning') ? 'selected' : '' }}>{{ __('notice.warning') }}</option>
                                    <option value="urgent" {{ (old('type', $notice->type ?? '') == 'urgent') ? 'selected' : '' }}>{{ __('notice.urgent') }}</option>
                                </select>
                            </div>

                            <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('notice.audience') }} <span class="text-danger">*</span></label>
                                <select name="audience" class="form-control default-select" required>
                                    <option value="all" {{ (old('audience', $notice->audience ?? '') == 'all') ? 'selected' : '' }}>{{ __('notice.all') }}</option>
                                    <option value="staff" {{ (old('audience', $notice->audience ?? '') == 'staff') ? 'selected' : '' }}>{{ __('notice.staff') }}</option>
                                    <option value="student" {{ (old('audience', $notice->audience ?? '') == 'student') ? 'selected' : '' }}>{{ __('notice.student') }}</option>
                                    <option value="parent" {{ (old('audience', $notice->audience ?? '') == 'parent') ? 'selected' : '' }}>{{ __('notice.parent') }}</option>
                                </select>
                            </div>

                            <div class="mb-3 col-md-12">
                                <label class="form-label">{{ __('notice.content') }} <span class="text-danger">*</span></label>
                                @if(!empty($planCtx['has_ai']))
                                <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                                    <input type="text" id="ai-notice-topic" class="form-control form-control-sm" style="max-width:280px" placeholder="{{ __('notice.title') }}…">
                                    @include('ai.partials.embed-button', [
                                        'tool' => 'draft_notice',
                                        'params' => ['tone' => 'professional'],
                                        'fields' => ['topic' => '#ai-notice-topic', 'audience' => 'select[name=audience]'],
                                        'label' => __('ai.btn_draft_notice'),
                                        'target' => 'textarea[name=content]',
                                        'panel' => '#ai-notice-draft-panel',
                                        'confirm' => true,
                                    ])
                                    @include('ai.partials.embed-button', [
                                        'tool' => 'translate',
                                        'params' => ['target_lang' => app()->getLocale() === 'fr' ? 'French' : 'English'],
                                        'fields' => ['text' => 'textarea[name=content]'],
                                        'label' => __('ai.btn_translate_notice'),
                                        'target' => 'textarea[name=content]',
                                        'confirm' => true,
                                    ])
                                </div>
                                <div class="ai-embed-panel" id="ai-notice-draft-panel"></div>
                                @endif
                                <textarea name="content" class="form-control" rows="5" required>{{ old('content', $notice->content ?? '') }}</textarea>
                            </div>

                            @if(!isset($notice))
                            <div class="mb-3 col-md-6">
                                <div class="form-check custom-checkbox checkbox-primary">
                                    <input type="checkbox" class="form-check-input" id="publish_now" name="publish_now" value="1" checked>
                                    <label class="form-check-label" for="publish_now">{{ __('notice.published') }} immediately?</label>
                                </div>
                            </div>
                            @else
                             <div class="mb-3 col-md-6">
                                <label class="form-label">{{ __('notice.status') }}</label>
                                <select name="is_published" class="form-control default-select">
                                    <option value="1" {{ $notice->is_published ? 'selected' : '' }}>{{ __('notice.published') }}</option>
                                    <option value="0" {{ !$notice->is_published ? 'selected' : '' }}>{{ __('notice.draft') }}</option>
                                </select>
                            </div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">{{ isset($notice) ? __('notice.success_update') : __('notice.success_create') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>