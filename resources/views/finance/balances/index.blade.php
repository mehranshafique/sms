@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.balance_overview') }}</h4>
                    <p class="mb-0">{{ __('finance.class_wise_breakdown') }}</p>
                </div>
            </div>
        </div>

        {{-- ROW 1: Class List Table --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ __('finance.all_classes') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="classTable" class="display" style="min-width: 845px">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('finance.class_name') }}</th>
                                        <th>{{ __('finance.students_count') }}</th>
                                        <th>{{ __('finance.paid_students') ?? 'Paid Students' }}</th> {{-- New Column --}}
                                        <th>{{ __('finance.total_invoiced') }}</th>
                                        <th>{{ __('finance.total_collected') }}</th>
                                        <th>{{ __('finance.total_outstanding') }}</th>
                                        <th class="text-end">{{ __('finance.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: Class Details (Hidden by default, shown via AJAX) --}}
        <div class="row d-none" id="detailsSection">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        {{-- Title updated via JS --}}
                        <h4 class="card-title text-white mb-0" id="selectedClassName">{{ __('finance.class_details') }}</h4>
                        <div class="d-flex align-items-center">
                            {{-- Search Input --}}
                            <div class="input-group input-group-sm me-3" style="width: 250px;">
                                <span class="input-group-text border-0 bg-white"><i class="fa fa-search"></i></span>
                                <input type="text" id="studentSearchInput" class="form-control border-0" placeholder="Search Name or ID...">
                            </div>
                            <button type="button" class="btn btn-xs btn-light text-primary" id="closeDetails"><i class="fa fa-times"></i> {{ __('finance.close') }}</button>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        {{-- Tabs Container --}}
                        <ul class="nav nav-tabs" id="installmentTabs" role="tablist">
                            {{-- Generated via JS --}}
                        </ul>

                        {{-- Tab Content --}}
                        <div class="tab-content mt-4" id="installmentTabContent">
                            {{-- Generated via JS --}}
                            <div class="text-center p-5">
                                <i class="fa fa-spinner fa-spin fa-2x"></i> {{ __('finance.loading_details') }}
                            </div>
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
        
        // 1. Initialize Class Table
        var table = $('#classTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('finance.balances.index') }}",
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { 
                    data: 'class_name', 
                    name: 'name',
                    render: function(data, type, row) {
                        // Format is already "Grade Section" from controller if available,
                        // or "Section (Grade)" depending on logic.
                        // Controller now sets 'class_name' as "Grade Section" directly or "Section"
                        return data;
                    }
                },
                { data: 'students_count', searchable: false },
                { 
                    data: 'paid_students_count', 
                    searchable: false,
                    render: function(data, type, row) {
                        return '<span class="badge badge-success light">' + data + '</span>';
                    }
                },
                { data: 'total_invoiced', searchable: false },
                { data: 'total_collected', searchable: false },
                { data: 'balance', searchable: false },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ]
        });

        // 2. Handle "View Details" Click
        $(document).on('click', '.view-class-btn', function() {
            let classId = $(this).data('id');
            let className = $(this).data('name'); // Now contains "Grade Section"
            
            // UI Updates: Set Title to "Class Details: Grade Section"
            $('#selectedClassName').text("{{ __('finance.class_details') }}: " + className);
            
            $('#detailsSection').removeClass('d-none');
            $('#studentSearchInput').val(''); // Clear search on open
            
            // Scroll to details
            $('html, body').animate({
                scrollTop: $("#detailsSection").offset().top - 100
            }, 500);

            // Fetch Data
            loadClassDetails(classId);
        });

        // 3. Close Details
        $('#closeDetails').click(function(){
            $('#detailsSection').addClass('d-none');
        });

        // 4. AJAX Load Function
        function loadClassDetails(classId) {
            let tabsList = $('#installmentTabs');
            let tabContent = $('#installmentTabContent');
            
            tabsList.empty();
            tabContent.html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></div>');

            $.ajax({
                url: "/finance/balances/class/" + classId,
                type: "GET",
                success: function(response) {
                    tabsList.empty();
                    tabContent.empty();

                    if(response.tabs.length === 0) {
                        tabContent.html('<div class="alert alert-warning">{{ __('finance.no_fee_structures_class') }}</div>');
                        return;
                    }

                    // A. Build Tabs
                    response.tabs.forEach((tab, index) => {
                        let isActive = index === 0 ? 'active' : '';
                        
                        let tabHtml = `
                            <li class="nav-item">
                                <a class="nav-link ${isActive}" id="tab-${tab.id}" data-bs-toggle="tab" href="#content-${tab.id}" role="tab">
                                    ${tab.label}
                                </a>
                            </li>`;
                        tabsList.append(tabHtml);
                    });

                    // B. Build Content (One Table per Tab)
                    response.tabs.forEach((tab, index) => {
                        let isActive = index === 0 ? 'show active' : '';
                        
                        // Build Student Rows for this Tab
                        let rowsHtml = '';
                        response.students.forEach(item => {
                            let student = item.student;
                            let status = item.statuses[tab.id]; // {label, style, paid, due}
                            let photoUrl = student.photo ? '/storage/'+student.photo : null;
                            let avatar = photoUrl 
                                ? `<img src="${photoUrl}" class="rounded-circle me-2" width="35" height="35">`
                                : `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px">${student.name.charAt(0)}</div>`;

                            // Action Link (Go to Student Finance Dashboard)
                            let actionUrl = "/finance/student/" + student.id + "/dashboard";

                            rowsHtml += `
                                <tr class="student-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            ${avatar}
                                            <div>
                                                <h6 class="mb-0 fs-14 student-name">${student.name}</h6>
                                                <small class="text-muted student-id">${student.admission_no}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-${status.style} light">${status.label}</span></td>
                                    <td class="text-success fw-bold">${status.paid}</td>
                                    <td class="text-danger fw-bold">${status.due}</td>
                                    <td>
                                        <a href="${actionUrl}" class="btn btn-primary btn-xs sharp shadow" target="_blank" title="{{ __('finance.view_dashboard') }}">
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>`;
                        });

                        let contentHtml = `
                            <div class="tab-pane fade ${isActive}" id="content-${tab.id}" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover verticle-middle student-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>{{ __('finance.student_identity') }}</th>
                                                <th>{{ __('finance.status') }}</th>
                                                <th>{{ __('finance.paid_amount') ?? 'Paid' }}</th>
                                                <th>{{ __('finance.due_amount') ?? 'Balance' }}</th>
                                                <th>{{ __('finance.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${rowsHtml}
                                        </tbody>
                                    </table>
                                </div>
                            </div>`;
                        
                        tabContent.append(contentHtml);
                    });
                },
                error: function() {
                    tabContent.html('<div class="alert alert-danger">{{ __('finance.error_loading') }}</div>');
                }
            });
        }

        // 5. Client-Side Search Function
        $('#studentSearchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            
            // Search in all tables within the active tab content
            $('.student-table tbody tr').filter(function() {
                var name = $(this).find('.student-name').text().toLowerCase();
                var id = $(this).find('.student-id').text().toLowerCase();
                $(this).toggle(name.indexOf(value) > -1 || id.indexOf(value) > -1)
            });
        });
    });
</script>
@endsection