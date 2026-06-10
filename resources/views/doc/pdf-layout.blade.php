<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 22mm 18mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #1a1a1a;
        }
        .cover {
            text-align: center;
            padding-top: 80px;
            page-break-after: always;
        }
        .cover h1 { font-size: 28pt; color: #002b80; margin-bottom: 8px; }
        .cover h2 { font-size: 14pt; color: #555; font-weight: normal; margin-bottom: 40px; }
        .cover .meta { font-size: 10pt; color: #777; margin-top: 60px; }
        h1 { font-size: 18pt; color: #002b80; border-bottom: 2px solid #002b80; padding-bottom: 4px; margin-top: 22px; page-break-after: avoid; page-break-before: always; }
        h1:first-of-type { page-break-before: auto; }
        h2 { font-size: 14pt; color: #003d99; margin-top: 18px; page-break-after: avoid; }
        h3 { font-size: 12pt; color: #333; margin-top: 14px; page-break-after: avoid; }
        h4 { font-size: 11pt; color: #444; margin-top: 12px; }
        p { margin: 6px 0 10px; text-align: justify; }
        ul { margin: 6px 0 12px 18px; padding: 0; }
        li { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 16px; font-size: 9.5pt; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; color: #002b80; font-weight: bold; }
        tr:nth-child(even) td { background: #fafafa; }
        pre {
            background: #f4f4f4;
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 8.5pt;
            line-height: 1.35;
            white-space: pre-wrap;
            word-wrap: break-word;
            page-break-inside: avoid;
        }
        code { font-family: DejaVu Sans Mono, monospace; font-size: 9pt; background: #f0f0f0; padding: 1px 4px; }
        pre code { background: transparent; padding: 0; }
        hr { border: none; border-top: 1px solid #ddd; margin: 16px 0; }
        .toc { page-break-after: always; }
        .toc h2 { border: none; }
        .footer-note { font-size: 8pt; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 8px; }
        strong { color: #111; }
    </style>
</head>
<body>
    <div class="cover">
        <h1>{{ $title }}</h1>
        <h2>Digitex School Management System</h2>
        <p><strong>Version:</strong> Laravel 11 &nbsp;|&nbsp; <strong>Document date:</strong> {{ $generatedAt }}</p>
        <div class="meta">
            E-Digitex SMS &mdash; Multi-Institution Education Platform<br>
            Confidential &mdash; For authorized users only
        </div>
    </div>
    <div class="content">
        {!! $body !!}
    </div>
    <div class="footer-note">
        Generated from project documentation at {{ $generatedAt }}. &copy; Digitex SMS.
    </div>
</body>
</html>
