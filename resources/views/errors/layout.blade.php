<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon.png') }}">
    <link href="{{ asset('vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --err-brand: #083366;
            --err-accent: #6a73fa;
            --err-accent-soft: rgba(106, 115, 250, 0.14);
        }

        body.digitex-error-page {
            min-height: 100vh;
            margin: 0;
            font-family: inherit;
            background:
                radial-gradient(circle at 12% 18%, rgba(106, 115, 250, 0.18), transparent 42%),
                radial-gradient(circle at 88% 82%, rgba(8, 51, 102, 0.12), transparent 38%),
                linear-gradient(145deg, #f8fafc 0%, #eef2ff 55%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .digitex-error-shell {
            width: 100%;
            max-width: 560px;
        }

        .digitex-error-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .digitex-error-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, var(--err-brand), var(--err-accent));
        }

        .digitex-error-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .digitex-error-logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 220px;
            min-height: 80px;
            padding: 0.85rem 1.5rem;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .digitex-error-logo {
            display: block;
            max-width: 100%;
            max-height: 56px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .digitex-error-code {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: var(--err-accent-soft);
            color: var(--err-brand);
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(106, 115, 250, 0.18);
        }

        .digitex-error-title {
            font-size: 1.65rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }

        .digitex-error-message {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.65;
            margin-bottom: 0.5rem;
        }

        .digitex-error-hint {
            color: #94a3b8;
            font-size: 0.92rem;
            margin-bottom: 1.75rem;
        }

        .digitex-error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .digitex-error-actions .btn {
            border-radius: 999px;
            padding: 0.72rem 1.35rem;
            font-weight: 600;
            min-width: 150px;
        }

        .digitex-error-actions .btn-primary {
            background: linear-gradient(135deg, var(--err-brand) 0%, var(--err-accent) 100%);
            border: none;
            box-shadow: 0 10px 24px rgba(106, 115, 250, 0.28);
        }

        .digitex-error-actions .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(106, 115, 250, 0.34);
        }

        .digitex-error-actions .btn-outline-secondary {
            border-color: #cbd5e1;
            color: #475569;
            background: #fff;
        }

        .digitex-error-footer {
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.82rem;
        }

        @media (max-width: 575.98px) {
            .digitex-error-card {
                padding: 2rem 1.25rem 1.5rem;
                border-radius: 18px;
            }

            .digitex-error-actions .btn {
                width: 100%;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="digitex-error-page">
    <div class="digitex-error-shell">
        <div class="digitex-error-card">
            <div class="digitex-error-header">
                <div class="digitex-error-logo-wrap">
                    <img src="{{ brand_logo_url() }}" alt="{{ brand_logo_alt() }}" class="digitex-error-logo">
                </div>
                @yield('error_badge')
            </div>

            @yield('content')

            <div class="digitex-error-footer">
                @php
                    $statusCode = trim($__env->yieldContent('status_code'));
                    if ($statusCode === '') {
                        $statusCode = (isset($exception) && method_exists($exception, 'getStatusCode'))
                            ? $exception->getStatusCode()
                            : 404;
                    }
                @endphp
                {{ __('errors.error_code', ['code' => $statusCode]) }}
            </div>
        </div>
    </div>
</body>
</html>
