@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
    <!-- Ensure Responsive Bootstrap styling is present for the modal -->
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dt-buttons .dropdown-toggle {
            background-color: #fff !important;
            color: #697a8d !important;
            border-color: #d9dee3 !important;
            box-shadow: 0 0.125rem 0.25rem 0 rgba(105, 122, 141, 0.1);
            border-radius: 0.375rem; 
            padding: 0.4375rem 1rem;
        }
        .dt-buttons {
            display: inline-flex;
            vertical-align: middle;
            gap: 10px;
            margin-bottom: 1rem;
        }
        .dt-buttons .btn-danger {
            border-radius: 0.375rem;
        }
        /* Make sure the "+" icon is properly styled and visible */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before, 
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
            background-color: var(--primary) !important;
            box-shadow: 0 0 3px #444 !important;
        }
        
        /* Modal Content Styling */
        .dtr-modal .modal-header { background-color: #f8f9fa; border-bottom: 1px solid #ebeeef; }
        .dtr-modal .modal-title { font-weight: bold; color: var(--primary); }
    </style>
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('chatbot.page_title') ?? 'Chatbot Settings' }}</h4>
                    <p class="mb-0">{{ __('chatbot.subtitle') ?? 'Configure automated responses and behaviors' }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        
                        {{-- Tabs --}}
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a href="#general" data-bs-toggle="tab" class="nav-link active">
                                    <i class="fa fa-cogs me-2"></i> {{ __('chatbot.general_config') ?? 'General Configuration' }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#keywords" data-bs-toggle="tab" class="nav-link">
                                    <i class="fa fa-key me-2"></i> {{ __('chatbot.keyword_management') ?? 'Keyword Management' }}
                                </a>
                            </li>
                            @can('setting.view')
                            <li class="nav-item">
                                <a href="#sessions" data-bs-toggle="tab" class="nav-link">
                                    <i class="fa fa-comments me-2"></i> {{ __('chatbot.chat_sessions') ?? 'Chat Sessions' }}
                                </a>
                            </li>
                            @endcan
                        </ul>

                        <div class="tab-content">
                            
                            {{-- Tab 1: General Settings --}}
                            <div class="tab-pane fade show active" id="general">
                                <form action="{{ route('chatbot.settings.store_config') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <h5 class="text-primary border-bottom pb-2">{{ __('chatbot.channels') ?? 'Channels' }}</h5>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="whatsapp" id="whatsappSwitch" value="1" {{ $config['whatsapp'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold ms-2 mt-1" for="whatsappSwitch">{{ __('chatbot.enable_whatsapp') ?? 'Enable WhatsApp' }}</label>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="sms" id="smsSwitch" value="1" {{ $config['sms'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold ms-2 mt-1" for="smsSwitch">{{ __('chatbot.enable_sms') ?? 'Enable SMS' }}</label>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="telegram" id="telegramSwitch" value="1" {{ $config['telegram'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold ms-2 mt-1" for="telegramSwitch">{{ __('chatbot.enable_telegram') ?? 'Enable Telegram' }}</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-4">
                                            <h5 class="text-primary border-bottom pb-2">{{ __('chatbot.session_settings') ?? 'Session Settings' }}</h5>
                                            <div class="form-group mt-3">
                                                <label class="form-label fw-bold">{{ __('chatbot.session_timeout') ?? 'Session Timeout (Minutes)' }}</label>
                                                <input type="number" name="timeout" class="form-control" value="{{ $config['timeout'] }}" min="1" max="1440" required>
                                                <small class="text-muted">{{ __('chatbot.session_timeout_help') ?? 'Time before re-auth.' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <h5 class="text-primary border-bottom pb-2">{{ __('chatbot.webhook_urls') ?? 'Webhook URLs' }}</h5>
                                            <p class="text-muted small mb-3">{{ __('chatbot.webhook_urls_help') ?? 'Configure these in your provider dashboard. Append the secret query string for Infobip/Telegram if Authorization is not sent.' }}</p>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered bg-light">
                                                    <tbody>
                                                        @foreach($webhookUrls ?? [] as $provider => $url)
                                                        <tr>
                                                            <td class="fw-bold text-uppercase" style="width:120px">{{ $provider }}</td>
                                                            <td><code class="small user-select-all">{{ $url }}</code></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if(!config('services.chatbot.webhook_secret'))
                                            <div class="alert alert-warning py-2 small mb-0">{{ __('chatbot.webhook_secret_missing') ?? 'Set CHATBOT_WEBHOOK_SECRET in .env so webhook URLs include ?secret=…' }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    @can('setting.manage')
                                    <div class="row mt-3">
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-primary shadow"><i class="fa fa-save me-2"></i> {{ __('chatbot.save_config') ?? 'Save Config' }}</button>
                                        </div>
                                    </div>
                                    @endcan
                                </form>
                            </div>

                            {{-- Tab 2: Keywords --}}
                            <div class="tab-pane fade" id="keywords">
                                @can('setting.manage')
                                <div class="text-end mb-3">
                                    <button class="btn btn-primary btn-sm shadow" data-bs-toggle="modal" data-bs-target="#keywordModal"><i class="fa fa-plus me-1"></i> {{ __('chatbot.add_keyword') ?? 'Add Keyword' }}</button>
                                </div>
                                @endcan
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>{{ __('chatbot.keyword') ?? 'Keyword' }}</th>
                                                <th>{{ __('chatbot.menu_profile') ?? 'Menu Profile' }}</th>
                                                <th>{{ __('chatbot.allowed_roles') ?? 'Allowed Roles' }}</th>
                                                <th>{{ __('chatbot.language') ?? 'Language' }}</th>
                                                <th>{{ __('chatbot.response_message') ?? 'Message' }}</th>
                                                @can('setting.manage')
                                                <th class="text-end">{{ __('chatbot.actions') ?? 'Actions' }}</th>
                                                @endcan
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($keywords as $kw)
                                            <tr>
                                                <td><span class="badge badge-primary light fw-bold">{{ strtoupper($kw->keyword) }}</span></td>
                                                <td><span class="badge badge-info light">{{ $menuProfiles[$kw->menu_profile] ?? ucfirst(str_replace('_', ' ', $kw->menu_profile ?? 'student')) }}</span></td>
                                                <td><small>{{ $kw->allowedRoles->pluck('name')->join(', ') ?: __('chatbot.default_roles') }}</small></td>
                                                <td>{{ strtoupper($kw->language) }}</td>
                                                <td><small>{{ \Illuminate\Support\Str::limit($kw->welcome_message, 60) }}</small></td>
                                                @can('setting.manage')
                                                <td class="text-end">
                                                    <button class="btn btn-info btn-xs shadow edit-keyword" 
                                                        data-id="{{ $kw->id }}" 
                                                        data-keyword="{{ $kw->keyword }}" 
                                                        data-menu-profile="{{ $kw->menu_profile ?? 'student' }}"
                                                        data-allowed-roles="{{ $kw->allowedRoles->pluck('id')->join(',') }}"
                                                        data-lang="{{ $kw->language }}" 
                                                        data-msg="{{ $kw->welcome_message }}">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                    <form action="{{ route('chatbot.keywords.destroy', $kw->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this keyword?');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-xs shadow"><i class="fa fa-trash"></i></button>
                                                    </form>
                                                </td>
                                                @endcan
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">{{ __('chatbot.no_keywords') ?? 'No keywords' }}</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Tab 3: Chat Sessions --}}
                            @can('setting.view')
                            <div class="tab-pane fade" id="sessions">
                                {{-- Removed fixed min-width and table-responsive wrapper to allow native DataTable responsiveness --}}
                                <table id="sessionsTable" class="display table table-striped table-hover nowrap dt-responsive" style="width:100%;">
                                    <thead>
                                        <tr>
                                            @can('setting.manage')
                                            <th class="no-sort" style="width: 40px;" data-priority="1">
                                                <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                    <input type="checkbox" class="form-check-input" id="checkAllSessions">
                                                    <label class="form-check-label" for="checkAllSessions"></label>
                                                </div>
                                            </th>
                                            @endcan
                                            <th style="width: 30px;" data-priority="1">#</th>
                                            <th data-priority="2">{{ __('chatbot.phone') ?? 'Phone' }}</th>
                                            <th data-priority="1">{{ __('chatbot.status') ?? 'Status' }}</th>
                                            <th data-priority="3">{{ __('chatbot.user_type') ?? 'User Type' }}</th>
                                            <th>{{ __('chatbot.institute') ?? 'Institute ID' }}</th>
                                            <th>{{ __('chatbot.attempts') ?? 'Attempts' }}</th>
                                            <th>{{ __('chatbot.created_at') ?? 'Created At' }}</th>
                                            <th>{{ __('chatbot.updated_at') ?? 'Updated At' }}</th>
                                            <th>{{ __('chatbot.expires_at') ?? 'Expires At' }}</th>
                                            <th>{{ __('chatbot.otp') ?? 'OTP' }}</th>
                                            <th>{{ __('chatbot.locale') ?? 'Lang' }}</th>
                                            @can('setting.manage')
                                            <th class="text-end" data-priority="1">{{ __('chatbot.actions') ?? 'Actions' }}</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            @endcan

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Keyword Modal --}}
        @can('setting.manage')
        <div class="modal fade" id="keywordModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="keywordModalTitle">{{ __('chatbot.add_keyword') ?? 'Add Keyword' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('chatbot.keywords.store') }}" method="POST" id="keywordForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="formMethod">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('chatbot.keyword') ?? 'Keyword' }} <span class="text-danger">*</span></label>
                                <input type="text" name="keyword" id="inputKeyword" class="form-control" required placeholder="e.g. Digitex">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('chatbot.menu_profile') ?? 'Menu Profile' }} <span class="text-danger">*</span></label>
                                <select name="menu_profile" id="inputMenuProfile" class="form-control default-select" required>
                                    @foreach($menuProfiles as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('chatbot.menu_profile_help') ?? 'Defines which chatbot menu actions are shown after login.' }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('chatbot.allowed_roles') ?? 'Allowed Roles' }}</label>
                                <select name="allowed_role_ids[]" id="inputAllowedRoles" class="form-control default-select" multiple data-live-search="true">
                                    @foreach($assignableRoles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('chatbot.allowed_roles_help') ?? 'Leave empty to use default system roles for this menu profile. Select school roles (e.g. Account, Accountant) for custom access.' }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('chatbot.language') ?? 'Language' }} <span class="text-danger">*</span></label>
                                <select name="language" id="inputLang" class="form-control default-select" required>
                                    <option value="en">English (EN)</option>
                                    <option value="fr">French (FR)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('chatbot.response_message') ?? 'Message' }} <span class="text-danger">*</span></label>
                                <textarea name="welcome_message" id="inputMsg" class="form-control" rows="5" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcan

    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        if($.fn.selectpicker) $('.default-select').selectpicker();

        // Keyword Edit Handler
        $('.edit-keyword').click(function() {
            let id = $(this).data('id');
            let kw = $(this).data('keyword');
            let menuProfile = $(this).data('menu-profile');
            let allowedRoles = ($(this).data('allowed-roles') || '').toString().split(',').filter(Boolean);
            let lang = $(this).data('lang');
            let msg = $(this).data('msg');

            $('#keywordModalTitle').text("{{ __('chatbot.edit_keyword') ?? 'Edit Keyword' }}");
            $('#inputKeyword').val(kw);
            $('#inputMenuProfile').val(menuProfile).change();
            $('#inputAllowedRoles').val(allowedRoles).change();
            $('#inputLang').val(lang).change();
            if($.fn.selectpicker) {
                $('#inputMenuProfile').selectpicker('refresh');
                $('#inputAllowedRoles').selectpicker('refresh');
                $('#inputLang').selectpicker('refresh');
            }
            $('#inputMsg').val(msg);

            let updateUrl = "{{ url('chatbot/keywords') }}/" + id;
            $('#keywordForm').attr('action', updateUrl);
            $('#formMethod').val('PUT');

            $('#keywordModal').modal('show');
        });

        $('#keywordModal').on('hidden.bs.modal', function () {
            $('#keywordForm').attr('action', "{{ route('chatbot.keywords.store') }}");
            $('#formMethod').val('POST');
            $('#keywordForm')[0].reset();
            $('#keywordModalTitle').text("{{ __('chatbot.add_keyword') ?? 'Add Keyword' }}");
            if($.fn.selectpicker) {
                $('#inputMenuProfile').selectpicker('refresh');
                $('#inputAllowedRoles').selectpicker('refresh');
                $('#inputLang').selectpicker('refresh');
            }
        });

        // Chat Sessions DataTable (ScrollX Removed, Responsive + Modal Display Added)
        @can('setting.view')
        var sessionTable = $('#sessionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('chatbot.sessions.data') }}",
            responsive: {
                details: {
                    // Trigger a beautifully formatted Bootstrap modal when clicking the '+' icon
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function ( row ) {
                            var data = row.data();
                            return 'Session Information: ' + (data.phone_number || 'Unknown');
                        }
                    }),
                    renderer: function ( api, rowIdx, columns ) {
                        var data = api.row(rowIdx).data();
                        
                        var html = '<div class="p-3">';
                        
                        // 1. Injected Dynamic User Profile Card
                        html += '<h5 class="text-primary border-bottom pb-2 mb-3"><i class="fa fa-user-circle me-2"></i>User Profile</h5>';
                        html += '<div class="mb-4 bg-light p-3 rounded border text-dark shadow-sm">' + (data.user_details || 'No details available.') + '</div>';
                        
                        // 2. Original Hidden Session Columns
                        html += '<h5 class="text-primary border-bottom pb-2 mb-3"><i class="fa fa-server me-2"></i>Session Data</h5>';
                        html += '<table class="table table-sm table-striped table-bordered text-dark">';
                        $.each( columns, function ( i, col ) {
                            // Render columns that are hidden due to screen size, excluding checkbox & actions
                            if ( col.hidden && col.dataIndex !== 'action' && col.dataIndex !== 'checkbox' ) {
                                html += '<tr data-dt-row="'+col.rowIndex+'" data-dt-column="'+col.columnIndex+'">'+
                                    '<td width="35%" class="fw-bold">'+col.title+':</td> '+
                                    '<td>'+col.data+'</td>'+
                                '</tr>';
                            }
                        });
                        html += '</table></div>';
                        
                        return html;
                    }
                }
            },
            columns: [
                @can('setting.manage')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false },
                { data: 'phone_number', name: 'phone_number' },
                { data: 'status', name: 'status' },
                { data: 'user_type', name: 'user_type' },
                { data: 'institution_id', name: 'institution_id' },
                { data: 'attempts', name: 'attempts' },
                { data: 'created_at', name: 'created_at' },
                { data: 'updated_at', name: 'updated_at' },
                { data: 'expires_at', name: 'expires_at' },
                { data: 'otp', name: 'otp' },
                { data: 'locale', name: 'locale' },
                @can('setting.manage')
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                @endcan
            ],
            // Injecting buttons layout
            dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [
                @can('setting.manage')
                {
                    text: '<i class="fa fa-trash me-1"></i> {{ __('chatbot.delete_selected') ?? 'Delete Selected' }}',
                    className: 'btn btn-danger btn-sm shadow',
                    action: function ( e, dt, node, config ) {
                        var ids = [];
                        $('.single-checkbox:checked').each(function() {
                            ids.push($(this).val());
                        });

                        if(ids.length > 0) {
                            Swal.fire({
                                title: "{{ __('chatbot.are_you_sure') ?? 'Are you sure?' }}",
                                text: "{{ __('chatbot.bulk_delete_warning') ?? 'You want to delete these sessions?' }}",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                confirmButtonText: "{{ __('chatbot.yes_delete') ?? 'Yes, delete!' }}"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: "{{ route('chatbot.sessions.bulk_destroy') }}",
                                        type: 'POST',
                                        data: {
                                            _token: "{{ csrf_token() }}",
                                            ids: ids,
                                            _method: 'DELETE'
                                        },
                                        success: function(response) {
                                            Swal.fire("{{ __('chatbot.success') ?? 'Success!' }}", response.success || response.message, 'success');
                                            sessionTable.ajax.reload(null, false);
                                            $('#checkAllSessions').prop('checked', false);
                                        },
                                        error: function() {
                                            Swal.fire("{{ __('chatbot.error') ?? 'Error!' }}", "{{ __('chatbot.something_went_wrong') ?? 'An error occurred.' }}", 'error');
                                        }
                                    });
                                }
                            });
                        } else {
                            Swal.fire("Info", "{{ __('chatbot.select_least_one') ?? 'Select at least one record.' }}", "info");
                        }
                    }
                }
                @endcan
            ]
        });

        // CRITICAL FIX: Ensure DataTables perfectly aligns columns when the hidden Bootstrap Tab is opened
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e){
            if ($.fn.dataTable.isDataTable('#sessionsTable')) {
                $('#sessionsTable').DataTable().columns.adjust().responsive.recalc();
            }
        });

        // Delete/End Single Session Action
        $(document).on('click', '.end-session-btn', function() {
            let id = $(this).data('id');
            let url = "{{ url('chatbot/sessions') }}/" + id;

            Swal.fire({
                title: "{{ __('chatbot.confirm_end_session') ?? 'End this session?' }}",
                text: "The user will have to start over by sending a keyword.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, end it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(response) {
                            Swal.fire('Ended!', response.message, 'success');
                            sessionTable.ajax.reload(null, false);
                        }
                    });
                }
            });
        });

        // Check All / Uncheck All functionality
        $('#checkAllSessions').on('click', function() {
            $('.single-checkbox').prop('checked', this.checked);
        });

        $('#sessionsTable tbody').on('change', '.single-checkbox', function() {
            if (!this.checked) {
                $('#checkAllSessions').prop('checked', false);
            }
        });
        @endcan
    });
</script>
@endsection