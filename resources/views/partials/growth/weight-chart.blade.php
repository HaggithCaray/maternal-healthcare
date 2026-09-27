{{-- Weight of the latest 7 measurements. Expects $growthMeasurements. --}}
@php
    $recent = $growthMeasurements->sortBy('date')->take(-7)->values();
    $maxWeight = max(1, (float) $recent->max('weight_kg'));
@endphp
<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
    <div class="flex items-center justify-between mb-lg">
        <div>
            <h4 class="font-headline-sm text-headline-sm">Growth Velocity</h4>
            <p class="text-body-sm text-on-surface-variant">Weight at the last {{ max(1, $recent->count()) }} measurement{{ $recent->count() === 1 ? '' : 's' }}</p>
        </div>
    </div>
    <div class="relative h-64 w-full bg-surface-container-low rounded-lg overflow-hidden flex items-end px-md pb-md">
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#00478d 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
        <div class="flex items-end justify-between w-full h-4/5 gap-xs md:gap-sm z-10">
            @forelse($recent as $gm)
                @php
                    $heightPct = min(90, max(15, round(($gm->weight_kg / $maxWeight) * 80)));
                    $flagged = ! in_array($gm->status, [\App\Services\WhoGrowthStandards::NORMAL, \App\Services\WhoGrowthStandards::NOT_ASSESSED, null], true);
                @endphp
                <div class="w-full {{ $flagged ? 'bg-error/40' : 'bg-primary/20' }} rounded-t-sm relative group cursor-pointer" style="height: {{ $heightPct }}%" title="{{ $gm->status }}">
                    <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-inverse-surface text-inverse-on-surface text-[10px] px-xs py-1 rounded hidden group-hover:block whitespace-nowrap">{{ $gm->weight_kg }}kg</div>
                </div>
            @empty
                <div class="w-full text-center text-on-surface-variant text-xs py-12">No growth metrics recorded yet</div>
            @endforelse
        </div>
    </div>
    <div class="flex justify-between mt-sm text-label-sm text-on-surface-variant px-md">
        @forelse($recent as $gm)
            <span>{{ $gm->age_months }}m</span>
        @empty
            <span>Birth</span>
        @endforelse
    </div>
</div>
