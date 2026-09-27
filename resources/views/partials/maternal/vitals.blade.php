{{-- Latest blood pressure and weight. Expects $checkups and $latestCheckup (either may be empty). --}}
@php
    $bpCategory = \App\Services\PrenatalAssessment::bloodPressureCategory($latestCheckup?->bp);
    $bpBadge = match ($bpCategory) {
        'Severe', 'High' => 'bg-error-container text-on-error-container',
        'Low' => 'bg-amber-100 text-amber-800',
        default => 'bg-tertiary-fixed text-on-tertiary-fixed',
    };
    $bpNote = match ($bpCategory) {
        'Severe' => 'Severe range (≥160/110) — urgent referral.',
        'High' => 'At or above 140/90 — screen for pre-eclampsia.',
        'Low' => 'Below 90/60 — monitor.',
        'Normal' => 'Within the normal range at the last visit.',
        default => 'No blood pressure recorded yet.',
    };

    $firstCheckup = $checkups->sortBy([['date', 'asc'], ['visit_number', 'asc']])->first();
    $weightChange = $latestCheckup && $firstCheckup && $latestCheckup->isNot($firstCheckup) && $latestCheckup->weight_kg && $firstCheckup->weight_kg
        ? round($latestCheckup->weight_kg - $firstCheckup->weight_kg, 1)
        : null;
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
        <div class="flex justify-between items-start mb-sm">
            <div>
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Blood Pressure</p>
                <h5 class="text-headline-md font-bold text-on-surface">{{ $latestCheckup?->bp ?? '—' }} @if($latestCheckup?->bp)<span class="text-body-sm font-normal">mmHg</span>@endif</h5>
            </div>
            @if($bpCategory)
            <div class="{{ $bpBadge }} px-2 py-1 rounded text-[10px] font-bold uppercase">{{ $bpCategory }}</div>
            @endif
        </div>
        <p class="text-xs text-on-surface-variant mt-sm">{{ $bpNote }}</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
        <div class="flex justify-between items-start mb-sm">
            <div>
                <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Latest Weight</p>
                <h5 class="text-headline-md font-bold text-on-surface">{{ $latestCheckup?->weight_kg ?? '—' }} @if($latestCheckup?->weight_kg)<span class="text-body-sm font-normal">kg</span>@endif</h5>
            </div>
        </div>
        <p class="text-xs text-on-surface-variant mt-sm">
            @if($weightChange !== null)
                {{ $weightChange >= 0 ? '+' : '' }}{{ $weightChange }} kg since the first recorded visit ({{ \Carbon\Carbon::parse($firstCheckup->date)->format('M d, Y') }}).
            @elseif($latestCheckup)
                Weight change will show after the next visit.
            @else
                No weight recorded yet.
            @endif
        </p>
    </div>
</div>
