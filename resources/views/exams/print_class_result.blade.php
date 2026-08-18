<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ __('exam.result_sheet') }} - {{ $classSection->name }}</title>
    @php
        $palette = [
            ['#1a5276', '#d6eaf8'],
            ['#117a65', '#d5f5e3'],
            ['#b9770e', '#fdebd0'],
            ['#6c3483', '#e8daef'],
            ['#922b21', '#fadbd8'],
            ['#1b4f72', '#aed6f1'],
            ['#0e6655', '#d0ece7'],
            ['#7d3c98', '#d7bde2'],
            ['#b7950b', '#fcf3cf'],
            ['#196f3d', '#abebc6'],
            ['#a04000', '#f6ddcc'],
            ['#1f618d', '#d4e6f1'],
            ['#148f77', '#d1f2eb'],
            ['#7b241c', '#f5b7b1'],
            ['#4a235a', '#e8daef'],
            ['#145a32', '#d5f5e3'],
            ['#7e5109', '#f9e79f'],
            ['#1a5276', '#d4e6f1'],
        ];
        $logoPath = $exam->institution->logo ?? null;
        $logoSrc = null;
        if ($logoPath) {
            $relative = ltrim(str_replace('\\', '/', $logoPath), '/');
            $diskFile = public_path('storage/' . $relative);
            $logoSrc = request()->has('download') && is_file($diskFile)
                ? $diskFile
                : asset('storage/' . $relative);
        }
        $classLabel = trim(($classSection->gradeLevel->name ?? '') . ' ' . $classSection->name);
        $sessionName = $exam->academicSession->name ?? '';
        $teacherName = $classSection->classTeacher?->user?->name
            ?? $classSection->classTeacher?->full_name
            ?? null;
        $studentCount = $records->count();
        $subjectCount = $subjects->count();
    @endphp
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1d2a3a; margin: 0; }
        .toolbar { margin-bottom: 10px; }
        .toolbar a, .toolbar button {
            display: inline-block; padding: 6px 12px; margin-right: 6px;
            background: #4c2bb6; color: #fff; border: 0; border-radius: 4px;
            text-decoration: none; font-size: 12px; cursor: pointer;
        }
        .sheet { border: 1px solid #c5cde0; }
        .brand { width: 100%; border-collapse: collapse; background: #f4f1ff; }
        .brand td { vertical-align: middle; padding: 8px 10px; }
        .logo { max-height: 58px; max-width: 70px; }
        .school-name { font-size: 16px; font-weight: bold; text-transform: uppercase; color: #2c1e6b; margin: 0 0 2px; }
        .school-meta { font-size: 9px; color: #5c6478; margin: 0; }
        .exam-badge {
            width: 72px; height: 72px; border-radius: 50%;
            background: #4c2bb6; color: #fff; text-align: center;
            font-weight: bold; line-height: 1.15;
        }
        .exam-badge .en { font-size: 13px; padding-top: 18px; display: block; }
        .exam-badge .yr { font-size: 8px; display: block; margin-top: 3px; opacity: 0.92; }
        .meta { width: 100%; border-collapse: collapse; background: #fff; }
        .meta td { padding: 5px 10px; border-top: 1px solid #e4e0f4; font-size: 10px; }
        .meta strong { color: #4c2bb6; }
        table.marks { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.marks th, table.marks td { border: 1px solid #9aa7c2; padding: 3px 2px; text-align: center; }
        table.marks thead th { color: #fff; font-size: 8px; }
        table.marks th.sticky { background: #2c1e6b; }
        table.marks tbody tr:nth-child(even) td.base { background: #f7f8fb; }
        table.marks td.name { text-align: left; font-weight: bold; font-size: 9px; background: #fff; }
        .fail { color: #c0392b; font-weight: bold; }
        .legend-wrap { padding: 8px 10px 4px; }
        .legend-title { font-size: 11px; font-weight: bold; color: #2c1e6b; margin: 0 0 6px; }
        .legend-item { display: inline-block; width: 32%; vertical-align: top; margin: 0 0 5px; font-size: 9px; }
        .swatch {
            display: inline-block; min-width: 42px; padding: 1px 5px; margin-right: 4px;
            color: #fff; font-weight: bold; font-size: 8px; text-align: center;
        }
        .note { font-size: 8px; color: #666; padding: 0 10px 6px; }
        .footer { width: 100%; margin-top: 8px; }
        .footer td { font-size: 9px; color: #666; padding: 8px 10px 4px; vertical-align: bottom; }
        .sig { text-align: right; }
        .sig-line { border-top: 1px solid #333; width: 180px; margin-left: auto; padding-top: 4px; }
        @media print {
            .no-print { display: none; }
            .sheet { border: 0; }
        }
        @foreach($subjects as $i => $subject)
            th.sub-{{ $i }} { background: {{ $palette[$i % count($palette)][0] }}; color: #fff; }
            td.sub-{{ $i }} { background: {{ $palette[$i % count($palette)][1] }}; }
            .swatch-{{ $i }} { background: {{ $palette[$i % count($palette)][0] }}; }
        @endforeach
    </style>
</head>
<body onload="window.print()">

    <div class="no-print toolbar">
        <button type="button" onclick="window.print()">{{ __('exam.print') }}</button>
        <a href="{{ url()->full() . (str_contains(url()->full(), '?') ? '&' : '?') }}download=true">{{ __('exam.download_pdf') }}</a>
    </div>

    <div class="sheet">
        <table class="brand">
            <tr>
                <td width="80" align="center">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo" class="logo">
                    @endif
                </td>
                <td>
                    <p class="school-name">{{ $exam->institution->name }}</p>
                    <p class="school-meta">
                        @php
                            $city = $exam->institution->city;
                            $cityLabel = is_object($city) ? ($city->name ?? null) : $city;
                        @endphp
                        {{ collect([$exam->institution->address, $cityLabel, $exam->institution->phone])->filter()->implode(' · ') }}
                    </p>
                    <p class="school-meta" style="margin-top:4px; font-weight:bold; color:#2c1e6b;">
                        {{ __('exam.result_sheet') }} — {{ $exam->name }}
                    </p>
                </td>
                <td width="90" align="right">
                    <div class="exam-badge">
                        <span class="en">{{ $exam->name }}</span>
                        <span class="yr">{{ $sessionName }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td><strong>{{ __('exam.class') }}:</strong> {{ $classLabel }}</td>
                <td><strong>{{ __('exam.session') }}:</strong> {{ $sessionName }}</td>
                <td><strong>{{ __('exam.students') }}:</strong> {{ $studentCount }}</td>
                <td><strong>{{ __('exam.subjects') }}:</strong> {{ $subjectCount }}</td>
                @if($teacherName)
                    <td><strong>{{ __('exam.class_teacher') }}:</strong> {{ $teacherName }}</td>
                @endif
            </tr>
        </table>

        <table class="marks">
            <thead>
                <tr>
                    <th class="sticky" style="text-align:left;">{{ __('exam.student') }}</th>
                    <th class="sticky">{{ __('exam.student_id') }}</th>
                    @foreach($subjects as $i => $subject)
                        <th class="sub-{{ $i }}">{{ $subject->code ?: \Illuminate\Support\Str::limit($subject->name, 8, '') }}</th>
                    @endforeach
                    <th class="sticky">{{ __('exam.total') }}</th>
                    <th class="sticky">{{ __('exam.average') }}</th>
                    <th class="sticky">{{ __('exam.rank') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $studentId => $studentRecords)
                    @php
                        $student = $studentRecords->first()->student;
                        $total = $studentRecords->sum('marks_obtained');
                        $avg = $subjectCount > 0 ? $total / $subjectCount : 0;
                    @endphp
                    <tr>
                        <td class="name">{{ $student->full_name }}</td>
                        <td class="base">{{ $student->admission_number ?? $student->id }}</td>
                        @foreach($subjects as $i => $subject)
                            @php
                                $mark = $studentRecords->firstWhere('subject_id', $subject->id);
                            @endphp
                            <td class="sub-{{ $i }}">
                                @if($mark)
                                    <span class="{{ $mark->marks_obtained < $subject->passing_marks ? 'fail' : '' }}">
                                        {{ $mark->is_absent ? __('exam.absent_short') : $mark->marks_obtained }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td class="base"><strong>{{ $total }}</strong></td>
                        <td class="base">{{ number_format($avg, 1) }}</td>
                        <td class="base"><strong>{{ $ranks[$studentId] ?? '' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="legend-wrap">
            <p class="legend-title">{{ __('exam.subject_legend') }}</p>
            @foreach($subjects as $i => $subject)
                @php
                    $code = $subject->code ?: \Illuminate\Support\Str::limit($subject->name, 8, '');
                @endphp
                <div class="legend-item">
                    <span class="swatch swatch-{{ $i }}">{{ $code }}</span>
                    {{ $subject->name }}
                </div>
            @endforeach
        </div>
        <p class="note">{{ __('exam.fail_note') }}</p>

        <table class="footer">
            <tr>
                <td>{{ __('exam.generated_on') }}: {{ now()->format('d M, Y h:i A') }}</td>
                <td class="sig">
                    <div class="sig-line">{{ __('exam.authorized_signature') }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
