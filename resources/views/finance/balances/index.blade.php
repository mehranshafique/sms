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
                                        <th>{{ __('finance.paid_students') ?? 'Paid Students' }}</th>
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
            let className = $(this).data('name');
            
            $('#selectedClassName').text("{{ __('finance.class_details') }}: " + className);
            $('#detailsSection').removeClass('d-none');
            $('#studentSearchInput').val('');
            
            $('html, body').animate({
                scrollTop: $("#detailsSection").offset().top - 100
            }, 500);

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
                        
                        // Get students specific to this tab
                        let studentsForTab = response.students_by_tab[tab.id] || [];

                        // --- CALCULATE TAB STATISTICS ---
                        let statTotalStudents = 0;
                        let statTotalPaidStudents = 0;
                        let statTotalPaid = 0;
                        let statTotalDue = 0;
                        let currencySymbol = '';

                        // Build Student Rows for this Tab
                        let rowsHtml = '';
                        
                        studentsForTab.forEach(item => {
                            let student = item.student;
                            let status = item.status; // {label, style, paid, due, has_invoice}
                            
                            statTotalStudents++;
                            
                            // Parse money values safely
                            let pVal = parseFloat(String(status.paid).replace(/[^0-9.-]+/g,"")) || 0;
                            let dVal = parseFloat(String(status.due).replace(/[^0-9.-]+/g,"")) || 0;
                            
                            if(status.has_invoice && dVal <= 0.01) {
                                statTotalPaidStudents++;
                            }

                            statTotalPaid += pVal;
                            statTotalDue += dVal;

                            // Capture symbol if not set
                            if(!currencySymbol && (String(status.paid).match(/[^0-9.-]+/))) {
                                currencySymbol = String(status.paid).replace(/[0-9.,-]+/g, '').trim();
                            }

                            let photoUrl = student.photo ? '/storage/'+student.photo : null;
                            let avatar = photoUrl 
                                ? `<img src="${photoUrl}" class="rounded-circle me-2" width="35" height="35">`
                                : `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px">${student.name.charAt(0)}</div>`;

                            let actionUrl = "/finance/student/" + student.id + "/dashboard";
                            let statementUrl = "/finance/student/" + student.id + "/statement"; // Statement URL

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
                                        <div class="d-flex">
                                            <a href="${actionUrl}" class="btn btn-primary btn-xs sharp shadow me-1" target="_blank" title="{{ __('finance.view_dashboard') }}">
                                                <i class="fa fa-tachometer-alt"></i>
                                            </a>
                                            <a href="${statementUrl}" class="btn btn-secondary btn-xs sharp shadow" target="_blank" title="{{ __('finance.student_statement') }}">
                                                <i class="fa fa-file-text-o"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>`;
                        });

                        // Calculate Invoice Total (Paid + Due)
                        let statTotalInvoiced = statTotalPaid + statTotalDue;

                        // Formatting Helper
                        const fmt = (num) => (currencySymbol ? currencySymbol + ' ' : '') + num.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

                        let contentHtml = `
                            <div class="tab-pane fade ${isActive}" id="content-${tab.id}" role="tabpanel">
                                
                                {{-- Tab Helper Text --}}
                                <div class="alert alert-info border-0 bg-info-light text-info mb-4">
                                    <i class="fa fa-info-circle me-2"></i> ${tab.description}
                                </div>

                                {{-- Compact Summary Stats Row --}}
                                <div class="row mb-4">
                                    <div class="col-xl-3 col-sm-6">
                                        <div class="card widget-flat border-0 shadow-sm mb-3">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-box bg-light-primary text-primary rounded-circle me-3 p-3">
                                                        <i class="fa fa-users fa-2x"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-muted fs-12 text-uppercase">{{ __('finance.students_count') }}</span>
                                                            <h4 class="mb-0 fw-bold text-dark">${statTotalStudents}</h4>
                                                        </div>
                                                        <div class="d-flex justify-content-between border-top pt-1">
                                                            <span class="text-success fs-12 text-uppercase">{{ __('finance.paid_students') ?? 'Paid' }}</span>
                                                            <h5 class="mb-0 fw-bold text-success">${statTotalPaidStudents}</h5>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-3 col-sm-6">
                                        <div class="card widget-flat border-0 shadow-sm mb-3">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-box bg-light-success text-success rounded-circle me-3 p-3">
                                                        <i class="fa fa-check-circle fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-muted mb-1 fs-12 text-uppercase">{{ __('finance.paid_amount') }}</p>
                                                        <h4 class="mb-0 fw-bold text-dark">${fmt(statTotalPaid)}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-sm-6">
                                        <div class="card widget-flat border-0 shadow-sm mb-3">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-box bg-light-danger text-danger rounded-circle me-3 p-3">
                                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-muted mb-1 fs-12 text-uppercase">{{ __('finance.due_amount') }}</p>
                                                        <h4 class="mb-0 fw-bold text-dark">${fmt(statTotalDue)}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-sm-6">
                                        <div class="card widget-flat border-0 shadow-sm mb-3">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-box bg-light-info text-info rounded-circle me-3 p-3">
                                                        <i class="fa fa-file-invoice-dollar fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-muted mb-1 fs-12 text-uppercase">{{ __('finance.total_invoiced') }}</p>
                                                        <h4 class="mb-0 fw-bold text-dark">${fmt(statTotalInvoiced)}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
            
            $('.student-table tbody tr').filter(function() {
                var name = $(this).find('.student-name').text().toLowerCase();
                var id = $(this).find('.student-id').text().toLowerCase();
                $(this).toggle(name.indexOf(value) > -1 || id.indexOf(value) > -1)
            });
        });
    });
</script>
@endsection