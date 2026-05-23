{{--
    v2.16 — Standalone error page layout.

    DESIGN INTENT
    -------------
    This layout is used as a SAFE FALLBACK when the active theme cannot
    render an error page (e.g. theme missing, DB down on 500). It has zero
    runtime dependencies: no Vite manifest lookup, no theme manager, no
    DB calls. CSS is inlined. The active brand colour is best-effort
    extracted from ThemeManager when available; if not, we fall back to
    a sober slate palette.

    EXPECTED @yields:
      - @yield('status_code')   e.g. "404"
      - @yield('title')         e.g. __('v216::errors.404.title')
      - @yield('heading')
      - @yield('description')
      - @yield('illustration')  inline SVG, optional
      - @section('actions')     a list of <a> buttons, optional
--}}
@php
    $brand = '#2563eb';
    try {
        if (class_exists(\App\Theme\ThemeManager::class)) {
            $palette = \App\Theme\ThemeManager::getColorsArray();
            if (! empty($palette['600'])) {
                $brand = $palette['600'];
            }
        }
    } catch (\Throwable $e) {
        // theme manager unavailable — keep the default brand colour
    }
    $appName = config('app.name', 'ClientXCMS');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · {{ $appName }}</title>
    <style>
        :root { --brand: {{ $brand }}; }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            color: #0f172a;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(180deg, #0b1220 0%, #0f172a 100%); color: #e2e8f0; }
            .card { background-color: rgba(15, 23, 42, 0.6) !important; border-color: rgba(148, 163, 184, 0.16) !important; }
            .muted { color: #94a3b8 !important; }
            .btn-secondary { background: rgba(148, 163, 184, 0.12) !important; color: #e2e8f0 !important; }
        }
        .wrap { min-height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .card {
            width: 100%;
            max-width: 36rem;
            background-color: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 12px 32px -16px rgba(15, 23, 42, 0.2);
            text-align: center;
        }
        .code { font-size: 0.875rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--brand); }
        h1 { font-size: 1.875rem; font-weight: 700; margin: 0.75rem 0 0; }
        @media (min-width: 640px) { h1 { font-size: 2.25rem; } }
        .muted { color: #475569; margin-top: 0.75rem; font-size: 1rem; }
        .illustration { margin: 0 auto 1.25rem; height: 80px; width: 80px; display: flex; align-items: center; justify-content: center; }
        .illustration svg { width: 100%; height: 100%; color: var(--brand); }
        .actions { margin-top: 1.75rem; display: flex; flex-direction: column; gap: 0.6rem; align-items: stretch; }
        @media (min-width: 480px) { .actions { flex-direction: row; justify-content: center; } }
        a.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.65rem 1.1rem; border-radius: 0.6rem; font-size: 0.95rem; font-weight: 600; text-decoration: none; transition: filter 120ms ease, transform 120ms ease; }
        a.btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
        a.btn:hover { filter: brightness(1.05); transform: translateY(-1px); }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-secondary { background: rgba(15, 23, 42, 0.06); color: #0f172a; }
        .footer { margin-top: 2rem; font-size: 0.8rem; color: #94a3b8; }
        @media (prefers-reduced-motion: reduce) {
            a.btn:hover { transform: none; }
        }
    </style>
</head>
<body>
    <main class="wrap" role="main">
        <div class="card">
            @hasSection('illustration')
                <div class="illustration" aria-hidden="true">@yield('illustration')</div>
            @endif
            <div class="code" aria-hidden="true">{{ __('v216::errors.common.status_code', ['code' => trim((string) View::yieldContent('status_code'))]) }}</div>
            <h1>@yield('title')</h1>
            @hasSection('heading')
                <p class="muted" style="font-weight:500;color:#0f172a">@yield('heading')</p>
            @endif
            <p class="muted">@yield('description')</p>
            <div class="actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a class="btn btn-primary" href="{{ url('/') }}">{{ __('v216::errors.common.home') }}</a>
                @endif
            </div>
        </div>
        <p class="footer">{{ $appName }}</p>
    </main>
</body>
</html>
