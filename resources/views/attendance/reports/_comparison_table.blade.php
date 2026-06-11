@if(!empty($comparisonTable))
<div class="card shadow-sm mt-3 mb-3">
    <div class="card-header border-0">
        <h5 class="card-title mb-0">{{ __('attendance.summary_comparison_title') }}</h5>
        <small class="text-muted">{{ $comparisonTable['period_label'] }} vs {{ $comparisonTable['previous_label'] }}</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __('attendance.metric') }}</th>
                        <th class="text-center">{{ $comparisonTable['period_label'] }}</th>
                        <th class="text-center">{{ $comparisonTable['previous_label'] }}</th>
                        <th class="text-center">{{ __('attendance.change') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparisonTable['rows'] as $row)
                        @php
                            $change = $row['change'];
                            $changeClass = $change > 0 && $row['metric'] !== 'days_absent' ? 'text-success' : ($change < 0 && $row['metric'] !== 'days_absent' ? 'text-danger' : ($change > 0 && $row['metric'] === 'days_absent' ? 'text-danger' : 'text-muted'));
                            if ($row['metric'] === 'attendance_percentage') {
                                $changeClass = $change > 0 ? 'text-success' : ($change < 0 ? 'text-danger' : 'text-muted');
                            }
                            $suffix = $row['metric'] === 'attendance_percentage' ? '%' : '';
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $row['label'] }}</td>
                            <td class="text-center">{{ $row['current'] }}{{ $suffix }}</td>
                            <td class="text-center">{{ $row['previous'] }}{{ $suffix }}</td>
                            <td class="text-center {{ $changeClass }}">
                                @if($change > 0)+@endif{{ $change }}{{ $suffix }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
