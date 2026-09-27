{{--
    WHO gross motor milestone windows (WHO Motor Development Study, 2006: 1st–99th percentile, months),
    compared with the child's age. Achievements are not recorded, so these are expectations only.
    Expects $patient.
--}}
@php
    $ageMonths = \Carbon\Carbon::parse($patient->dob)->diffInMonths(\Carbon\Carbon::now());
    $milestones = [
        ['Sitting without support', 3.8, 9.2],
        ['Standing with assistance', 4.8, 11.4],
        ['Hands-and-knees crawling', 5.2, 13.5],
        ['Walking with assistance', 5.9, 13.7],
        ['Standing alone', 6.9, 16.9],
        ['Walking alone', 8.2, 17.6],
    ];
@endphp
<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
    <h4 class="font-headline-sm text-headline-sm mb-xs flex items-center gap-sm">
        <span class="material-symbols-outlined text-secondary">directions_walk</span>
        Motor Milestones
    </h4>
    <p class="text-xs text-on-surface-variant mb-md">When most children reach each skill (WHO). At {{ (int) $ageMonths }} months:</p>
    @if($ageMonths > 24)
    <p class="text-body-sm text-on-surface-variant">All six WHO gross motor milestones are usually reached by 18 months. Ask the midwife about language and social milestones for older children.</p>
    @else
    <div class="space-y-xs">
        @foreach($milestones as [$skill, $from, $to])
        @php
            $state = $ageMonths < $from ? 'upcoming' : ($ageMonths <= $to ? 'now' : 'past');
        @endphp
        <div class="flex items-center gap-sm p-sm rounded-lg {{ $state === 'now' ? 'bg-secondary-container/20' : ($state === 'past' ? 'bg-surface-container-low' : 'border border-dashed border-outline-variant') }}">
            <span class="material-symbols-outlined text-[20px] {{ $state === 'now' ? 'text-secondary' : ($state === 'past' ? 'text-on-surface-variant' : 'text-outline') }}">
                {{ $state === 'now' ? 'schedule' : ($state === 'past' ? 'task_alt' : 'pending') }}
            </span>
            <div class="flex-1">
                <p class="text-label-md font-label-md">{{ $skill }}</p>
                <p class="text-label-sm text-on-surface-variant">
                    Usually {{ rtrim(rtrim(number_format($from, 1), '0'), '.') }}–{{ rtrim(rtrim(number_format($to, 1), '0'), '.') }} months
                    @if($state === 'now') &middot; <span class="font-bold text-secondary">expected around now</span>@endif
                    @if($state === 'past') &middot; expected by now — tell the midwife if not yet @endif
                </p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
