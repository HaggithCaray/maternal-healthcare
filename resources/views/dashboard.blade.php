@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endpush

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Dashboard Overview</h3>
        <p class="font-body-md text-body-md text-on-surface-variant">Welcome back, {{ auth()->user()?->name ?? 'User' }}. Here is what's happening at the health center today.</p>
    </div>
    <div class="flex gap-sm w-full sm:w-auto">
        <a href="{{ route('register') }}" class="grow sm:flex-initial flex items-center justify-center gap-xs px-md py-sm bg-secondary text-on-secondary rounded-lg font-label-md hover:bg-secondary/90 transition-all soft-drop-shadow active:scale-95">
            <span class="material-symbols-outlined text-[20px]">person_add</span>
            Add Patient
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-gutter mb-lg">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col justify-between h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-surface-variant uppercase tracking-wider">Total Mothers</p>
            <h4 class="text-headline-md font-bold text-primary mt-1">{{ number_format($totalMothers) }}</h4>
        </div>
        <div class="flex items-center gap-1 text-tertiary font-label-md">
            <span class="material-symbols-outlined text-sm">trending_up</span>
            <span>Active monitoring</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-primary/5 opacity-20">pregnant_woman</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col justify-between h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-surface-variant uppercase tracking-wider">Children Monitored</p>
            <h4 class="text-headline-md font-bold text-secondary mt-1">{{ number_format($totalChildren) }}</h4>
        </div>
        <div class="flex items-center gap-1 text-tertiary font-label-md">
            <span class="material-symbols-outlined text-sm">trending_up</span>
            <span>Active growth records</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-secondary/5 opacity-20">child_care</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10 flex flex-col justify-between h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-surface-variant uppercase tracking-wider">Today's Vaccinations</p>
            <h4 class="text-headline-md font-bold text-on-surface mt-1">{{ $todayVaccinations }}</h4>
        </div>
        <div class="flex items-center gap-1 text-on-surface-variant font-label-md">
            <span class="material-symbols-outlined text-sm">vaccines</span>
            <span>Due today</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-on-surface/5 opacity-10">vaccines</span>
    </div>
    <div class="bg-primary-container p-md rounded-xl soft-drop-shadow flex flex-col justify-between h-32 relative overflow-hidden">
        <div>
            <p class="text-label-sm font-label-md text-on-primary-container uppercase tracking-wider">Unread Messages</p>
            <h4 class="text-headline-md font-bold text-on-primary-container mt-1">{{ sprintf('%02d', $unreadMessages) }}</h4>
        </div>
        <div class="flex items-center gap-1 text-on-primary-container font-label-md opacity-80">
            <span class="material-symbols-outlined text-sm">mail</span>
            <span>From maternal patients</span>
        </div>
        <span class="material-symbols-outlined absolute -right-2 -bottom-2 text-6xl text-on-primary/10">forum</span>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-gutter">
    <div class="xl:col-span-2 space-y-gutter">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <div class="mb-md">
                <h4 class="font-headline-sm text-on-surface">Registration Trends</h4>
                <p class="text-body-sm text-on-surface-variant">New patients in the last six months</p>
            </div>
            @include('partials.registration-chart', ['emptyMessage' => 'No patients were registered in the last six months.'])
        </div>

        <div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 overflow-hidden">
            <div class="p-md border-b border-outline-variant/20 flex justify-between items-center">
                <h4 class="font-headline-sm text-on-surface">Vaccination Schedule</h4>
                
            </div>
            <div class="divide-y divide-outline-variant/20">
                @forelse($upcomingVaccinations as $imm)
                <div class="p-md flex items-center justify-between hover:bg-surface-container-low transition-colors">
                    <div class="flex items-center gap-md">
                        <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed">
                            <span class="material-symbols-outlined">vaccines</span>
                        </div>
                        <div>
                            <p class="font-label-md text-on-surface">{{ $imm->childRecord->patient->first_name }} {{ $imm->childRecord->patient->last_name }}</p>
                            <p class="text-body-sm text-on-surface-variant">{{ $imm->vaccine_name }} (Dose {{ $imm->dose_number }})</p>
                        </div>
                    </div>
                    @php $isOverdue = \Carbon\Carbon::parse($imm->scheduled_date)->lt(\Carbon\Carbon::today()); @endphp
                    <div class="text-right">
                        <p class="font-label-md {{ $isOverdue ? 'text-error' : 'text-primary' }}">{{ \Carbon\Carbon::parse($imm->scheduled_date)->format('M d, Y') }}</p>
                        <p class="text-label-sm {{ $isOverdue ? 'text-error font-bold' : 'text-on-surface-variant' }}">{{ $isOverdue ? 'Overdue' : 'Scheduled' }}</p>
                    </div>
                </div>
                @empty
                <div class="p-md text-center text-on-surface-variant text-body-sm">
                    No upcoming vaccinations scheduled.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-gutter">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-on-surface mb-md">Today's Immunizations</h4>
            <div class="space-y-sm">
                @forelse($upcomingVaccinations->filter(fn($v) => \Carbon\Carbon::parse($v->scheduled_date)->isToday()) as $imm)
                <div class="p-sm bg-surface-container-low rounded-lg border-l-4 border-primary">
                    <p class="font-label-md text-on-surface">{{ $imm->vaccine_name }} - Dose {{ $imm->dose_number }}</p>
                    <p class="text-body-sm text-on-surface-variant">{{ $imm->childRecord->patient->first_name }} {{ $imm->childRecord->patient->last_name }}</p>
                </div>
                @empty
                <div class="p-sm bg-surface-container-low rounded-lg text-center text-on-surface-variant text-body-sm">
                    No vaccinations scheduled for today.
                </div>
                @endforelse
            </div>
            <a href="{{ route('immunization') }}" class="block text-center w-full mt-md py-sm border border-outline-variant text-primary rounded-lg font-label-md hover:bg-surface-container-high transition-colors">
                View Full Schedule
            </a>
        </div>

        <div class="bg-secondary text-on-secondary rounded-xl soft-drop-shadow overflow-hidden p-md relative">
            <div class="relative z-10 pr-12">
                <h5 class="font-headline-sm mb-2">Community Health Tip</h5>
                <p class="text-body-sm opacity-90">Remind mothers to bring their child's yellow immunization card to every visit.</p>
                <a href="{{ route('sms') }}" class="inline-block mt-4 px-4 py-2 bg-on-secondary text-secondary rounded-lg font-label-md text-xs uppercase font-bold tracking-tight">Send SMS</a>
            </div>
            <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-9xl opacity-20 text-on-secondary">health_and_safety</span>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-label-md text-on-surface-variant mb-md uppercase tracking-widest text-[10px]">Recent Activity</h4>
            <div class="space-y-md">
                @forelse($recentActivity as $event)
                <div class="flex gap-sm">
                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $event['level'] === 'alert' ? 'bg-error' : ($event['level'] === 'good' ? 'bg-tertiary' : 'bg-primary') }}"></div>
                    <div>
                        <p class="text-body-sm text-on-surface">{{ $event['text'] }} <span class="font-bold">{{ $event['name'] }}</span></p>
                        <p class="text-[10px] text-outline" title="{{ $event['at']->format('M d, Y g:i A') }}">{{ $event['at']->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <p class="text-body-sm text-on-surface-variant">No activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
