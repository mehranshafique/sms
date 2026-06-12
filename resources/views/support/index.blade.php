@extends('layout.layout')

@section('content')
@include('support.partials.support-styles')
<div class="content-body">
    <div class="container-fluid">

        {{-- Hero --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="sp-hero shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center p-4" style="position:relative; z-index:1;">
                        <div>
                            <h3 class="text-white fw-bold mb-1">
                                {{ $isSupport ? __('support.inbox_title') : __('support.my_tickets_title') }}
                            </h3>
                            <p class="mb-0 text-white opacity-75">
                                {{ $isSupport ? __('support.inbox_subtitle') : __('support.my_tickets_subtitle') }}
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="la la-life-ring sp-hero__icon d-none d-md-block"></i>
                            <a href="{{ route('support.create') }}" class="btn btn-light fw-bold text-primary">
                                <i class="la la-plus"></i> {{ __('support.new_ticket') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="row g-3 mb-2">
            <div class="col-sm-3 col-6 mb-3">
                <div class="sp-stat d-flex align-items-center" style="gap:12px;">
                    <span class="sp-stat__icon tint-primary"><i class="la la-ticket-alt"></i></span>
                    <div><div class="sp-stat__value">{{ $stats['total'] }}</div><div class="sp-stat__label">{{ __('support.stat_total') }}</div></div>
                </div>
            </div>
            <div class="col-sm-3 col-6 mb-3">
                <div class="sp-stat d-flex align-items-center" style="gap:12px;">
                    <span class="sp-stat__icon tint-info"><i class="la la-comments"></i></span>
                    <div><div class="sp-stat__value">{{ $stats['open'] }}</div><div class="sp-stat__label">{{ __('support.stat_open') }}</div></div>
                </div>
            </div>
            <div class="col-sm-3 col-6 mb-3">
                <div class="sp-stat d-flex align-items-center" style="gap:12px;">
                    <span class="sp-stat__icon tint-success"><i class="la la-check-circle"></i></span>
                    <div><div class="sp-stat__value">{{ $stats['resolved'] }}</div><div class="sp-stat__label">{{ __('support.stat_resolved') }}</div></div>
                </div>
            </div>
            <div class="col-sm-3 col-6 mb-3">
                <div class="sp-stat d-flex align-items-center" style="gap:12px;">
                    <span class="sp-stat__icon tint-danger"><i class="la la-exclamation-circle"></i></span>
                    <div><div class="sp-stat__value">{{ $stats['urgent'] }}</div><div class="sp-stat__label">{{ __('support.stat_urgent') }}</div></div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="sp-panel p-3 mb-3">
            <form method="GET" action="{{ route('support.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex flex-wrap gap-2 flex-grow-1">
                    @php $statuses = ['' => __('support.filter_all')] + collect(\App\Models\SupportTicket::STATUSES)->mapWithKeys(fn($s) => [$s => __('support.status_'.$s)])->toArray(); @endphp
                    @foreach($statuses as $key => $label)
                        <a href="{{ route('support.index', array_filter(['status' => $key, 'priority' => $priority, 'q' => $search])) }}"
                           class="sp-filter-btn {{ (string)$status === (string)$key ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="input-group" style="max-width: 280px;">
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="{{ __('support.search_placeholder') }}">
                    <button class="btn btn-primary" type="submit"><i class="la la-search"></i></button>
                </div>
            </form>
        </div>

        {{-- Ticket list --}}
        <div class="row">
            <div class="col-12">
                @forelse($tickets as $ticket)
                    @php
                        $unread = $isSupport ? $ticket->hasUnreadForSupport() : $ticket->hasUnreadForUser();
                        $name = $ticket->user->name ?? '—';
                    @endphp
                    <a href="{{ route('support.show', $ticket->id) }}" class="sp-ticket {{ $unread ? 'is-unread' : '' }}">
                        <span class="sp-ticket__avatar tint-primary">{{ strtoupper(mb_substr($name,0,1)) }}</span>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <p class="sp-ticket__subject mb-0">{{ $ticket->subject }}</p>
                                @if($unread)<span class="badge rounded-pill" style="background:var(--sp-primary);color:#fff;font-size:10px;">{{ __('support.new') }}</span>@endif
                            </div>
                            <div class="sp-ticket__meta mt-1">
                                <span class="fw-bold text-dark">{{ $ticket->ticket_number }}</span>
                                · <i class="la la-folder"></i> {{ __('support.category_'.$ticket->category) }}
                                @if($isSupport)
                                    · <i class="la la-user"></i> {{ $name }}
                                    @if($ticket->institution) · <i class="la la-university"></i> {{ $ticket->institution->name }} @endif
                                @endif
                                · {{ $ticket->messages_count }} <i class="la la-comment"></i>
                                · {{ optional($ticket->last_reply_at)->diffForHumans() ?? $ticket->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="text-end d-none d-sm-block">
                            <span class="sp-prio prio-{{ $ticket->priority }} d-block mb-2"><span class="dot"></span>{{ __('support.priority_'.$ticket->priority) }}</span>
                            <span class="sp-pill pill-{{ $ticket->status }}"><span class="dot"></span>{{ __('support.status_'.$ticket->status) }}</span>
                        </div>
                    </a>
                @empty
                    <div class="sp-panel sp-empty">
                        <i class="la la-inbox d-block mb-3"></i>
                        <h5 class="text-dark">{{ __('support.empty_title') }}</h5>
                        <p>{{ __('support.empty_subtitle') }}</p>
                        <a href="{{ route('support.create') }}" class="btn btn-primary"><i class="la la-plus"></i> {{ __('support.new_ticket') }}</a>
                    </div>
                @endforelse

                <div class="mt-3">{{ $tickets->links() }}</div>
            </div>
        </div>

    </div>
</div>
@endsection
