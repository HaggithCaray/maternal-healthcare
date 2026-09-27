{{-- WHO nutritional status of the latest measurement. Expects $latestGrowth (may be null). --}}
<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
    <h4 class="font-headline-sm text-headline-sm mb-md flex items-center gap-sm">
        <span class="material-symbols-outlined text-tertiary">restaurant</span>
        Nutritional Status
    </h4>
    @if(! $latestGrowth || $latestGrowth->status === \App\Services\WhoGrowthStandards::NOT_ASSESSED)
        <div class="p-md rounded-xl bg-surface-container text-center">
            <p class="text-body-md font-bold text-on-surface-variant">Not assessed</p>
            <p class="text-body-sm text-on-surface-variant mt-xs">
                {{ $latestGrowth ? 'WHO standards cover children from birth to 5 years.' : 'Log a weight and height to see the WHO assessment.' }}
            </p>
        </div>
    @else
        @php $isNormal = $latestGrowth->status === \App\Services\WhoGrowthStandards::NORMAL; @endphp
        <div class="p-md rounded-xl text-center border-l-4 {{ $isNormal ? 'bg-tertiary-fixed-dim/20 border-tertiary' : 'bg-error-container/20 border-error' }}">
            <p class="text-headline-md font-headline-md uppercase {{ $isNormal ? 'text-tertiary' : 'text-error' }}">{{ $isNormal ? 'Normal' : $latestGrowth->status }}</p>
            <p class="text-body-sm text-on-surface-variant mt-xs">Measured {{ \Carbon\Carbon::parse($latestGrowth->date)->format('M d, Y') }} at {{ $latestGrowth->age_months }} months</p>
        </div>
        <div class="mt-md space-y-xs">
            @foreach($latestGrowth->indicators() as $indicator)
            <div class="flex justify-between items-center text-label-sm gap-sm">
                <span>{{ $indicator['label'] }}</span>
                <span class="font-bold {{ $indicator['status'] === 'Normal' ? 'text-tertiary' : ($indicator['status'] === 'Tall' ? 'text-on-surface-variant' : 'text-error') }}">
                    @if($indicator['z'] === null)
                        —
                    @else
                        {{ $indicator['status'] }} (z {{ $indicator['z'] >= 0 ? '+' : '' }}{{ number_format($indicator['z'], 2) }})
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        <p class="mt-md text-[11px] text-on-surface-variant italic">WHO Child Growth Standards. Below −2 SD is moderate, below −3 SD is severe.</p>
    @endif
</div>
