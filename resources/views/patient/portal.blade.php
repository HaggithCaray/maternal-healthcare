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
            <span>From the health station</span>
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
        <p class="text-body-sm text-on-surface-variant">Chat with the health station</p>
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-gutter">
    <div class="xl:col-span-2 space-y-gutter">
        <div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 p-md">
            <h4 class="font-headline-sm text-headline-sm text-on-surface mb-md flex items-center gap-sm">
                <span class="material-symbols-outlined text-secondary">vaccines</span>
                Immunization Schedule
            </h4>
            @if($children->isEmpty())
            <p class="text-body-sm text-on-surface-variant">
                No children are linked to your record yet. Ask the midwife to link your child when they register them.
            </p>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-gutter">
                @foreach($children as $childRecord)
                @php
                    $child = $childRecord->patient;
                    $ageMonths = $child ? (int) \Carbon\Carbon::parse($child->dob)->diffInMonths(\Carbon\Carbon::now()) : null;
                    $pending = $childRecord->immunizations->where('status', 'Scheduled')->sortBy('scheduled_date');
                    $overdue = $pending->filter(fn ($i) => \Carbon\Carbon::parse($i->scheduled_date)->lt(\Carbon\Carbon::today()));
                    $next = $overdue->first() ?? $pending->first();
                    $isOverdue = $overdue->isNotEmpty();
                @endphp
                <div class="p-sm bg-surface-container-low rounded-lg border-l-4 {{ $isOverdue ? 'border-error' : ($next ? 'border-tertiary' : 'border-outline-variant') }}">
                    <div class="flex items-start justify-between gap-sm">
                        <div>
                            <p class="font-label-md text-on-surface">{{ $child?->first_name }} {{ $child?->last_name }} @if($ageMonths !== null)({{ $ageMonths }}mo)@endif</p>
                            @if($child)
                            <p class="text-label-sm mt-xs flex gap-sm">
                                <a href="{{ route('immunization', ['id' => $child->id]) }}" class="text-primary hover:underline">Vaccines</a>
                                <a href="{{ route('growth', ['id' => $child->id]) }}" class="text-primary hover:underline">Growth</a>
                            </p>
                            @endif
                            <p class="text-body-sm text-on-surface-variant">
                                {{ $next ? $next->vaccine_name . ' — dose ' . $next->dose_number : 'All scheduled vaccines given' }}
                            </p>
                        </div>
                        @if($next)
                        <span class="text-label-sm font-bold whitespace-nowrap {{ $isOverdue ? 'text-error' : 'text-tertiary' }}">{{ \Carbon\Carbon::parse($next->scheduled_date)->format('M d') }}</span>
                        @endif
                    </div>
                    <div class="mt-sm flex items-center gap-1">
                        @if($isOverdue)
                        <span class="material-symbols-outlined text-xs text-error">warning</span>
                        <span class="text-label-sm text-error">{{ $overdue->count() }} overdue &middot; Please visit the health station</span>
                        @elseif($next)
                        <span class="material-symbols-outlined text-xs text-tertiary">event</span>
                        <span class="text-label-sm text-tertiary">Scheduled</span>
                        @else
                        <span class="material-symbols-outlined text-xs text-tertiary">check_circle</span>
                        <span class="text-label-sm text-tertiary">Up to date</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            <a href="{{ route('immunization') }}" class="block text-center w-full mt-md py-sm border border-outline-variant text-primary rounded-lg font-label-md hover:bg-surface-container-high transition-colors">
                View Full Immunization Record
            </a>
        </div>
    </div>

    <div class="space-y-gutter">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm text-on-surface mb-md flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary">forum</span>
                Recent Messages
            </h4>
            <div class="space-y-sm">
                @forelse($recentMessages as $message)
                @php $fromMe = $message->sender_id === auth()->id(); @endphp
                <a href="{{ route('messaging') }}" class="block p-sm bg-surface-container-low rounded-lg hover:bg-surface-container transition-colors border-l-4 {{ ! $fromMe && ! $message->is_read ? 'border-primary' : 'border-outline-variant' }}">
                    <p class="font-label-md text-on-surface">{{ $fromMe ? 'You' : $message->sender?->name }}</p>
                    <p class="text-body-sm text-on-surface-variant truncate">{{ $message->message ?: ($message->attachment_name ? 'Attachment: ' . $message->attachment_name : '') }}</p>
                    <p class="text-[10px] text-outline mt-1">{{ $message->created_at->diffForHumans() }}</p>
                </a>
                @empty
                <p class="text-body-sm text-on-surface-variant">No messages yet. You can ask your midwife anything here.</p>
                @endforelse
            </div>
            <a href="{{ route('messaging') }}" class="block text-center w-full mt-md py-sm border border-outline-variant text-primary rounded-lg font-label-md hover:bg-surface-container-high transition-colors">
                Open Messages{{ $unreadMessagesCount > 0 ? " ({$unreadMessagesCount} unread)" : '' }}
            </a>
        </div>

        <div class="bg-secondary text-on-secondary rounded-xl soft-drop-shadow overflow-hidden p-md relative">
            <div class="relative z-10 pr-12">
                <h5 class="font-headline-sm mb-2">Health Reminder</h5>
                <p class="text-body-sm opacity-90">Drink plenty of water every day, especially during pregnancy, and bring your child's yellow immunization card to every clinic visit.</p>
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
                <dl class="space-y-sm">
                    <div class="flex justify-between gap-sm text-body-sm">
                        <dt class="opacity-80">Name</dt>
                        <dd class="font-bold text-right">{{ $mother ? $mother->full_name : auth()->user()->name }}</dd>
                    </div>
                    @if($mother)
                    <div class="flex justify-between gap-sm text-body-sm">
                        <dt class="opacity-80">Patient ID</dt>
                        <dd class="font-bold text-right">#MC-{{ $mother->created_at->format('Y') }}-{{ sprintf('%03d', $mother->id) }}</dd>
                    </div>
                    <div class="flex justify-between gap-sm text-body-sm">
                        <dt class="opacity-80">Phone</dt>
                        <dd class="font-bold text-right">{{ $mother->phone }}</dd>
                    </div>
                    <div class="flex justify-between gap-sm text-body-sm">
                        <dt class="opacity-80">Blood Type</dt>
                        <dd class="font-bold text-right">{{ $mother->maternalRecord?->blood_type ?: 'Not recorded' }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between gap-sm text-body-sm">
                        <dt class="opacity-80">Children</dt>
                        <dd class="font-bold text-right">
                            @forelse($children as $childRecord)
                                {{ $childRecord->patient?->first_name }} ({{ (int) \Carbon\Carbon::parse($childRecord->patient?->dob)->diffInMonths(\Carbon\Carbon::now()) }}mo)@if(! $loop->last) &middot; @endif
                            @empty
                                None linked
                            @endforelse
                        </dd>
                    </div>
                </dl>
                <div class="mt-md pt-md border-t border-white/20 flex gap-sm">
                    @if($mother?->registration_type === 'Maternal')
                    <a href="{{ route('maternal') }}" class="flex-1 text-center py-sm bg-on-primary/15 text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary/25 transition-colors">
                        My Pregnancy Record
                    </a>
                    @endif
                    <a href="{{ route('account.password') }}" class="flex-1 text-center py-sm bg-on-primary/15 text-on-primary rounded-lg font-label-sm text-label-sm hover:bg-on-primary/25 transition-colors">
                        Change Password
                    </a>
                </div>
                <p class="text-[11px] opacity-80 mt-sm">Something wrong in your details? Tell your midwife so your record can be updated.</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-label-md text-on-surface-variant mb-md uppercase tracking-widest text-[10px]">Recent Activity</h4>
            <div class="space-y-md">
                @forelse($activity as $event)
                <div class="flex gap-sm">
                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $event['level'] === 'alert' ? 'bg-error' : ($event['level'] === 'good' ? 'bg-tertiary' : 'bg-primary') }}"></div>
                    <div>
                        <p class="text-body-sm text-on-surface">{{ $event['text'] }} <span class="font-bold">{{ $event['name'] }}</span></p>
                        <p class="text-[10px] text-outline" title="{{ $event['at']->format('M d, Y g:i A') }}">{{ $event['at']->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <p class="text-body-sm text-on-surface-variant">Nothing recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
