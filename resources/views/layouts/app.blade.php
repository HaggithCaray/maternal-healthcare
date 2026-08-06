<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#005eb8">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="{{ asset('manifest.json') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
<title>@yield('title', 'Maternal Health Hub') - Maternal Health Hub</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-surface-variant": "#424752",
                        "on-tertiary-container": "#84f366",
                        "tertiary-fixed-dim": "#71df54",
                        "secondary": "#006a6a",
                        "secondary-fixed": "#7df5f5",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "on-primary-container": "#c8daff",
                        "primary-fixed": "#d6e3ff",
                        "on-secondary-fixed": "#002020",
                        "surface-container": "#e0f0ff",
                        "tertiary-container": "#146f00",
                        "on-tertiary-fixed-variant": "#0d5300",
                        "on-primary-fixed": "#001b3d",
                        "on-surface": "#0d1d29",
                        "surface": "#f6f9ff",
                        "surface-container-lowest": "#ffffff",
                        "surface-bright": "#f6f9ff",
                        "inverse-on-surface": "#e6f2ff",
                        "outline-variant": "#c2c6d4",
                        "surface-variant": "#d4e5f5",
                        "on-tertiary": "#ffffff",
                        "background": "#f6f9ff",
                        "outline": "#727783",
                        "secondary-container": "#7af2f2",
                        "secondary-fixed-dim": "#5dd9d8",
                        "on-error": "#ffffff",
                        "on-primary-fixed-variant": "#00468c",
                        "on-secondary-fixed-variant": "#004f4f",
                        "on-tertiary-fixed": "#022100",
                        "tertiary-fixed": "#8cfc6d",
                        "on-primary": "#ffffff",
                        "surface-dim": "#cbdcec",
                        "on-secondary": "#ffffff",
                        "surface-container-high": "#d9eafa",
                        "primary": "#00478d",
                        "surface-container-highest": "#d4e5f5",
                        "primary-container": "#005eb8",
                        "inverse-surface": "#22323e",
                        "on-error-container": "#93000a",
                        "on-secondary-container": "#006e6e",
                        "tertiary": "#0d5400",
                        "primary-fixed-dim": "#a9c7ff",
                        "inverse-primary": "#a9c7ff",
                        "surface-tint": "#005db6",
                        "surface-container-low": "#ebf5ff",
                        "on-background": "#0d1d29"
                    },
                    "spacing": {
                        "gutter": "24px",
                        "base": "8px",
                        "lg": "40px",
                        "md": "24px",
                        "margin-desktop": "48px",
                        "xl": "64px",
                        "margin-mobile": "16px",
                        "xs": "4px",
                        "sm": "12px"
                    },
                    "fontFamily": {
                        "label-md": ["Inter"],
                        "body-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-sm": ["Inter"],
                        "headline-sm": ["Inter"],
                        "headline-lg": ["Inter"],
                        "headline-md": ["Inter"]
                    }
                }
            }
        }
    </script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .soft-drop-shadow, .soft-shadow {
            box-shadow: 0px 4px 20px rgba(145, 158, 171, 0.15);
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f9ff;
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
</head>
<body class="bg-background text-on-surface {{ request()->routeIs('messaging') ? 'overflow-hidden h-screen h-dvh' : '' }}">

{{-- Background Watermark Overlay --}}
<div class="fixed inset-0 flex items-center justify-center pointer-events-none opacity-[0.08] z-0 select-none p-6">
    <div class="flex flex-col md:flex-row items-center justify-center gap-12 md:gap-32">
        <img src="{{ asset('images/bayan_ng_carmen.jpg') }}" alt="Bayan ng Carmen Logo Watermark" class="w-[45vw] h-[45vw] md:w-[35vw] md:h-[35vw] max-w-[220px] max-h-[220px] md:max-w-[500px] md:max-h-[500px] aspect-square object-cover rounded-full grayscale">
        <img src="{{ asset('images/DOH.jpg') }}" alt="DOH Logo Watermark" class="w-[45vw] h-[45vw] md:w-[35vw] md:h-[35vw] max-w-[220px] max-h-[220px] md:max-w-[500px] md:max-h-[500px] aspect-square object-cover rounded-full grayscale">
    </div>
</div>

@php
    $user = auth()->user();
    $allNavItems = [
        ['route' => 'dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard', 'roles' => ['admin']],
        ['route' => 'records', 'icon' => 'folder_shared', 'label' => 'Records', 'roles' => ['admin']],
        ['route' => 'register', 'icon' => 'person_add', 'label' => 'Register', 'roles' => ['admin']],
        ['route' => 'immunization', 'icon' => 'vaccines', 'label' => 'Immunization', 'roles' => ['admin', 'user']],
        ['route' => 'sms', 'icon' => 'sms', 'label' => 'SMS', 'roles' => ['admin']],
        ['route' => 'messaging', 'icon' => 'forum', 'label' => 'Messaging', 'roles' => ['admin', 'user']],
        ['route' => 'growth', 'icon' => 'monitoring', 'label' => 'Growth', 'roles' => ['admin', 'user']],
        ['route' => 'maternal', 'icon' => 'pregnant_woman', 'label' => 'Maternal', 'roles' => ['admin', 'user']],
        ['route' => 'reports', 'icon' => 'analytics', 'label' => 'Reports', 'roles' => ['admin']],
        ['route' => 'patient.portal', 'icon' => 'home_health', 'label' => 'My Portal', 'roles' => ['user']],
        ['route' => 'admin', 'icon' => 'settings', 'label' => 'Admin', 'roles' => ['admin']],
    ];
    $navItems = array_filter($allNavItems, fn($item) => $user && in_array($user->role, $item['roles']));
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<div class="fixed inset-0 bg-inverse-surface/40 z-40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="h-screen w-64 fixed left-0 top-0 bg-surface-container-low shadow-sm flex flex-col py-md px-sm z-50 transition-transform duration-300 -translate-x-full lg:translate-x-0" id="sidebar">
    <div class="flex items-center justify-between mb-lg px-sm">
        <div>
            <div class="flex items-center gap-3 mb-3 select-none">
                <img src="{{ asset('images/bayan_ng_carmen.jpg') }}" alt="Bayan ng Carmen Logo" class="w-10 h-10 object-contain rounded-full shadow-sm bg-white border border-outline-variant/20">
                <img src="{{ asset('images/DOH.jpg') }}" alt="DOH Logo" class="w-10 h-10 object-contain rounded-full shadow-sm bg-white border border-outline-variant/20">
            </div>
            <h1 class="font-headline-sm text-headline-sm font-bold text-primary leading-tight">Maternal Health Hub</h1>
            <p class="text-label-sm font-label-md text-on-surface-variant">Maternal &amp; Child Care</p>
        </div>
        <button class="lg:hidden p-1 rounded-full hover:bg-surface-variant transition-colors" onclick="toggleSidebar()">
            <span class="material-symbols-outlined text-on-surface-variant">close</span>
        </button>
    </div>
    <nav class="flex-grow space-y-xs overflow-y-auto hide-scrollbar">
        @foreach($navItems as $item)
            @php $active = $currentRoute === $item['route']; @endphp
            <a class="flex items-center gap-sm px-sm py-base rounded-lg transition-colors @if($active) text-primary font-bold bg-secondary-container/30 @else text-on-surface-variant hover:bg-surface-variant/50 @endif"
               href="{{ route($item['route']) }}" onclick="if(window.innerWidth < 1024) toggleSidebar()">
                <span class="material-symbols-outlined" data-icon="{{ $item['icon'] }}">{{ $item['icon'] }}</span>
                <span class="font-label-md text-label-md">{{ $item['label'] }}</span>
                @if($item['route'] === 'messaging')
                <span class="ml-auto bg-primary text-on-primary text-[10px] px-1.5 py-0.5 rounded-full">4</span>
                @endif
            </a>
        @endforeach
    </nav>
</aside>

<main class="ml-0 lg:ml-64 flex flex-col {{ request()->routeIs('messaging') ? 'h-screen h-dvh overflow-hidden' : 'min-h-screen' }}">

    <header class="h-16 w-full sticky top-0 z-30 bg-surface border-b border-outline-variant/30 flex justify-between items-center px-margin-mobile md:px-margin-desktop">
        <div class="flex items-center gap-sm">
            <button class="lg:hidden p-2 rounded-full hover:bg-surface-variant transition-colors" onclick="toggleSidebar()">
                <span class="material-symbols-outlined text-on-surface-variant">menu</span>
            </button>
            <h2 class="font-headline-md text-headline-md font-bold text-primary truncate">Maternal &amp; Child Health</h2>
        </div>
        <div class="flex items-center gap-xs md:gap-md">
            <div class="relative group hidden sm:block">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
                <input class="pl-10 pr-4 py-2 bg-surface-container border-none rounded-full w-40 md:w-64 focus:ring-2 focus:ring-primary transition-all" placeholder="Search records..." type="text">
            </div>
            <div class="flex items-center gap-xs md:gap-sm">
                <button class="p-2 rounded-full hover:bg-surface-variant transition-colors relative">
                    <span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
                </button>
                <div class="relative group">
                    <div class="flex items-center gap-sm cursor-pointer hover:opacity-80 transition-opacity" onclick="document.getElementById('user-menu').classList.toggle('hidden');document.getElementById('chevron-icon')?.classList.toggle('rotate-180')">
                        <div class="text-right hidden lg:block">
                            <p class="font-label-md text-label-md text-on-surface">{{ auth()->user()?->name ?? 'Maria Santos' }}</p>
                            <p class="text-label-sm text-on-surface-variant">{{ auth()->user()?->role ?? 'Midwife I' }}</p>
                        </div>
                        <div class="w-9 h-9 md:w-10 md:h-10 rounded-full bg-primary-container flex items-center justify-center font-bold text-primary text-sm border-2 border-primary/20 shrink-0">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'M', 0, 2)) }}
                        </div>
                        <span class="material-symbols-outlined text-on-surface-variant transition-transform duration-200" id="chevron-icon">expand_more</span>
                    </div>
                    <div id="user-menu" class="hidden absolute right-0 top-full mt-2 w-48 bg-surface-container-low rounded-xl soft-drop-shadow border border-outline-variant/20 py-2 z-50">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-sm px-4 py-2 text-on-surface hover:bg-surface-variant/50 transition-colors">
                                <span class="material-symbols-outlined text-on-surface-variant text-[20px]" data-icon="logout">logout</span>
                                <span class="font-label-md text-label-md">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="p-margin-mobile md:p-margin-desktop space-y-gutter flex-grow">
        @yield('content')
    </div>

    @if(!request()->routeIs('messaging'))
    <footer class="mt-auto px-margin-mobile md:px-margin-desktop py-md border-t border-outline-variant/10 flex flex-col md:flex-row justify-between items-center gap-sm text-label-sm text-on-surface-variant">
        <p>&copy; {{ date('Y') }} Maternal Health Hub. All rights reserved.</p>
        <div class="flex gap-md">
            <a class="hover:text-primary" href="#">Privacy Policy</a>
            <a class="hover:text-primary" href="#">System Status</a>
        </div>
    </footer>
    @endif
</main>

@if(auth()->user()?->role === 'admin' && !request()->routeIs('messaging'))
<a href="{{ route('register') }}" class="fixed bottom-margin-mobile right-margin-mobile md:bottom-md md:right-md w-14 h-14 bg-primary text-on-primary rounded-full soft-drop-shadow flex items-center justify-center hover:scale-110 active:scale-95 transition-transform z-50 group">
    <span class="material-symbols-outlined text-[28px]" data-icon="add">add</span>
    <span class="absolute right-16 bg-inverse-surface text-inverse-on-surface px-3 py-1 rounded-lg text-sm whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">Quick Patient Add</span>
</a>
@endif

@stack('scripts')

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        sidebar.classList.toggle('translate-x-0');
        overlay.classList.toggle('opacity-0');
        overlay.classList.toggle('pointer-events-none');
    }

    const searchInput = document.querySelector('header input[type="text"]');
    if (searchInput) {
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.classList.add('ring-2', 'ring-primary/20');
        });
        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.classList.remove('ring-2', 'ring-primary/20');
        });
    }

    document.addEventListener('click', function (e) {
        const menu = document.getElementById('user-menu');
        if (menu && !menu.parentElement.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    const unreadCount = 4;
    if (unreadCount > 0) {
        const messageIcon = document.querySelector('[data-icon="forum"]');
        if (messageIcon) {
            messageIcon.parentElement.classList.add('animate-pulse');
        }
    }
</script>

</body>
</html>
