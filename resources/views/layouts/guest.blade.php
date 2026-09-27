<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maternal Health Hub - Maternal &amp; Child Care Login</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    @fonts
    @vite('resources/css/guest.css')

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-medical-shadow {
            box-shadow: 0px 4px 20px rgba(145, 158, 171, 0.15);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-background min-h-screen flex items-center justify-center p-margin-mobile md:p-gutter">

{{-- Background Watermark Overlay --}}
<div class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-[0.06] -z-20 select-none p-6">
    <div class="flex flex-col md:flex-row items-center justify-center gap-12 md:gap-32">
        <img src="{{ asset('images/bayan_ng_carmen.jpg') }}" alt="Bayan ng Carmen Logo Watermark" class="w-[45vw] h-[45vw] md:w-[35vw] md:h-[35vw] max-w-[220px] max-h-[220px] md:max-w-[500px] md:max-h-[500px] aspect-square object-cover rounded-full grayscale">
        <img src="{{ asset('images/DOH.jpg') }}" alt="DOH Logo Watermark" class="w-[45vw] h-[45vw] md:w-[35vw] md:h-[35vw] max-w-[220px] max-h-[220px] md:max-w-[500px] md:max-h-[500px] aspect-square object-cover rounded-full grayscale">
    </div>
</div>

    @yield('content')

    <div class="fixed top-20 left-20 w-32 h-32 bg-primary/5 rounded-full blur-3xl -z-10 animate-float" style="animation-duration: 8s"></div>
    <div class="fixed bottom-20 right-20 w-48 h-48 bg-secondary/5 rounded-full blur-3xl -z-10 animate-float" style="animation-duration: 10s"></div>

    @stack('scripts')
    <script>
        // Nobody is signed in here (logged out or session expired): drop any offline copies of pages.
        // The name matches PAGES_CACHE in public/sw.js.
        if ('caches' in window) caches.delete('maternal-health-pages');
    </script>
</body>
</html>
