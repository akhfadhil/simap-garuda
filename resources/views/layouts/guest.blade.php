@php($party = $party ?? config('party'))
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $party['app_name'] }} - Login</title>
    <script>
        (function() {
            const saved  = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.toggle('dark', saved === 'dark');
        })();
    </script>
    <style>
        :root {
            --color-brand: {{ $party['colors']['primary'] }};
            --color-brand-dark: {{ $party['colors']['primary_dark'] }};
            --color-brand-soft: {{ $party['colors']['primary_soft'] }};
            --color-bg-dark: {{ $party['colors']['bg_dark'] ?? '#140404' }};
            --color-role-korcam: {{ $party['colors']['korcam'] }};
            --color-role-kordes: {{ $party['colors']['kordes'] }};
            --color-role-saksi-tps: {{ $party['colors']['saksi_tps'] }};
        }
        .brand-border-soft {
            border-color: var(--color-brand-soft) !important;
        }
        .brand-bg {
            background-color: var(--color-brand) !important;
        }
        .brand-bg-hover:hover {
            background-color: var(--color-brand-dark) !important;
        }
        .brand-focus:focus {
            border-color: var(--color-brand) !important;
            outline: none !important;
        }
        .brand-shadow {
            box-shadow: 0 10px 15px -3px var(--color-brand-soft), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
        }
        .dark .dark-bg-custom {
            background-color: var(--color-bg-dark) !important;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset($party['assets']['logo']) }}">
</head>
<body class="dark-bg-custom bg-slate-200 dark:text-gray-100 text-gray-800 min-h-screen flex items-center justify-center relative">
    <div class="absolute inset-0 pointer-events-none dark:opacity-100 opacity-30"
         style="background-image: linear-gradient(var(--color-brand-soft) 1px, transparent 1px), linear-gradient(90deg, var(--color-brand-soft) 1px, transparent 1px); background-size: 60px 60px;"></div>
    @yield('content')
    <footer class="absolute bottom-4 inset-x-0 px-4">
        <p class="text-center text-[11px] dark:text-gray-600 text-gray-500">
            &copy; {{ $party['copyright_year'] }} {{ $party['name'] }}
        </p>
    </footer>
</body>
</html>
