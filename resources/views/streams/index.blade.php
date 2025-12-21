@extends('layout.layout')

@section('styles')
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css" rel="stylesheet">
@endsection

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        {{-- TITLE BAR --}}
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('stream.page_title') }}</h4>
                    <p class="mb-0">{{ __('stream.manage_subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                @can('stream.create')
                <a href="{{ route('streams.create') }}" class="btn btn-primary btn-rounded shadow-sm">
                    <i class="fa fa-plus me-2"></i> {{ __('stream.create_new') }}
                </a>
                @endcan
            </div>
        </div>

        {{-- STATS --}}
        <div class="row">
            <div class="col-xl-6 col-lg-6">
                <div class="widget-stat card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-primary text-primary"><i class="la la-list"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('stream.total_streams') }}</p>
                                <h4 class="mb-0">{{ $totalStreams }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="widget-stat card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="media ai-icon">
                            <span class="me-3 bgl-success text-success"><i class="la la-check-circle"></i></span>
                            <div class="media-body">
                                <p class="mb-1">{{ __('stream.active_streams') }}</p>
                                <h4 class="mb-0">{{ $activeStreams }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 pb-0 pt-4 px-4 bg-white">
                        <h4 class="card-title mb-0 fw-bold">{{ __('stream.stream_list') }}</h4>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="table-responsive">
                            <table id="streamTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        @can('stream.delete')
                                        <th style="width: 50px;" class="no-sort">
                                            <div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                <label class="form-check-label" for="checkAll"></label>
                                            </div>
                                        </th>
                                        @endcan
                                        <th>{{ __('stream.table_no') }}</th>
                                        <th>{{ __('stream.name') }}</th>
                                        <th>{{ __('stream.code') }}</th>
                                        <th>{{ __('stream.institution') }}</th>
                                        <th>{{ __('stream.status') }}</th>
                                        <th class="text-end">{{ __('stream.action') }}</th>
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#streamTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('streams.index') }}",
            columns: [
                @can('stream.delete')
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                @endcan
                { data: 'DT_RowIndex', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'code', name: 'code' },
                { data: 'institution_name', name: 'institution.name' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            // ... (Standard Buttons Logic same as previous modules)
        });
        
        // ... (Standard Delete Logic same as previous modules)
    });
</script>
@endsection