@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('chatbot.page_title') }}</h4>
                    <p class="mb-0">{{ __('chatbot.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        
                        {{-- Tabs --}}
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a href="#general" data-bs-toggle="tab" class="nav-link active">
                                    <i class="fa fa-cogs me-2"></i> {{ __('chatbot.general_config') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#keywords" data-bs-toggle="tab" class="nav-link">
                                    <i class="fa fa-key me-2"></i> {{ __('chatbot.keyword_management') }}
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            
                            {{-- Tab 1: General Config --}}
                            <div class="tab-pane fade show active" id="general">
                                <form action="{{ route('chatbot.config.update') }}" method="POST">
                                    @csrf
                                    
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h5 class="text-primary mb-3">{{ __('chatbot.channels') }}</h5>
                                            <p class="text-muted fs-13 mb-3">{{ __('chatbot.channel_help') }}</p>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="card bg-light border">
                                                <div class="card-body p-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="chatbot_enable_whatsapp" id="enableWa" value="1" {{ $config['whatsapp'] ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="enableWa">WhatsApp</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="card bg-light border">
                                                <div class="card-body p-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="chatbot_enable_sms" id="enableSms" value="1" {{ $config['sms'] ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="enableSms">SMS (Two-Way)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="card bg-light border">
                                                <div class="card-body p-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="chatbot_enable_telegram" id="enableTg" value="1" {{ $config['telegram'] ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="enableTg">Telegram</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <h5 class="text-primary mb-3">{{ __('chatbot.session_settings') }}</h5>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">{{ __('chatbot.session_timeout') }}</label>
                                            <input type="number" name="chatbot_session_timeout" class="form-control" value="{{ $config['timeout'] }}" min="1" required>
                                            <small class="text-muted">{{ __('chatbot.session_timeout_help') }}</small>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save me-2"></i> {{ __('chatbot.save_config') }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {{-- Tab 2: Keyword Management --}}
                            <div class="tab-pane fade" id="keywords">
                                <div class="d-flex justify-content-end mb-3">
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#keywordModal">
                                        <i class="fa fa-plus me-2"></i> {{ __('chatbot.add_keyword') }}
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered verticle-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>{{ __('chatbot.keyword') }}</th>
                                                <th>{{ __('chatbot.language') }}</th>
                                                <th>{{ __('chatbot.response_message') }}</th>
                                                <th class="text-end">{{ __('chatbot.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($keywords as $k)
                                            <tr>
                                                <td><span class="badge badge-success fs-14">{{ $k->keyword }}</span></td>
                                                <td>
                                                    @if($k->language == 'en') <span class="badge badge-light text-dark">English</span>
                                                    @else <span class="badge badge-light text-dark">French</span> @endif
                                                </td>
                                                <td>
                                                    <div class="text-wrap" style="max-width: 400px;">
                                                        {{ Str::limit($k->welcome_message, 80) }}
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-xs btn-primary edit-keyword shadow" 
                                                            data-id="{{ $k->id }}"
                                                            data-keyword="{{ $k->keyword }}"
                                                            data-lang="{{ $k->language }}"
                                                            data-msg="{{ $k->welcome_message }}">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                    <form action="{{ route('chatbot.keywords.destroy', $k->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger shadow" onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <i class="fa fa-info-circle me-1"></i> {{ __('chatbot.no_keywords') }}
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Keyword Modal --}}
<div class="modal fade" id="keywordModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="keywordModalTitle">{{ __('chatbot.add_keyword') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('chatbot.keywords.store') }}" method="POST" id="keywordForm">
                @csrf
                <input type="hidden" name="_method" value="POST" id="formMethod">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('chatbot.keyword') }} <span class="text-danger">*</span></label>
                        <input type="text" name="keyword" id="inputKeyword" class="form-control" placeholder="e.g. bonjour, hi" required>
                        <small class="text-muted">{{ __('chatbot.keyword_help') }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('chatbot.language') }}</label>
                        <select name="language" id="inputLang" class="form-control default-select">
                            <option value="en">English</option>
                            <option value="fr">French</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('chatbot.response_message') }} <span class="text-danger">*</span></label>
                        <textarea name="welcome_message" id="inputMsg" class="form-control" rows="5" placeholder="{{ __('chatbot.response_placeholder') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finance.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('finance.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        if($.fn.selectpicker) $('.default-select').selectpicker();

        // Edit Handler
        $('.edit-keyword').click(function() {
            let id = $(this).data('id');
            let kw = $(this).data('keyword');
            let lang = $(this).data('lang');
            let msg = $(this).data('msg');

            $('#keywordModalTitle').text("{{ __('chatbot.edit_keyword') }}");
            $('#inputKeyword').val(kw);
            $('#inputLang').val(lang).change();
            if($.fn.selectpicker) $('#inputLang').selectpicker('refresh');
            $('#inputMsg').val(msg);

            // Change Form Action
            let updateUrl = "{{ url('chatbot/keywords') }}/" + id;
            $('#keywordForm').attr('action', updateUrl);
            $('#formMethod').val('PUT');

            $('#keywordModal').modal('show');
        });

        // Reset Modal on Close
        $('#keywordModal').on('hidden.bs.modal', function () {
            $('#keywordForm').attr('action', "{{ route('chatbot.keywords.store') }}");
            $('#formMethod').val('POST');
            $('#keywordForm')[0].reset();
            $('#keywordModalTitle').text("{{ __('chatbot.add_keyword') }}");
            if($.fn.selectpicker) $('#inputLang').selectpicker('refresh');
        });
    });
</script>
@endsection