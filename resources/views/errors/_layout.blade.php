<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title') · {{ config('app.name', 'ClientXCMS') }}</title>
    @vite('resources/themes/default/css/app.scss')
</head>
<body class="min-h-full bg-gray-50 dark:bg-gray-900">
    <main class="flex min-h-screen items-center">
        @yield('content')
    </main>
</body>
</html>
