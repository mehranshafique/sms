        @if(!empty($under_revision))
            <div class="revision-banner">{{ __('reports.under_revision_banner') }}</div>
        @endif
        @if(!empty($outstanding_banner))
            <div class="outstanding-banner">{{ __('reports.outstanding_staff_banner') }}</div>
        @endif
