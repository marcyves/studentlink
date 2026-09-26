<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $htmlLang ?? 'fr') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('studentlink-theme');
                    var dark = stored
                        ? stored === 'dark'
                        : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (dark) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
