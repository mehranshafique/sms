@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-8 p-md-0">
                <div class="welcome-text">
                    <h4>{{ __('queue_monitor.page_title') }}</h4>
                    <p class="mb-0">{{ __('queue_monitor.subtitle') }}</p>
                </div>
            </div>
            <div class="col-sm-4 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex gap-2">
                <form method="POST" action="{{ route('platform.queue-monitor.test') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fa fa-play me-1"></i> {{ __('queue_monitor.run_test_job') }}
                    </button>
                </form>
                @if($failedJobs->isNotEmpty())
                <form method="POST" action="{{ route('platform.queue-monitor.retry_all') }}">
                    @csrf
                    <button type="submit" class="btn btn-warning shadow-sm">
                        <i class="fa fa-redo me-1"></i> {{ __('queue_monitor.retry_all') }}
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">{{ __('queue_monitor.pending_jobs') }}</h6>
                        <h2 class="mb-0">{{ $pendingJobs }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">{{ __('queue_monitor.failed_jobs') }}</h6>
                        <h2 class="mb-0 text-danger">{{ $failedJobs->count() }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small">{{ __('queue_monitor.last_cron_heartbeat') }}</h6>
                        <p class="mb-0 fw-bold">
                            @if($lastHeartbeat)
                                {{ \Carbon\Carbon::parse($lastHeartbeat)->format('d M, Y H:i:s') }}
                                <span class="badge bg-success ms-1">{{ __('queue_monitor.cron_ok') }}</span>
                            @else
                                <span class="text-muted">{{ __('queue_monitor.no_heartbeat') }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">{{ __('queue_monitor.failed_jobs_list') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($failedJobs->isEmpty())
                            <p class="text-muted mb-0">{{ __('queue_monitor.no_failed_jobs') }}</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('queue_monitor.job') }}</th>
                                            <th>{{ __('queue_monitor.queue') }}</th>
                                            <th>{{ __('queue_monitor.failed_at') }}</th>
                                            <th class="text-end">{{ __('queue_monitor.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($failedJobs as $job)
                                            @php
                                                $payload = json_decode($job->payload ?? '{}', true);
                                                $jobName = $payload['displayName'] ?? class_basename($payload['data']['commandName'] ?? 'Job');
                                            @endphp
                                            <tr>
                                                <td>{{ $job->id }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $jobName }}</div>
                                                    <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($job->exception ?? '', 120) }}</small>
                                                </td>
                                                <td>{{ $job->queue }}</td>
                                                <td>{{ $job->failed_at }}</td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('platform.queue-monitor.retry', $job->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-redo"></i> {{ __('queue_monitor.retry') }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
