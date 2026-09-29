<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ session('theme', 'indigo') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>404 - الصفحة غير موجودة</title>
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
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-surface-bg text-text-main flex items-center justify-center p-6 antialiased">
    <div class="text-center max-w-lg w-full bg-surface-card border border-border-subtle rounded-3xl px-8 py-12 shadow-2xl">
        <div class="text-7xl sm:text-8xl font-extrabold leading-none bg-gradient-to-r from-primary-600 via-secondary-500 to-accent-500 bg-clip-text text-transparent mb-4 tracking-tight">
            404
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-text-main mb-3">لم يتم العثور على الصفحة أو المستأجر</h1>
        <p class="text-sm text-text-muted leading-relaxed mb-8">
            {{ $message ?? 'النطاق أو المستأجر الذي تحاول الوصول إليه غير موجود أو غير مفعل حالياً.' }}
        </p>
        <a href="{{ config('app.url', '/') }}" class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-primary-600 hover:bg-primary-500 text-on-primary font-semibold text-sm shadow-lg shadow-primary-600/30 transition-all hover:-translate-y-0.5">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rtl:rotate-180">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            العودة إلى الصفحة الرئيسية
        </a>
    </div>
</body>
</html>
