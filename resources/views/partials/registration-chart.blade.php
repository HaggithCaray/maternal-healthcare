{{--
    Stacked column chart of new maternal and child registrations per month, with a table view.
    Expects $chartMonths: list of ['label' => 'Sep 2026', 'short' => 'Sep', 'Maternal' => int, 'Child' => int]
    and optionally $emptyMessage.
--}}
@php
    $chartTotals = array_map(fn ($m) => $m['Maternal'] + $m['Child'], $chartMonths);
    $chartPeak = $chartTotals ? max($chartTotals) : 0;

    // Clean y-axis maximum: the smallest of 1, 2, 5 x 10^n that fits the busiest month (4 at minimum).
    $axisMax = 4;
    foreach ([1, 2, 5, 10, 20, 50, 100, 200, 500, 1000] as $step) {
        if ($step * 4 >= $chartPeak) { $axisMax = $step * 4; break; }
    }
    $ticks = [$axisMax, $axisMax * 3 / 4, $axisMax / 2, $axisMax / 4, 0];
    $peakIndex = $chartPeak > 0 ? array_search($chartPeak, $chartTotals, true) : null;
@endphp

<div class="flex items-center gap-md text-label-sm text-on-surface mb-sm" aria-label="Legend">
    <span class="flex items-center gap-xs"><span class="series-maternal inline-block w-3 h-3 rounded-xs"></span>Maternal</span>
    <span class="flex items-center gap-xs"><span class="series-child inline-block w-3 h-3 rounded-xs"></span>Child</span>
</div>

@if($chartPeak === 0)
    <div class="h-56 flex items-center justify-center bg-surface-container-low rounded-lg text-body-sm text-on-surface-variant">
        {{ $emptyMessage ?? 'No patients were registered in this period.' }}
    </div>
@else
<div class="flex gap-xs" role="img" aria-label="Column chart of new registrations per month; the table below lists every value.">
    {{-- Y axis --}}
    <div class="flex flex-col justify-between h-56 pb-5 text-[10px] text-on-surface-variant text-right w-6 tabular-nums" aria-hidden="true">
        @foreach($ticks as $tick)
        <span class="leading-none">{{ $tick == (int) $tick ? (int) $tick : $tick }}</span>
        @endforeach
    </div>
    {{-- Plot --}}
    <div class="relative flex-1 h-56">
        <div class="absolute inset-x-0 top-0 bottom-5 flex flex-col justify-between pointer-events-none" aria-hidden="true">
            @foreach($ticks as $tick)
            <div class="border-t border-outline-variant/50"></div>
            @endforeach
        </div>
        {{-- Columns fill the gridline area exactly, so segment heights are a share of the axis maximum --}}
        <div class="absolute inset-x-0 top-0 bottom-5 flex">
            @foreach($chartMonths as $i => $m)
            <div class="group relative flex-1 h-full flex justify-center outline-hidden rounded-t hover:bg-surface-container-low/70 focus:bg-surface-container-low/70" tabindex="0"
                 aria-label="{{ $m['label'] }}: {{ $m['Maternal'] }} maternal, {{ $m['Child'] }} child, {{ $chartTotals[$i] }} total">
                <div class="h-full w-full max-w-6 flex flex-col justify-end gap-[2px]">
                    @if($i === $peakIndex)
                    <span class="text-[10px] font-bold text-on-surface text-center tabular-nums leading-none mb-[2px]">{{ $chartTotals[$i] }}</span>
                    @endif
                    @if($m['Child'] > 0)
                    <div class="series-child w-full rounded-t shrink-0" style="height: {{ $m['Child'] / $axisMax * 100 }}%"></div>
                    @endif
                    @if($m['Maternal'] > 0)
                    <div class="series-maternal w-full shrink-0 {{ $m['Child'] > 0 ? '' : 'rounded-t' }}" style="height: {{ $m['Maternal'] / $axisMax * 100 }}%"></div>
                    @endif
                </div>
                <div class="pointer-events-none absolute bottom-full mb-1 z-20 hidden group-hover:block group-focus:block bg-inverse-surface text-inverse-on-surface text-[11px] rounded-lg px-sm py-xs shadow-lg whitespace-nowrap">
                    <p class="font-bold">{{ $m['label'] }}</p>
                    <p class="flex items-center gap-xs"><span class="series-maternal inline-block w-2 h-2 rounded-xs"></span>Maternal: {{ $m['Maternal'] }}</p>
                    <p class="flex items-center gap-xs"><span class="series-child inline-block w-2 h-2 rounded-xs"></span>Child: {{ $m['Child'] }}</p>
                    <p>Total: {{ $chartTotals[$i] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="absolute inset-x-0 bottom-0 h-5 flex items-end" aria-hidden="true">
            @foreach($chartMonths as $m)
            <span class="flex-1 text-center text-[10px] text-on-surface-variant leading-none">{{ $m['short'] }}</span>
            @endforeach
        </div>
    </div>
</div>
@endif

<details class="mt-md">
    <summary class="text-label-md text-primary cursor-pointer no-print">Show as table</summary>
    <table class="w-full mt-sm text-body-sm tabular-nums">
        <thead>
            <tr class="text-left text-on-surface-variant border-b border-outline-variant/30">
                <th class="py-xs font-medium">Month</th>
                <th class="py-xs font-medium text-right">Maternal</th>
                <th class="py-xs font-medium text-right">Child</th>
                <th class="py-xs font-medium text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chartMonths as $i => $m)
            <tr class="border-b border-outline-variant/10">
                <td class="py-xs">{{ $m['label'] }}</td>
                <td class="py-xs text-right">{{ $m['Maternal'] }}</td>
                <td class="py-xs text-right">{{ $m['Child'] }}</td>
                <td class="py-xs text-right font-bold">{{ $chartTotals[$i] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</details>
