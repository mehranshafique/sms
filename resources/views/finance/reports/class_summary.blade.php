@extends('layout.layout')

@section('content')
<style>
    @media print {
        @page {
            size: landscape;
            margin: 0.5cm;
        }
        
        body {
            visibility: hidden;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }

        /* Using fixed positioning to ensure the print section covers 
           the entire page, ignoring sidebar margins or other layout spacing.
        */
        #printSection {
            visibility: visible;
            position: fixed;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            margin: 0;
            padding: 20px;
            background-color: #fff;
            z-index: 99999;
            overflow: visible !important; /* Ensure content isn't clipped */
        }

        #printSection * {
            visibility: visible;
        }

        .no-print {
            display: none !important;
        }

        /* Table Styling for Print */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 0 !important;
            background-color: #fff !important;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
            padding: 4px 6px !important; /* Reduced padding */
            font-size: 10pt !important; /* Optimized font size */
            color: #000 !important;
            vertical-align: middle !important;
        }

        .table-bordered th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            font-weight: bold !important;
            text-align: center !important;
        }

        /* Ensure specific columns don't wrap awkwardly */
        .table-bordered td {
            white-space: normal !important;
        }

        /* Badges should look like text or simple boxes */
        .badge {
            border: 1px solid #000;
            color: #000 !important;
            background: transparent !important;
            padding: 2px 4px;
        }

        /* Remove shadow/borders from containers */
        .card, .card-body {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Hide scrollbars */
        ::-webkit-scrollbar { display: none; }
    }
    
    /* Screen specific fixes */
    .table th, .table td {
        vertical-align: middle;
    }

    /* Enhanced Visuals for Screen */
    @media screen {
        /* Header Colors */
        .header-identity { background-color: #34495e !important; color: white !important; }
        .header-contact { background-color: #95a5a6 !important; color: white !important; }
        .header-payment { background-color: #27ae60 !important; color: white !important; }
        .header-cumulative { background-color: #2980b9 !important; color: white !important; }
        .header-remaining { background-color: #f39c12 !important; color: white !important; }
        .header-annual { background-color: #7f8c8d !important; color: white !important; }
        .header-debt { background-color: #c0392b !important; color: white !important; }
        
        /* Column Background Tints for Readability */
        .col-bg-payment { background-color: rgba(39, 174, 96, 0.08) !important; }
        .col-bg-cumulative { background-color: rgba(41, 128, 185, 0.08) !important; }
        .col-bg-remaining { background-color: rgba(243, 156, 18, 0.08) !important; }
        .col-bg-debt { background-color: rgba(192, 57, 43, 0.08) !important; }
        .col-bg-annual { background-color: rgba(127, 140, 141, 0.08) !important; }

        /* Row Hover Effect */
        .table-hover tbody tr:hover td {
            background-color: #e8e8e8 !important;
            transition: background-color 0.2s;
        }
        
        /* Specific Text Colors */
        .text-payment { color: #219150 !important; }
        .text-cumulative { color: #1f618d !important; }
        .text-remaining { color: #d68910 !important; }
        .text-debt { color: #c0392b !important; }
    }
</style>

<div class="content-body">
    <div class="container-fluid">
        
        {{-- Page Title --}}
        <div class="row page-titles mx-0 no-print">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('finance.class_financial_report') }}</h4>
                    <p class="mb-0">{{ __('finance.report_subtitle') }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('finance.reports.class_summary') }}" method="GET">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">{{ __('finance.select_class') }}</label>
                                    <select name="class_section_id" class="form-control default-select" required>
                                        <option value="">{{ __('finance.choose_class') }}</option>
                                        @foreach($classes as $id => $name)
                                            <option value="{{ $id }}" {{ request('class_section_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100"><i class="fa fa-filter me-2"></i> {{ __('finance.generate_report') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(request('class_section_id') && !empty($reportData))
            
            @php
                $currency = \App\Enums\CurrencySymbol::default();
            @endphp

            {{-- Summary Cards (Hidden in Print) --}}
            <div class="row no-print">
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-success text-white shadow-sm">
                        <div class="card-body">
                            <div class="media">
                                <span class="me-3">
                                    <i class="la la-money"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1 text-white opacity-75">{{ __('finance.today_payment') }}</p>
                                    <h3 class="text-white">{{ $currency }} {{ number_format($totals['today_payment'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-primary text-white shadow-sm">
                        <div class="card-body">
                            <div class="media">
                                <span class="me-3">
                                    <i class="la la-wallet"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1 text-white opacity-75">{{ __('finance.cumulative_paid') }}</p>
                                    <h3 class="text-white">{{ $currency }} {{ number_format($totals['cumulative_paid'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-warning text-white shadow-sm">
                        <div class="card-body">
                            <div class="media">
                                <span class="me-3">
                                    <i class="la la-hourglass-half"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1 text-white opacity-75">{{ __('finance.remaining_fees') }}</p>
                                    <h3 class="text-white">{{ $currency }} {{ number_format($totals['remaining'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="widget-stat card bg-danger text-white shadow-sm">
                        <div class="card-body">
                            <div class="media">
                                <span class="me-3">
                                    <i class="la la-exclamation-circle"></i>
                                </span>
                                <div class="media-body text-white">
                                    <p class="mb-1 text-white opacity-75">{{ __('finance.previous_debt') }}</p>
                                    <h3 class="text-white">{{ $currency }} {{ number_format($totals['previous_debt'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detailed Table --}}
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center no-print bg-white pt-4 px-4">
                            @php
                                $selectedClassName = '';
                                if(request('class_section_id') && isset($classes[request('class_section_id')])) {
                                    $selectedClassName = $classes[request('class_section_id')];
                                }
                            @endphp
                            <h4 class="card-title text-primary">
                                {{ __('finance.financial_overview') }} 
                                @if($selectedClassName)
                                    <span class="text-danger ms-2" style="font-weight: 800;">- {{ $selectedClassName }}</span>
                                @endif
                            </h4>
                            <div>
                                <button class="btn btn-secondary btn-sm me-2 shadow-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print Report</button>
                                <button class="btn btn-info btn-sm text-white shadow-sm" id="exportBtn"><i class="fa fa-file-excel-o me-1"></i> Export CSV</button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive" id="printSection">
                                {{-- Print Header (Visible only on print) --}}
                                <div class="d-none d-print-block text-center mb-4">
                                    <h3>{{ __('finance.class_financial_report') }}</h3>
                                    @if($selectedClassName)
                                        <h4>{{ $selectedClassName }}</h4>
                                    @endif
                                    <p>{{ __('finance.financial_overview') }} - {{ date('d M, Y') }}</p>
                                </div>

                                <table class="table table-bordered verticle-middle table-responsive-sm w-100 table-hover" id="financialTable">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #ccc;">
                                            <th class="header-identity text-center" style="width: 40px; border-top-left-radius: 8px;">#</th>
                                            <th class="header-identity">{{ __('finance.student_identity') }}</th>
                                            <th class="header-contact">{{ __('finance.parent_guardian') }}</th>
                                            <th class="text-center header-payment">{{ __('finance.today_payment') }}</th>
                                            <th class="text-center header-cumulative">{{ __('finance.cumulative_paid') }}</th>
                                            <th class="text-center header-remaining">{{ __('finance.remaining_fees') }}</th>
                                            <th class="text-center header-annual">{{ __('finance.annual_fee') }}</th>
                                            <th class="text-center header-debt" style="border-top-right-radius: 8px;">{{ __('finance.previous_debt') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($reportData as $index => $row)
                                        <tr>
                                            <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong class="text-dark">{{ $row['name'] }}</strong>
                                                    <span class="text-muted fs-12">{{ $row['student_id'] }}</span>
                                                    <span class="badge badge-xs badge-{{ $row['payment_mode'] == 'global' ? 'info' : 'light' }} mt-1 w-fit-content no-print border">
                                                        {{ ucfirst($row['payment_mode']) }}
                                                    </span>
                                                    {{-- Print-only text for badge --}}
                                                    <span class="d-none d-print-inline fs-10">({{ ucfirst($row['payment_mode']) }})</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark">{{ $row['parent_name'] }}</span>
                                                    <small class="text-muted">{{ $row['parent_phone'] }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center fw-bold col-bg-payment">
                                                @if($row['today_payment'] > 0)
                                                    <span class="text-payment">+ {{ $currency }} {{ number_format($row['today_payment'], 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-bold col-bg-cumulative text-cumulative">
                                                {{ $currency }} {{ number_format($row['cumulative_paid'], 2) }}
                                            </td>
                                            <td class="text-center col-bg-remaining">
                                                @if($row['remaining'] > 0)
                                                    <span class="badge badge-sm badge-warning text-white no-print shadow-sm">
                                                        {{ $currency }} {{ number_format($row['remaining'], 2) }}
                                                    </span>
                                                    <span class="d-none d-print-inline fw-bold">
                                                        {{ $currency }} {{ number_format($row['remaining'], 2) }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-sm badge-success text-white no-print shadow-sm">
                                                        <i class="fa fa-check me-1"></i> {{ __('finance.paid') }}
                                                    </span>
                                                    <span class="d-none d-print-inline text-success">{{ $currency }} 0.00</span>
                                                @endif
                                            </td>
                                            <td class="text-center col-bg-annual text-muted fw-bold">
                                                {{ $currency }} {{ number_format($row['annual_fee'], 2) }}
                                            </td>
                                            <td class="text-center col-bg-debt">
                                                @if($row['previous_debt'] > 0)
                                                    <span class="text-danger fw-bold"><i class="fa fa-exclamation-triangle me-1"></i> {{ $currency }} {{ number_format($row['previous_debt'], 2) }}</span>
                                                @else
                                                    <span class="text-success"><i class="fa fa-check-circle"></i></span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-dark text-white">
                                        <tr>
                                            <th colspan="3" class="text-end text-white text-uppercase" style="border-bottom-left-radius: 8px;">{{ __('finance.totals') }}:</th>
                                            <th class="text-center text-white header-payment">{{ $currency }} {{ number_format($totals['today_payment'], 2) }}</th>
                                            <th class="text-center text-white header-cumulative">{{ $currency }} {{ number_format($totals['cumulative_paid'], 2) }}</th>
                                            <th class="text-center text-white header-remaining">{{ $currency }} {{ number_format($totals['remaining'], 2) }}</th>
                                            <th class="text-center text-white header-annual">{{ $currency }} {{ number_format($totals['annual_fee'], 2) }}</th>
                                            <th class="text-center text-white header-debt" style="border-bottom-right-radius: 8px;">{{ $currency }} {{ number_format($totals['previous_debt'], 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Visualization Graphs (Hidden in Print) --}}
            <div class="row no-print">
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header border-0">
                            <h4 class="card-title text-primary">{{ __('finance.fee_collection_analysis') }}</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="collectionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header border-0">
                            <h4 class="card-title text-primary">{{ __('finance.pending_vs_collected') }}</h4>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        @elseif(request('class_section_id'))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning text-center p-5 shadow-sm">
                        <i class="fa fa-exclamation-triangle fa-2x mb-3 text-warning"></i>
                        <h4>{{ __('finance.no_data_found') }}</h4>
                        <p>{{ __('finance.no_financial_records_found') }}</p>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('vendor/chart.js/Chart.bundle.min.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // Export to CSV Logic (Simple Client-Side)
        document.getElementById('exportBtn')?.addEventListener('click', function() {
            let csv = [];
            let rows = document.querySelectorAll("#financialTable tr");
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++) 
                    row.push('"' + cols[j].innerText.replace(/\n/g, ' ').trim() + '"');
                csv.push(row.join(","));        
            }

            let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            let downloadLink = document.createElement("a");
            downloadLink.download = "financial_report.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        });

        // Initialize Charts if data exists
        @if(isset($totals))
            const totalCollected = {{ $totals['cumulative_paid'] }};
            const totalRemaining = {{ $totals['remaining'] }};
            const previousDebt = {{ $totals['previous_debt'] }};
            
            // 1. Bar Chart: Collection
            new Chart(document.getElementById("collectionChart"), {
                type: 'bar',
                data: {
                    labels: ["Collected", "Remaining", "Debt"],
                    datasets: [{
                        label: "Amount ({{ \App\Enums\CurrencySymbol::default() }})",
                        backgroundColor: ["#2980b9", "#f39c12", "#c0392b"],
                        data: [totalCollected, totalRemaining, previousDebt]
                    }]
                },
                options: {
                    responsive: true,
                    legend: { display: false },
                    title: { display: true, text: '{{ __('finance.fee_collection_analysis') }}' },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });

            // 2. Doughnut Chart: Status
            new Chart(document.getElementById("statusChart"), {
                type: 'doughnut',
                data: {
                    labels: ["Paid", "Pending"],
                    datasets: [{
                        backgroundColor: ["#27ae60", "#f39c12"],
                        data: [totalCollected, (totalRemaining + previousDebt)]
                    }]
                },
                options: {
                    responsive: true
                }
            });
        @endif
    });
</script>
@endsection