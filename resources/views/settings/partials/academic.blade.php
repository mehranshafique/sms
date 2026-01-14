<form action="{{ route('settings.update') }}" method="POST" class="ajax-form">
    @csrf
    <div class="row">
        <div class="col-md-12 mb-4">
            <h5 class="text-primary border-bottom pb-2">{{ __('settings.lmd_config') }}</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.validation_threshold') }} (%)</label>
                    <input type="number" name="lmd_validation_threshold" class="form-control" value="{{ $lmdThreshold }}" step="0.1">
                    <small class="text-muted">{{ __('settings.threshold_hint') }}</small>
                </div>
            </div>
        </div>

        <div class="col-md-12">
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

        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-primary">{{ __('settings.save_settings') }}</button>
        </div>
    </div>
</form>

<script>
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

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
        }
    });
</script>