@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('audit.page_title') }}</h4>
                    <p class="mb-0">{{ __('audit.subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('audit.module') }}</label>
                            <select name="module" class="form-control default-select">
                                <option value="">{{ __('audit.all_modules') }}</option>
                                @foreach($modules as $mod)
                                    <option value="{{ $mod }}">{{ $mod }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('audit.date_from') }}</label>
                            <input type="text" name="date_from" class="form-control datepicker" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('audit.date_to') }}</label>
                            <input type="text" name="date_to" class="form-control datepicker" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" id="filterBtn" class="btn btn-primary w-100"><i class="fa fa-filter me-2"></i> {{ __('audit.filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="auditTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('audit.date') }}</th>
                                        <th>{{ __('audit.user') }}</th>
                                        <th>{{ __('audit.institution') }}</th>
                                        <th>{{ __('audit.module') }}</th>
                                        <th>{{ __('audit.action') }}</th>
                                        <th>{{ __('audit.description') }}</th>
                                        <th>{{ __('audit.ip') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        var table = $('#auditTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('audit-logs.index') }}",
                data: function(d) {
                    d.module = $('select[name="module"]').val();
                    d.date_from = $('input[name="date_from"]').val();
                    d.date_to = $('input[name="date_to"]').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'user_name', name: 'user.name' },
                { data: 'institution_name', name: 'institution.code' },
                { data: 'module', name: 'module' },
                { data: 'action', name: 'action' },
                { data: 'details', name: 'description' },
                { data: 'ip_address', name: 'ip_address' },
            ],
            order: [[0, 'desc']], // Latest first
            language: {
                paginate: { next: '<i class="fa fa-angle-right"></i>', previous: '<i class="fa fa-angle-left"></i>' }
            }
        });

        $('#filterBtn').on('click', function() {
            table.draw();
        });
    });
</script>
@endsection