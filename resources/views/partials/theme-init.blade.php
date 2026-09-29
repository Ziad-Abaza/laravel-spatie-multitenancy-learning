{{--
    Theme initialization: data-theme, data-mode and the .dark class are already
    rendered server-side from SettingManagerContract::getTheme(). This script
    only resolves the client-only "system" mode before first paint to prevent FOUC.
--}}
<script>
    (function () {
        var mode = document.documentElement.getAttribute('data-mode');
        var dark = mode === 'dark'
            || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
    })();
</script>
