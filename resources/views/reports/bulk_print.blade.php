<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bulk Report Generation</title>
    <style>
        body { margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($reports as $index => $report)
        <div class="report-wrapper">
            {{-- Include the specific view for the report type (Primary/Secondary/Period) --}}
            {{-- We pass the $report array which contains 'student', 'data', 'settings', etc. --}}
            @include($viewName, $report)
        </div>
        
        {{-- Add page break unless it's the last student --}}
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>