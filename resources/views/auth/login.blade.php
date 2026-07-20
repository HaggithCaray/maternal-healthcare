@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 bg-surface-container-lowest rounded-xl overflow-hidden soft-medical-shadow min-h-[700px]">
    <div class="hidden lg:flex flex-col justify-center items-center p-xl relative bg-gradient-to-br from-surface-container-low to-surface-container-high overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-secondary/5 rounded-full -ml-48 -mb-48"></div>
        <div class="relative z-10 text-center max-w-md">
            <div class="mb-lg flex justify-center items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" class="w-full h-auto max-w-[340px] mx-auto drop-shadow-xl select-none pointer-events-none">
                  <defs>
                    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#EBF5FF" />
                      <stop offset="100%" stop-color="#E6F2FF" />
                    </linearGradient>
                    <linearGradient id="nurseScrubs" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#0d9488" />
                      <stop offset="100%" stop-color="#0f766e" />
                    </linearGradient>
                    <linearGradient id="babyBlanket" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#f472b6" />
                      <stop offset="100%" stop-color="#db2777" />
                    </linearGradient>
                    <linearGradient id="monitorGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" stop-color="#1e293b" />
                      <stop offset="100%" stop-color="#0f172a" />
                    </linearGradient>
                    <linearGradient id="windowGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#f1f5f9" />
                      <stop offset="100%" stop-color="#e2e8f0" />
                    </linearGradient>
                  </defs>

                  <!-- Background blob -->
                  <path d="M 100,300 C 100,150 200,100 350,100 C 500,100 700,150 700,300 C 700,450 550,500 400,500 C 250,500 100,450 100,300 Z" fill="url(#bgGrad)" opacity="0.6"/>
                  <circle cx="200" cy="180" r="120" fill="#e0f2fe" opacity="0.5"/>
                  <circle cx="620" cy="380" r="100" fill="#ccfbf1" opacity="0.4"/>

                  <!-- Background Window -->
                  <g opacity="0.8">
                    <rect x="180" y="80" width="180" height="240" rx="10" fill="url(#windowGrad)"/>
                    <line x1="270" y1="80" x2="270" y2="320" stroke="#ffffff" stroke-width="4"/>
                    <line x1="180" y1="180" x2="360" y2="180" stroke="#ffffff" stroke-width="4"/>
                    <path d="M 140,320 L 400,320" stroke="#94a3b8" stroke-width="6" stroke-linecap="round"/>
                  </g>

                  <!-- Background Medical Monitor -->
                  <g transform="translate(520, 180)">
                    <path d="M 60,180 L 100,180 M 80,140 L 80,180" stroke="#64748b" stroke-width="8" stroke-linecap="round"/>
                    <rect x="10" y="20" width="140" height="120" rx="8" fill="#475569"/>
                    <rect x="18" y="28" width="124" height="104" rx="4" fill="url(#monitorGrad)"/>
                    <path d="M 25,80 L 50,80 L 55,60 L 60,110 L 65,70 L 70,85 L 75,80 L 100,80 L 105,50 L 110,100 L 115,75 L 120,80 L 135,80" fill="none" stroke="#14b8a6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <text x="25" y="48" fill="#38bdf8" font-family="sans-serif" font-size="12" font-weight="bold">HR 78</text>
                    <text x="105" y="48" fill="#10b981" font-family="sans-serif" font-size="12" font-weight="bold">SpO2 99%</text>
                    <path d="M 80,6 C 75,6 75,12 80,12 C 85,12 85,6 80,6 Z" fill="#38bdf8" opacity="0.7"/>
                  </g>

                  <!-- Decorative Plant (Left Front) -->
                  <g transform="translate(100, 360)">
                    <path d="M 10,200 Q 15,100 50,70" fill="none" stroke="#0d9488" stroke-width="6" stroke-linecap="round"/>
                    <path d="M 50,70 Q 20,60 10,95 Q 20,130 50,70" fill="#14b8a6" opacity="0.85"/>
                    <path d="M 10,200 Q 30,120 90,110" fill="none" stroke="#0d9488" stroke-width="5" stroke-linecap="round"/>
                    <path d="M 90,110 Q 60,95 45,125 Q 60,165 90,110" fill="#2dd4bf" opacity="0.85"/>
                    <path d="M 10,200 Q -10,130 10,110" fill="none" stroke="#0d9488" stroke-width="4" stroke-linecap="round"/>
                    <path d="M 10,110 Q -10,90 -25,120 Q -10,150 10,110" fill="#0d9488" opacity="0.85"/>
                  </g>

                  <!-- The Nurse/Midwife -->
                  <g>
                    <circle cx="390" cy="200" r="42" fill="#1e293b"/>
                    <path d="M 280,480 C 280,380 340,320 400,320 C 460,320 520,380 520,480 Z" fill="url(#nurseScrubs)"/>
                    <path d="M 370,320 L 400,360 L 430,320" fill="none" stroke="#e0f2fe" stroke-width="4"/>
                    <path d="M 373,323 L 400,358 L 427,323" fill="#0f172a" opacity="0.1"/>
                    
                    <path d="M 375,270 L 375,330 L 425,330 L 425,270 Z" fill="#ffd1b3"/>
                    <circle cx="400" cy="230" r="45" fill="#ffe0cc"/>
                    
                    <path d="M 355,225 C 365,190 410,185 440,205 C 445,210 445,220 445,230 C 420,200 380,210 365,235 Z" fill="#0f172a"/>
                    <path d="M 355,220 C 358,200 375,190 395,192 C 375,198 362,210 355,220 Z" fill="#334155"/>
                    
                    <path d="M 375,235 Q 382,242 390,235" fill="none" stroke="#334155" stroke-width="3" stroke-linecap="round"/>
                    <path d="M 410,235 Q 418,242 425,235" fill="none" stroke="#334155" stroke-width="3" stroke-linecap="round"/>
                    <path d="M 398,235 L 398,246 Q 398,249 402,249" fill="none" stroke="#e0a683" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M 388,258 Q 400,268 412,258" fill="none" stroke="#e11d48" stroke-width="3.5" stroke-linecap="round"/>
                    <circle cx="370" cy="248" r="6" fill="#f43f5e" opacity="0.3"/>
                    <circle cx="430" cy="248" r="6" fill="#f43f5e" opacity="0.3"/>

                    <path d="M 300,430 Q 320,480 390,480 Q 420,480 435,465" fill="none" stroke="#0d9488" stroke-width="36" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M 300,430 Q 320,480 390,480 Q 420,480 435,465" fill="none" stroke="#14b8a6" stroke-width="28" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="435" cy="460" r="16" fill="#ffe0cc"/>

                    <path d="M 490,430 Q 480,470 420,490 Q 380,500 350,490" fill="none" stroke="#0f766e" stroke-width="36" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M 490,430 Q 480,470 420,490 Q 380,500 350,490" fill="none" stroke="#0d9488" stroke-width="28" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="350" cy="487" r="16" fill="#ffe0cc"/>
                  </g>

                  <!-- Baby -->
                  <g transform="translate(10, -5)">
                    <path d="M 360,450 C 340,430 330,390 355,360 C 375,340 430,360 450,385 C 470,410 470,450 440,470 C 410,490 380,470 360,450 Z" fill="url(#babyBlanket)"/>
                    <path d="M 355,360 Q 400,390 440,375" fill="none" stroke="#ffffff" stroke-width="3" opacity="0.6" stroke-linecap="round"/>
                    <path d="M 345,410 Q 395,430 445,420" fill="none" stroke="#ffffff" stroke-width="3" opacity="0.6" stroke-linecap="round"/>
                    <path d="M 360,450 Q 400,460 430,450" fill="none" stroke="#ffffff" stroke-width="3" opacity="0.6" stroke-linecap="round"/>

                    <circle cx="370" cy="360" r="28" fill="#ffd1b3"/>
                    
                    <path d="M 355,360 Q 360,364 365,360" fill="none" stroke="#475569" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M 375,360 Q 380,364 385,360" fill="none" stroke="#475569" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M 365,372 Q 370,377 375,372" fill="none" stroke="#e11d48" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="355" cy="368" r="4" fill="#f43f5e" opacity="0.3"/>
                    <circle cx="385" cy="368" r="4" fill="#f43f5e" opacity="0.3"/>
                    
                    <path d="M 342,360 C 342,330 398,330 398,360 Z" fill="#38bdf8"/>
                    <circle cx="370" cy="328" r="8" fill="#ffffff"/>
                  </g>

                  <!-- Heart/Love icon floating -->
                  <path d="M 12,5 C 8,1 2,1 2,6 C 2,11 12,17 12,17 C 12,17 22,11 22,6 C 22,1 16,1 12,5 Z" fill="#f43f5e" opacity="0.8" transform="translate(480, 240) scale(1.5)"/>
                  <!-- Sparkles -->
                  <path d="M 480,260 L 485,270 L 495,272 L 487,279 L 490,289 L 480,282 L 470,289 L 473,279 L 465,272 L 475,270 Z" fill="#fbbf24" opacity="0.9" transform="scale(0.4) translate(680, 220)"/>
                </svg>
            </div>
            <div class="flex justify-center items-center gap-4 mb-4 select-none">
                <img src="{{ asset('images/bayan_ng_carmen.jpg') }}" alt="Bayan ng Carmen Logo" class="w-16 h-16 object-contain rounded-full shadow-md bg-white border border-outline-variant/30">
                <img src="{{ asset('images/DOH.jpg') }}" alt="DOH Logo" class="w-16 h-16 object-contain rounded-full shadow-md bg-white border border-outline-variant/30">
            </div>
            <h1 class="font-headline-lg text-headline-lg text-primary mb-sm">Maternal Health Hub</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Dedicated to the well-being of mothers and children in our community with professional, modern care.</p>
            <div class="mt-xl flex flex-wrap justify-center gap-sm">
                <div class="flex items-center gap-xs px-sm py-xs bg-surface-container-lowest rounded-full border border-outline-variant/30 text-primary">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                    <span class="font-label-sm text-label-sm">Secure Data</span>
                </div>
                <div class="flex items-center gap-xs px-sm py-xs bg-surface-container-lowest rounded-full border border-outline-variant/30 text-secondary">
                    <span class="material-symbols-outlined text-[18px]">favorite</span>
                    <span class="font-label-sm text-label-sm">Community Focus</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col justify-center p-md md:p-xl lg:px-xl">
        <div class="mb-lg lg:hidden flex flex-col items-center">
            <div class="flex items-center gap-3 mb-3 select-none">
                <img src="{{ asset('images/bayan_ng_carmen.jpg') }}" alt="Bayan ng Carmen Logo" class="w-12 h-12 object-contain rounded-full shadow-md bg-white border border-outline-variant/30">
                <img src="{{ asset('images/DOH.jpg') }}" alt="DOH Logo" class="w-12 h-12 object-contain rounded-full shadow-md bg-white border border-outline-variant/30">
            </div>
            <h2 class="font-headline-md text-headline-md text-primary">Maternal Health Hub</h2>
        </div>
        <div class="max-w-md w-full mx-auto">
            <header class="mb-xl text-left">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-xs">Welcome Back</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Please sign in to access the Monitoring System</p>
            </header>

            @if ($errors->any())
                <div class="mb-md p-sm bg-error-container text-on-error-container rounded-lg font-body-sm text-body-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-md">
                @csrf

                <div class="space-y-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="role">Select Your Role</label>
                    <div class="relative">
                        <select class="w-full h-12 pl-10 pr-md bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md focus:ring-2 focus:ring-primary focus:border-primary transition-all appearance-none cursor-pointer" id="role" name="role">
                            <option value="admin">Healthcare Worker</option>
                            <option value="user">Patient</option>
                        </select>
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant">badge</span>
                        <span class="material-symbols-outlined absolute right-sm top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant">expand_more</span>
                    </div>
                </div>

                <div class="space-y-xs">
                    <label class="block font-label-md text-label-md text-on-surface" for="email">Email or Username</label>
                    <div class="relative">
                        <input class="w-full h-12 pl-10 pr-md bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="email" type="text" name="email" value="{{ old('email') }}" placeholder="Enter your credentials" required autofocus>
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant">person</span>
                    </div>
                </div>

                <div class="space-y-xs">
                    <div class="flex justify-between items-center">
                        <label class="block font-label-md text-label-md text-on-surface" for="password">Password</label>
                    </div>
                    <div class="relative">
                        <input class="w-full h-12 pl-10 pr-12 bg-surface-container-lowest border border-outline-variant rounded-lg font-body-md text-body-md focus:ring-2 focus:ring-primary focus:border-primary transition-all" id="password" type="password" name="password" placeholder="••••••••" required>
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant">lock</span>
                        <button class="absolute right-sm top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors" type="button" onclick="togglePassword()">
                            <span class="material-symbols-outlined" id="toggleIcon">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-xs cursor-pointer group">
                        <input class="w-5 h-5 border-2 border-outline rounded focus:ring-2 focus:ring-primary checked:bg-primary checked:border-primary transition-all" type="checkbox" name="remember" id="remember">
                        <span class="font-body-sm text-body-sm text-on-surface-variant group-hover:text-on-surface">Remember me</span>
                    </label>
                    <a class="font-label-md text-label-md text-secondary hover:underline transition-all" href="#">Forgot password?</a>
                </div>

                <button class="w-full h-12 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-sm" type="submit">
                    Sign In to Portal
                    <span class="material-symbols-outlined">login</span>
                </button>
            </form>

            <footer class="mt-xl text-center space-y-md">
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Need access? <a class="text-primary font-bold hover:underline" href="#">Contact Health Center Admin</a>
                </p>
                <div class="pt-lg border-t border-outline-variant/30">
                    <p class="font-label-sm text-label-sm text-outline flex items-center justify-center gap-xs">
                        <span class="material-symbols-outlined text-[14px]">copyright</span>
                        2026 Maternal Health Hub. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePassword() {
        const passInput = document.querySelector('#password');
        const icon = document.querySelector('#toggleIcon');
        if (passInput.type === 'password') {
            passInput.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            passInput.type = 'password';
            icon.textContent = 'visibility';
        }
    }

    const formInputs = document.querySelectorAll('input, select');
    const mainCard = document.querySelector('.soft-medical-shadow');
    formInputs.forEach(input => {
        input.addEventListener('focus', () => {
            mainCard.style.boxShadow = '0px 10px 40px rgba(0, 71, 141, 0.1)';
        });
        input.addEventListener('blur', () => {
            mainCard.style.boxShadow = '0px 4px 20px rgba(145, 158, 171, 0.15)';
        });
    });
</script>
@endpush
