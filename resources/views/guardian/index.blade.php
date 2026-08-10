@extends('layout.layout')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <h4>{{ __('guardian.welcome') }}</h4>

        @if(!empty($schoolWhatsapp))
            @php
                $waUrl = 'https://wa.me/'.$schoolWhatsapp;
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.urlencode($waUrl);
            @endphp
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <img src="{{ $qrUrl }}" alt="WhatsApp QR" width="96" height="96" class="rounded border">
                    <div>
                        <h5 class="mb-1"><i class="fab fa-whatsapp text-success me-1"></i>{{ __('guardian.school_whatsapp') }}</h5>
                        <p class="text-muted small mb-2">{{ __('guardian.school_whatsapp_help') }}</p>
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                            {{ __('guardian.open_school_whatsapp') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            @forelse($children as $child)
                <div class="col-md-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5>{{ $child->full_name }}</h5>
                            <p class="text-muted small">{{ $child->admission_number }}</p>
                            <a href="{{ route('guardian.fees', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_fees') }}</a>
                            <a href="{{ route('guardian.results', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_results') }}</a>
                            <a href="{{ route('guardian.attendance', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_attendance') }}</a>
                            <a href="{{ route('guardian.requests', ['student_id' => $child->id]) }}" class="btn btn-outline-primary btn-sm d-block mb-2">{{ __('guardian.my_requests') }}</a>
                            <a href="{{ route('guardian.discipline', ['student_id' => $child->id]) }}" class="btn btn-outline-warning btn-sm d-block">{{ __('discipline.guardian_title') }}</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-warning">{{ __('guardian.no_children') }}</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
