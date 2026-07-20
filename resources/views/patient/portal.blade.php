@extends('layouts.app')

@section('title', 'My Portal')

@push('styles')
<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .module-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0px 8px 30px rgba(145, 158, 171, 0.2);
    }
</style>
@endpush

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-md md:text-headline-lg text-primary">Welcome, {{ auth()->user()?->name ?? 'Mom' }}</h3>
        <p class="font-body-md text-body-md text-on-surface-variant">Here's your health overview at a glance.</p>
    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-gutter mb-lg">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col justify-between h-auto min-h-[88px] md:h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-surface-variant uppercase tracking-wider">Next Vaccine Due</p>
            <h4 class="text-headline-md font-bold text-primary mt-1">{{ $nextVaccineDate }}</h4>
        </div>
        <div class="flex items-center gap-1 text-on-surface-variant font-label-md">
            <span class="material-symbols-outlined text-sm">vaccines</span>
            <span class="truncate">{{ $nextVaccineName }}</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-primary/5 opacity-20">vaccines</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col justify-between h-auto min-h-[88px] md:h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-surface-variant uppercase tracking-wider">Children</p>
            <h4 class="text-headline-md font-bold text-secondary mt-1">{{ $childrenCount }}</h4>
        </div>
        <div class="flex items-center gap-1 text-tertiary font-label-md">
            <span class="material-symbols-outlined text-sm">child_care</span>
            <span>Registered</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-secondary/5 opacity-20">family_history</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col justify-between h-auto min-h-[88px] md:h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-surface-variant uppercase tracking-wider">Overdue Vaccines</p>
            <h4 class="text-headline-md font-bold text-on-surface mt-1">{{ $overdueVaccinesCount }}</h4>
        </div>
        <div class="flex items-center gap-1 text-error font-label-md">
            <span class="material-symbols-outlined text-sm">vaccines</span>
            <span>Requires schedule</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-on-surface/5 opacity-10">warning</span>
    </div>
    <div class="bg-primary-container p-md rounded-xl soft-drop-shadow flex flex-col justify-between h-auto min-h-[88px] md:h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-primary-container uppercase tracking-wider">Unread Messages</p>
            <h4 class="text-headline-md font-bold text-on-primary-container mt-1">{{ $unreadMessagesCount }}</h4>
        </div>
        <div class="flex items-center gap-1 text-on-primary-container font-label-md opacity-80">
            <span class="material-symbols-outlined text-sm">forum</span>
            <span>From your midwife</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-on-primary/10">mail</span>
    </div>
</div>

<h4 class="font-headline-sm text-headline-sm text-on-surface mb-md">Quick Access</h4>
<div class="grid grid-cols-2 md:grid-cols-4 gap-gutter mb-lg">
    <a href="{{ route('immunization') }}" class="module-card bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col items-center text-center gap-sm py-lg md:py-xl cursor-pointer group">
        <div class="w-14 h-14 rounded-full bg-tertiary-fixed/30 flex items-center justify-center group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-tertiary text-[32px]" style="font-variation-settings: 'FILL' 1;">vaccines</span>
        </div>
        <p class="font-label-md text-label-md text-on-surface font-bold">Immunization</p>
        <p class="text-body-sm text-on-surface-variant">View vaccine records</p>
    </a>
    <a href="{{ route('growth') }}" class="module-card bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col items-center text-center gap-sm py-lg md:py-xl cursor-pointer group">
        <div class="w-14 h-14 rounded-full bg-secondary-fixed/30 flex items-center justify-center group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-secondary text-[32px]" style="font-variation-settings: 'FILL' 1;">monitoring</span>
        </div>
        <p class="font-label-md text-label-md text-on-surface font-bold">Growth</p>
        <p class="text-body-sm text-on-surface-variant">Track child development</p>
    </a>
    <a href="{{ route('maternal') }}" class="module-card bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col items-center text-center gap-sm py-lg md:py-xl cursor-pointer group">
        <div class="w-14 h-14 rounded-full bg-primary-fixed/30 flex items-center justify-center group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-primary text-[32px]" style="font-variation-settings: 'FILL' 1;">pregnant_woman</span>
        </div>
        <p class="font-label-md text-label-md text-on-surface font-bold">Maternal</p>
        <p class="text-body-sm text-on-surface-variant">Pregnancy &amp; postnatal</p>
    </a>
    <a href="{{ route('messaging') }}" class="module-card bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col items-center text-center gap-sm py-lg md:py-xl cursor-pointer group">
        <div class="w-14 h-14 rounded-full bg-error-container/30 flex items-center justify-center group-hover:scale-110 transition-transform relative">
            <span class="material-symbols-outlined text-error text-[32px]" style="font-variation-settings: 'FILL' 1;">forum</span>
            @if($unreadMessagesCount > 0)
            <span class="absolute -top-1 -right-1 w-5 h-5 bg-error text-on-error text-[10px] font-bold rounded-full flex items-center justify-center">{{ $unreadMessagesCount }}</span>
            @endif
        </div>
        <p class="font-label-md text-label-md text-on-surface font-bold">Messages</p>
        <p class="text-body-sm text-on-surface-variant">Chat with your midwife</p>
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-gutter">
    <div class="xl:col-span-2 space-y-gutter">
        <div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 p-md">
            <h4 class="font-headline-sm text-headline-sm text-on-surface mb-md flex items-center gap-sm">
                <span class="material-symbols-outlined text-secondary">vaccines</span>
                Immunization Schedule
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-gutter">
                <div class="p-sm bg-surface-container-low rounded-lg border-l-4 border-tertiary">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-label-md text-on-surface">Liam Gabriel (18mo)</p>
                            <p class="text-body-sm text-on-surface-variant">MMR &mdash; 2nd Dose</p>
                        </div>
                        <span class="text-label-sm font-bold text-tertiary">Apr 15</span>
                    </div>
                    <div class="mt-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-tertiary">check_circle</span>
                        <span class="text-label-sm text-tertiary">Scheduled</span>
                    </div>
                </div>
                <div class="p-sm bg-surface-container-low rounded-lg border-l-4 border-error">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-label-md text-on-surface">Sofia Marie (6mo)</p>
                            <p class="text-body-sm text-on-surface-variant">Pentavalent &mdash; 3rd Dose</p>
                        </div>
                        <span class="text-label-sm font-bold text-error">Overdue</span>
                    </div>
                    <div class="mt-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-error">warning</span>
                        <span class="text-label-sm text-error">Due Mar 28 &middot; Please visit clinic</span>
                    </div>
                </div>
            </div>
            <button class="w-full mt-md py-sm border border-outline-variant text-primary rounded-lg font-label-md hover:bg-surface-container-high transition-colors">
                View Full Immunization Record
            </button>
        </div>
    </div>

    <div class="space-y-gutter">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm text-on-surface mb-md flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary">forum</span>
                Recent Messages
            </h4>
            <div class="space-y-sm">
                <div class="p-sm bg-surface-container-low rounded-lg hover:bg-surface-container transition-colors cursor-pointer border-l-4 border-primary">
                    <p class="font-label-md text-on-surface">Dr. Elena Reyes</p>
                    <p class="text-body-sm text-on-surface-variant truncate">Your OGTT results are normal. Next prenatal visit is on schedule.</p>
                    <p class="text-[10px] text-outline mt-1">2 hours ago</p>
                </div>
                <div class="p-sm bg-surface-container-low rounded-lg hover:bg-surface-container transition-colors cursor-pointer border-l-4 border-secondary">
                    <p class="font-label-md text-on-surface">Nurse Maria Santos</p>
                    <p class="text-body-sm text-on-surface-variant truncate">Reminder: Please bring Sofia's yellow card for the immunization this Friday.</p>
                    <p class="text-[10px] text-outline mt-1">Yesterday</p>
                </div>
                <div class="p-sm bg-surface-container-low rounded-lg hover:bg-surface-container transition-colors cursor-pointer border-l-4 border-outline-variant opacity-70">
                    <p class="font-label-md text-on-surface">Barangay Health Office</p>
                    <p class="text-body-sm text-on-surface-variant truncate">Free nutrition counseling every Wednesday at 9 AM. Walk-ins welcome!</p>
                    <p class="text-[10px] text-outline mt-1">3 days ago</p>
                </div>
            </div>
            <a href="{{ route('messaging') }}" class="block text-center w-full mt-md py-sm border border-outline-variant text-primary rounded-lg font-label-md hover:bg-surface-container-high transition-colors">
                Open Messages ({{ $unreadMessagesCount }} Unread)
            </a>
        </div>

        <div class="bg-secondary text-on-secondary rounded-xl soft-drop-shadow overflow-hidden p-md relative">
            <div class="relative z-10 pr-12">
                <h5 class="font-headline-sm mb-2">Health Reminder</h5>
                <p class="text-body-sm opacity-90">Stay hydrated! Drink at least 8 glasses of water daily, especially during pregnancy. Bring your yellow card to all clinic visits.</p>
                <div class="mt-4 flex gap-sm">
                    <span class="px-3 py-1 bg-on-secondary/15 text-on-secondary rounded-full font-label-sm text-[11px]">Pregnancy Tip</span>
                    <span class="px-3 py-1 bg-on-secondary/15 text-on-secondary rounded-full font-label-sm text-[11px]">Immunization</span>
                </div>
            </div>
            <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-9xl opacity-15 text-on-secondary">medical_services</span>
        </div>

        <div class="bg-primary text-on-primary rounded-xl soft-drop-shadow p-md relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-sm mb-md">
                    <span class="material-symbols-outlined">identity_card</span>
                    <h4 class="font-label-md text-label-md uppercase tracking-widest">My Profile</h4>
                </div>
                <div class="space-y-sm">
                    <div class="flex justify-between text-body-sm">
                        <span class="opacity-80">Name</span>
                        <span class="font-bold">{{ auth()->user()?->name ?? 'Maria Santos-Dizon' }}</span>
                    </div>
                    <div class="flex justify-between text-body-sm">
                        <span class="opacity-80">Patient ID</span>
                        <span class="font-bold">#MC-2024-0089</span>
                    </div>
                    <div class="flex justify-between text-body-sm">
                        <span class="opacity-80">Blood Type</span>
                        <span class="font-bold">O+</span>
                    </div>
                    <div class="flex justify-between text-body-sm">
                        <span class="opacity-80">Children</span>
                        <span class="font-bold">Liam (18mo) &middot; Sofia (6mo)</span>
                    </div>
                </div>
                <div class="mt-md pt-md border-t border-white/20 flex gap-sm">
                    <button class="flex-1 py-sm bg-on-primary/15 text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary/25 transition-colors">
                        View Records
                    </button>
                    <button class="flex-1 py-sm bg-on-primary/15 text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary/25 transition-colors">
                        Edit Profile
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-label-md text-on-surface-variant mb-md uppercase tracking-widest text-[10px]">Recent Activity</h4>
            <div class="space-y-md">
                <div class="flex gap-sm">
                    <div class="w-2 h-2 rounded-full bg-tertiary mt-1.5"></div>
                    <div>
                        <p class="text-body-sm text-on-surface">Immunization record updated for <span class="font-bold">Liam</span></p>
                        <p class="text-[10px] text-outline">2 days ago</p>
                    </div>
                </div>
                <div class="flex gap-sm">
                    <div class="w-2 h-2 rounded-full bg-primary mt-1.5"></div>
                    <div>
                        <p class="text-body-sm text-on-surface">Vaccination scheduled: <span class="font-bold">MMR for Liam</span></p>
                        <p class="text-[10px] text-outline">3 days ago</p>
                    </div>
                </div>
                <div class="flex gap-sm">
                    <div class="w-2 h-2 rounded-full bg-secondary mt-1.5"></div>
                    <div>
                        <p class="text-body-sm text-on-surface">Growth metrics logged for <span class="font-bold">Sofia</span></p>
                        <p class="text-[10px] text-outline">1 week ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
