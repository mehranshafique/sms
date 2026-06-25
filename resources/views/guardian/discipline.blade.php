@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <h4>{{ __('discipline.guardian_title') }} — {{ $student->full_name }}</h4>
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('discipline.reference') }}</th>
                                <th>{{ __('discipline.incident_type') }}</th>
                                <th>{{ __('discipline.title') }}</th>
                                <th>{{ __('discipline.incident_date') }}</th>
                                <th>{{ __('discipline.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                            <tr>
                                <td class="fw-bold text-primary">{{ $record->reference_no }}</td>
                                <td>{{ $record->typeLabel() }}</td>
                                <td>{{ $record->title }}</td>
                                <td>{{ $record->incident_date->format('d M, Y') }}</td>
                                <td><span class="badge badge-{{ $record->status === 'active' ? 'warning' : 'success' }}">{{ $record->statusLabel() }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('discipline.no_records') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
