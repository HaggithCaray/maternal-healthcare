<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maternal Health Hub - Maternal &amp; Child Care Login</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
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
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    spacing: {
                        gutter: "24px",
                        base: "8px",
                        lg: "40px",
                        md: "24px",
                        "margin-desktop": "48px",
                        xl: "64px",
                        "margin-mobile": "16px",
                        xs: "4px",
                        sm: "12px"
                    },
                    fontFamily: {
                        "label-md": ["Inter"],
                        "body-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-sm": ["Inter"],
                        "headline-lg-mobile": ["Inter"],
                        "headline-sm": ["Inter"],
                        "headline-lg": ["Inter"],
                        "headline-md": ["Inter"],
                        "label-sm": ["Inter"]
                    },
                    fontSize: {
                        "label-md": ["14px", {"lineHeight": "20px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                        "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "700"}],
                        "headline-sm": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}]
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet">

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
</body>
</html>
