@props(['dossier'])

<div class="card shadow-sm border-0 mt-3" style="border-radius:12px;">
    <div class="card-header bg-white border-0 pt-3 px-4">
        <h6 class="mb-0 fw-bold text-primary"><i class="fa fa-folder-open me-2"></i>{{ __('requests.student_dossier') }}</h6>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.class_section') }}</small>
                <div class="fw-bold">{{ $dossier['class_section'] ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.parent') }}</small>
                <div class="fw-bold">{{ $dossier['parent_name'] ?? '—' }}</div>
                @if(!empty($dossier['parent_phones']))
                    <small class="text-muted">{{ implode(' / ', $dossier['parent_phones']) }}</small>
                @endif
            </div>
            <div class="col-md-4">
                <div class="bg-light rounded p-3 h-100">
                    <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.total_fees') }}</small>
                    <div class="fw-bold">{{ $dossier['total_fees'] ?? '0.00' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light rounded p-3 h-100">
                    <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.amount_paid') }}</small>
                    <div class="fw-bold text-success">{{ $dossier['amount_paid'] ?? '0.00' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light rounded p-3 h-100">
                    <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.outstanding') }}</small>
                    <div class="fw-bold text-danger">{{ $dossier['outstanding_balance'] ?? '0.00' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.attendance_summary') }}</small>
                <div>
                    {{ __('requests.attendance_present') }}: <strong>{{ $dossier['attendance']['present'] ?? 0 }}</strong> |
                    {{ __('requests.attendance_absent') }}: <strong>{{ $dossier['attendance']['absent'] ?? 0 }}</strong> |
                    <strong>{{ $dossier['attendance']['percentage'] ?? 0 }}%</strong>
                </div>
            </div>
            <div class="col-md-6">
                <small class="text-muted text-uppercase d-block mb-1">{{ __('requests.discipline_incidents') }}</small>
                <div class="fw-bold">{{ $dossier['discipline_incidents'] ?? 0 }}</div>
            </div>
        </div>

        @if(!empty($dossier['recent_payments']) && count($dossier['recent_payments']))
            <h6 class="mt-4 mb-2 fw-bold">{{ __('requests.recent_payments') }}</h6>
            <ul class="list-group list-group-flush mb-0">
                @foreach($dossier['recent_payments'] as $payment)
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between border-0 border-bottom">
                        <span class="text-muted">{{ $payment['date'] }} — {{ $payment['invoice'] }}</span>
                        <strong>{{ $payment['amount'] }}</strong>
                    </li>
                @endforeach
            </ul>
        @endif

        @if(!empty($dossier['previous_requests']) && count($dossier['previous_requests']))
            <h6 class="mt-4 mb-2 fw-bold">{{ __('requests.previous_requests') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>{{ __('requests.ticket_number') }}</th>
                            <th>{{ __('requests.request_type') }}</th>
                            <th>{{ __('requests.status') }}</th>
                            <th>{{ __('requests.date_submitted') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dossier['previous_requests'] as $prev)
                            <tr>
                                <td>{{ $prev['ticket'] }}</td>
                                <td>{{ $prev['type'] }}</td>
                                <td>{{ $prev['status'] }}</td>
                                <td>{{ $prev['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
