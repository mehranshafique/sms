@include('layout.header')

@include('layout.sidebar')

<div class="content-body">
    @include('layout.partials.setup-alerts')
    @if(has_ai_access() && request()->routeIs('dashboard'))
        <div class="dashboard-ai-slot">
            @include('dashboard.partials.ai-copilot')
        </div>
    @endif
    @yield('content')
</div>

@include('layout.footer')
