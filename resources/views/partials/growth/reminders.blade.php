{{-- Vaccine and growth-check reminders for one child. Expects $patient, $childRecord and $latestGrowth. --}}
@php
    $today = \Carbon\Carbon::today();
    $pending = $childRecord ? $childRecord->immunizations->where('status', 'Scheduled')->sortBy('scheduled_date') : collect();
    $overdue = $pending->filter(fn ($i) => \Carbon\Carbon::parse($i->scheduled_date)->lt($today));
    $nextDose = $pending->first(fn ($i) => \Carbon\Carbon::parse($i->scheduled_date)->gte($today));

    // Growth monitoring: monthly for children under 2, every 3 months after (DOH guidance).
    $ageMonths = (int) \Carbon\Carbon::parse($patient->dob)->diffInMonths(\Carbon\Carbon::now());
    $nextGrowthCheck = $latestGrowth
        ? \Carbon\Carbon::parse($latestGrowth->date)->addMonths($ageMonths < 24 ? 1 : 3)
        : null;
@endphp
<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
    <h4 class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant mb-md">Reminders</h4>
    <div class="space-y-sm">
        @if($overdue->isNotEmpty())
        <div class="p-sm bg-error-container/20 border border-error/10 rounded-lg flex gap-sm">
            <span class="material-symbols-outlined text-error">priority_high</span>
            <div>
                <p class="text-label-md font-label-md text-on-error-container">{{ $overdue->count() }} vaccine dose{{ $overdue->count() === 1 ? '' : 's' }} overdue</p>
                <p class="text-label-sm text-on-error-container/70">{{ $overdue->first()->vaccine_name }} dose {{ $overdue->first()->dose_number }} &mdash; due {{ \Carbon\Carbon::parse($overdue->first()->scheduled_date)->format('M d, Y') }}</p>
            </div>
        </div>
        @endif
        <div class="p-sm bg-primary-container/10 border border-primary/10 rounded-lg flex gap-sm">
            <span class="material-symbols-outlined text-primary">vaccines</span>
            <div>
                <p class="text-label-md font-label-md text-on-primary-fixed-variant">Next vaccine</p>
                <p class="text-label-sm text-on-primary-fixed-variant/70">
                    {{ $nextDose ? $nextDose->vaccine_name . ' dose ' . $nextDose->dose_number . ' — ' . \Carbon\Carbon::parse($nextDose->scheduled_date)->format('M d, Y') : 'No upcoming doses scheduled' }}
                </p>
            </div>
        </div>
        <div class="p-sm bg-primary-container/10 border border-primary/10 rounded-lg flex gap-sm">
            <span class="material-symbols-outlined text-primary">calendar_today</span>
            <div>
                <p class="text-label-md font-label-md text-on-primary-fixed-variant">Next growth check</p>
                <p class="text-label-sm text-on-primary-fixed-variant/70">
                    @if($nextGrowthCheck)
                        Around {{ $nextGrowthCheck->format('M d, Y') }}{{ $nextGrowthCheck->lt($today) ? ' (due now)' : '' }}
                    @else
                        No measurement recorded yet
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap justify-center gap-md mt-md">
        <a href="{{ route('immunization', auth()->user()->isAdmin() ? ['id' => $patient->id] : []) }}" class="py-sm text-primary font-label-md text-label-md hover:underline">View immunization record</a>
        @unless(auth()->user()->isAdmin())
        <a href="{{ route('messaging') }}" class="py-sm text-primary font-label-md text-label-md hover:underline">Message my midwife</a>
        @endunless
    </div>
</div>
