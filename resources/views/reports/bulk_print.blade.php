<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reports.bulletin_title') }} - {{ __('reports.whole_class') }}</title>
    @include('reports.partials.bulletin_css')
</head>
<body>
    
    <!-- Floating Print Button (Hides automatically during actual print) -->
    <div class="print-controls">
        <button onclick="window.print()" class="print-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimer
        </button>
    </div>

    @foreach (collect($reports)->chunk(4) as $chunkIndex => $chunk)
        <div class="a4-landscape">
            
            @foreach ($chunk as $index => $reportData)
                @include($viewName, array_merge($reportData, ['is_bulk' => true, 'loop_index' => $chunkIndex . '-' . $index]))
            @endforeach

            {{-- Fill empty columns to maintain the precise flex layout if the chunk has fewer than 4 students --}}
            @for ($i = $chunk->count(); $i < 4; $i++)
                <div class="student-column" style="visibility: hidden; border: none;"></div>
            @endfor

        </div>
    @endforeach
</body>
</html>