<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'fa', 'ur', 'he']) ? 'rtl' : 'ltr' }}" class="h-full" data-theme="{{ session('theme', 'indigo') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title inertia>{{ config('app.name', 'SaaS Platform') }}</title>

    <!-- Theme & Mode Initialization (prevents FOUC) -->
    <script>
        (function() {
            try {
                const storedTheme = localStorage.getItem('saas_theme') || '{{ session('theme', 'indigo') }}';
                const storedMode = localStorage.getItem('saas_mode') || '{{ session('theme_mode', 'dark') }}';
                document.documentElement.setAttribute('data-theme', storedTheme);
                if (storedMode === 'dark' || (storedMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800|inter:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-surface-bg text-text-main font-sans antialiased selection:bg-primary-500 selection:text-on-primary">
    @inertia
</body>
</html>
