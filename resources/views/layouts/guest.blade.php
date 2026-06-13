<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMAP — Login</title>
    <script>
        (function() {
            const saved  = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.toggle('dark', saved === 'dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dark:bg-gray-950 bg-slate-200 dark:text-gray-100 text-gray-800 min-h-screen flex items-center justify-center relative">
    <div class="absolute inset-0 pointer-events-none dark:opacity-100 opacity-30"
         style="background-image: linear-gradient(rgba(230,57,70,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(230,57,70,0.04) 1px, transparent 1px); background-size: 60px 60px;"></div>
    @yield('content')
    <footer class="absolute bottom-4 inset-x-0 px-4">
        <p class="text-center text-[11px] dark:text-gray-600 text-gray-500">
            &copy; 2026 KPU Kabupaten Banyuwangi
        </p>
    </footer>
</body>
</html>
