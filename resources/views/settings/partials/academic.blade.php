<form action="{{ route('settings.update') }}" method="POST" class="ajax-form" enctype="multipart/form-data">
    @csrf
    <div class="row">
        {{-- 1. Active Periods Management --}}
        <div class="col-md-12 mb-4">
            <h5 class="text-primary border-bottom pb-2">{{ __('settings.active_periods_title') ?? 'Active Periods for Marks Entry' }}</h5>
            <p class="text-muted small">{{ __('settings.active_periods_help') ?? 'Select the periods currently open for teachers to enter marks.' }}</p>
            
            <div class="row">
                @php
                    $markEntryPeriods = [
                        'p1' => 'Period 1', 'p2' => 'Period 2', 'p3' => 'Period 3',
                        'p4' => 'Period 4', 'p5' => 'Period 5', 'p6' => 'Period 6',
                        'trimester_exam_1' => 'Trimester 1 Exam',
                        'trimester_exam_2' => 'Trimester 2 Exam',
                        'trimester_exam_3' => 'Trimester 3 Exam',
                        'semester_exam_1' => 'Semester 1 Exam',
                        'semester_exam_2' => 'Semester 2 Exam',
                    ];
                    $periods = $markEntryPeriods + [
                        'trimester_1' => 'Trimester 1 (TR1)',
                        'trimester_2' => 'Trimester 2 (TR2)',
                        'trimester_3' => 'Trimester 3 (TR3)',
                        'semester_1' => 'Semester 1 (S1)',
                        'semester_2' => 'Semester 2 (S2)',
                    ];
                @endphp

                @foreach($markEntryPeriods as $key => $label)
                <div class="col-md-3 mb-2">
                    <div class="form-check custom-checkbox mb-3">
                        <input type="checkbox" name="active_periods[]" value="{{ $key }}" class="form-check-input" id="period_{{ $key }}" 
                            {{ in_array($key, $activePeriods) ? 'checked' : '' }}>
                        <label class="form-check-label" for="period_{{ $key }}">{{ $label }}</label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- 2. LMD Configuration --}}
        <div class="col-md-12 mb-4">
            <h5 class="text-primary border-bottom pb-2">{{ __('settings.lmd_config') }}</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.validation_threshold') }} (%)</label>
                    <input type="number" name="lmd_validation_threshold" class="form-control" value="{{ $lmdThreshold }}" step="0.1">
                    <small class="text-muted">{{ __('settings.threshold_hint') }}</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.resit_pass_percentage') }}</label>
                    <input type="number" name="resit_pass_percentage" class="form-control" value="{{ $resitPassPercentage ?? 50 }}" min="0" max="100" step="0.1">
                    <small class="text-muted">{{ __('settings.resit_pass_percentage_help') }}</small>
                </div>
            </div>
        </div>

        {{-- Report card seal --}}
        <div class="col-md-12 mb-4">
            <h5 class="text-primary border-bottom pb-2">{{ __('settings.report_seal_title') }}</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.report_seal_position') }}</label>
                    <select name="report_seal_position" class="form-control">
                        @foreach(['center' => __('settings.seal_center'), 'left' => __('settings.seal_left'), 'right' => __('settings.seal_right'), 'none' => __('settings.seal_none')] as $value => $label)
                            <option value="{{ $value }}" @selected(($reportSealPosition ?? 'center') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">{{ __('settings.report_seal_position_help') }}</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.report_seal_image') }}</label>
                    <input type="file" name="report_seal_image" class="form-control" accept="image/*">
                    <small class="text-muted">{{ __('settings.report_seal_image_help') }}</small>
                    @if(!empty($reportSealImage))
                        <div class="mt-2 d-flex align-items-center gap-3">
                            <img src="{{ asset('storage/' . $reportSealImage) }}" alt="seal" style="height:64px;width:64px;object-fit:contain;border:1px solid #eee;border-radius:8px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_report_seal_image" value="1" id="remove_report_seal_image">
                                <label class="form-check-label" for="remove_report_seal_image">{{ __('settings.remove_report_seal') }}</label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Grading Scale Configuration --}}
        <div class="col-md-12 mb-4">
            <h5 class="text-primary border-bottom pb-2 d-flex justify-content-between">
                {{ __('settings.grading_scale') }}
                <button type="button" class="btn btn-xs btn-primary" id="addGradeRow"><i class="fa fa-plus"></i></button>
            </h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('settings.grade_label') }}</th>
                            <th>{{ __('settings.min_percentage') }}</th>
                            <th>{{ __('settings.remark') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="gradingTableBody">
                        @foreach($gradingScale as $item)
                        <tr>
                            <td><input type="text" name="grade[]" class="form-control form-control-sm" value="{{ $item['grade'] }}" placeholder="A+"></td>
                            <td><input type="number" name="grade_min[]" class="form-control form-control-sm" value="{{ $item['min'] }}" step="0.1"></td>
                            <td><input type="text" name="grade_remark[]" class="form-control form-control-sm" value="{{ $item['remark'] }}"></td>
                            <td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Application scale (report card) --}}
        <div class="col-md-12 mb-4">
            <h5 class="text-primary border-bottom pb-2 d-flex justify-content-between">
                {{ __('settings.application_scale') }}
                <button type="button" class="btn btn-xs btn-primary" id="addAppRow"><i class="fa fa-plus"></i></button>
            </h5>
            <p class="text-muted small">{{ __('settings.application_scale_help') }}</p>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('settings.grade_label') }}</th>
                            <th>{{ __('settings.min_percentage') }}</th>
                            <th>{{ __('settings.remark') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="applicationTableBody">
                        @foreach($applicationScale as $item)
                        <tr>
                            <td><input type="text" name="app_grade[]" class="form-control form-control-sm" value="{{ $item['grade'] }}" placeholder="A"></td>
                            <td><input type="number" name="app_min[]" class="form-control form-control-sm" value="{{ $item['min'] }}" step="0.1"></td>
                            <td><input type="text" name="app_label[]" class="form-control form-control-sm" value="{{ $item['label'] ?? ($item['remark'] ?? '') }}"></td>
                            <td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Financial Restrictions for Reports --}}
        <div class="col-md-12 mb-4 mt-2">
            <h5 class="text-primary border-bottom pb-2"><i class="fa fa-lock me-2"></i>{{ __('settings.financial_restrictions') ?? 'Financial Restrictions' }}</h5>
            <div class="form-check form-switch custom-switch mt-3">
                <input class="form-check-input" type="checkbox" name="block_reports_on_debt" id="block_reports_on_debt" {{ (isset($blockReportsOnDebt) && $blockReportsOnDebt === true) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold text-dark ms-2 mt-1" style="cursor: pointer;" for="block_reports_on_debt">{{ __('settings.block_reports_on_debt') ?? 'Block Report Cards & Transcripts for Students with Unpaid Fees' }}</label>
            </div>
            <p class="text-muted small mt-2 ms-5">{{ __('settings.block_reports_on_debt_help') }}</p>

            <div id="reportMinPaidWrapper" class="mt-3 p-3 rounded bg-light border" style="{{ (isset($blockReportsOnDebt) && $blockReportsOnDebt === true) ? '' : 'display:none;' }}">
                <h6 class="fw-bold mb-1"><i class="fa fa-money-bill me-2 text-primary"></i>{{ __('settings.report_min_paid_title') }}</h6>
                <p class="text-muted small">{{ __('settings.report_min_paid_help') }}</p>
                <div class="row">
                    @foreach($periods as $key => $label)
                        <div class="col-md-3 mb-3">
                            <label class="form-label" for="min_paid_{{ $key }}">{{ $label }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ \App\Enums\CurrencySymbol::default() }}</span>
                                <input type="number"
                                       min="0"
                                       step="0.01"
                                       class="form-control"
                                       name="report_min_paid[{{ $key }}]"
                                       id="min_paid_{{ $key }}"
                                       value="{{ $reportMinPaidAmounts[$key] ?? '' }}"
                                       placeholder="0">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary btn-rounded">
                <i class="fa fa-save me-2"></i>{{ __('settings.save_settings') }}
            </button>
        </div>
    </div>
</form>

@include('settings.partials.assessment_periods')

<script>
    (function () {
        const toggle = document.getElementById('block_reports_on_debt');
        const wrapper = document.getElementById('reportMinPaidWrapper');
        if (toggle && wrapper) {
            toggle.addEventListener('change', function () {
                wrapper.style.display = this.checked ? '' : 'none';
            });
        }
    })();

    document.getElementById('addGradeRow').addEventListener('click', function() {
        const tbody = document.getElementById('gradingTableBody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="grade[]" class="form-control form-control-sm" placeholder="New"></td>
            <td><input type="number" name="grade_min[]" class="form-control form-control-sm" step="0.1"></td>
            <td><input type="text" name="grade_remark[]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
    });

    document.getElementById('addAppRow').addEventListener('click', function() {
        const tbody = document.getElementById('applicationTableBody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="app_grade[]" class="form-control form-control-sm" placeholder="A"></td>
            <td><input type="number" name="app_min[]" class="form-control form-control-sm" step="0.1"></td>
            <td><input type="text" name="app_label[]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
        }
    });
</script>
