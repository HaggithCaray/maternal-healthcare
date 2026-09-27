@extends('layouts.app')

@section('title', 'Reports')

@push('styles')
<style>
    /* Chart series colors (validated for color-vision deficiency and contrast on white) */
    .series-maternal { background-color: #2a78d6; }
    .series-child { background-color: #eb6834; }

    @media print {
        #sidebar, main > header, main > footer, .no-print, .fixed { display: none !important; }
        main { margin-left: 0 !important; }
        .soft-drop-shadow { box-shadow: none !important; border: 1px solid #c2c6d4; }
        details > table { display: table !important; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
@php
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $totals = array_map('array_sum', $monthlyRegistrations);
    $peakTotal = max($totals);

    // Clean y-axis maximum: the smallest of 1, 2, 5 x 10^n that fits the busiest month (4 at minimum).
    $axisMax = 4;
    foreach ([1, 2, 5, 10, 20, 50, 100, 200, 500, 1000] as $step) {
        if ($step * 4 >= $peakTotal) { $axisMax = $step * 4; break; }
    }
    $ticks = [$axisMax, $axisMax * 3 / 4, $axisMax / 2, $axisMax / 4, 0];
    $peakMonth = $peakTotal > 0 ? array_search($peakTotal, $totals, true) : null;
@endphp

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Reports &amp; Analytics</h3>
        <p class="text-on-surface-variant font-body-md">Barangay Bicao maternal and child health summary for {{ $year }}.</p>
    </div>
    <div class="flex items-center gap-sm no-print">
        <form method="GET" action="{{ route('reports') }}" class="flex items-center gap-xs">
            <label for="report-year" class="text-label-md text-on-surface-variant">Year</label>
            <select id="report-year" name="year" onchange="this.form.submit()" class="bg-surface-container border border-outline-variant rounded-lg px-md py-xs text-label-md font-label-md text-on-surface focus:ring-1 focus:ring-primary">
                @foreach($years as $option)
                <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="px-sm py-xs border border-outline-variant rounded-lg text-label-md">Show</button></noscript>
        </form>
        <button type="button" onclick="window.print()" class="bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all shadow-sm whitespace-nowrap">
            <span class="material-symbols-outlined">print</span>
            Print Report
        </button>
    </div>
</div>

{{-- Headline figures --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-md mb-lg">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">Registered patients</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs">{{ number_format($totalPatients) }}</p>
        <p class="text-xs text-on-surface-variant mt-xs">{{ number_format($maternalCases) }} mothers &bull; {{ number_format($childRecords) }} children</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">New registrations in {{ $year }}</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs">{{ number_format($registeredInYear) }}</p>
        <p class="text-xs text-on-surface-variant mt-xs">
            {{ number_format(array_sum(array_column($monthlyRegistrations, 'Maternal'))) }} mothers &bull;
            {{ number_format(array_sum(array_column($monthlyRegistrations, 'Child'))) }} children
        </p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">Immunization coverage, {{ $year }}</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs">{{ $complianceRate === null ? '—' : $complianceRate . '%' }}</p>
        <p class="text-xs text-on-surface-variant mt-xs">
            @if($complianceRate === null)
                No doses were due in {{ $year }}.
            @else
                {{ number_format($dosesGiven) }} of {{ number_format($dosesDue) }} doses due so far were given.
            @endif
        </p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">High-risk mothers</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs flex items-center gap-xs">
            @if($highRiskMothers > 0)<span class="material-symbols-outlined text-error" aria-hidden="true">warning</span>@endif
            {{ number_format($highRiskMothers) }}
        </p>
        <p class="text-xs text-on-surface-variant mt-xs">Currently marked High Risk in Records.</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter mb-lg">
    {{-- Monthly registrations --}}
    <div class="col-span-1 xl:col-span-8 bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
        <div class="flex flex-wrap items-start justify-between gap-sm mb-md">
            <div>
                <h4 class="font-headline-sm text-headline-sm">Registrations per month, {{ $year }}</h4>
                <p class="text-body-sm text-on-surface-variant">New patients by month of registration.</p>
            </div>
            <div class="flex items-center gap-md text-label-sm text-on-surface" aria-label="Legend">
                <span class="flex items-center gap-xs"><span class="series-maternal inline-block w-3 h-3 rounded-sm"></span>Maternal</span>
                <span class="flex items-center gap-xs"><span class="series-child inline-block w-3 h-3 rounded-sm"></span>Child</span>
            </div>
        </div>

        @if($registeredInYear === 0)
            <div class="h-56 flex items-center justify-center bg-surface-container-low rounded-lg text-body-sm text-on-surface-variant">
                No patients were registered in {{ $year }}.
            </div>
        @else
        <div class="flex gap-xs" role="img" aria-label="Column chart of monthly registrations in {{ $year }}; the table below lists every value.">
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
                    @foreach($monthlyRegistrations as $month => $counts)
                    @php $total = $totals[$month]; @endphp
                    <div class="group relative flex-1 h-full flex justify-center outline-none rounded-t hover:bg-surface-container-low/70 focus:bg-surface-container-low/70" tabindex="0"
                         aria-label="{{ $monthNames[$month - 1] }} {{ $year }}: {{ $counts['Maternal'] }} maternal, {{ $counts['Child'] }} child, {{ $total }} total">
                        <div class="h-full w-full max-w-6 flex flex-col justify-end gap-[2px]">
                            @if($month === $peakMonth)
                            <span class="text-[10px] font-bold text-on-surface text-center tabular-nums leading-none mb-[2px]">{{ $total }}</span>
                            @endif
                            @if($counts['Child'] > 0)
                            <div class="series-child w-full rounded-t shrink-0" style="height: {{ $counts['Child'] / $axisMax * 100 }}%"></div>
                            @endif
                            @if($counts['Maternal'] > 0)
                            <div class="series-maternal w-full shrink-0 {{ $counts['Child'] > 0 ? '' : 'rounded-t' }}" style="height: {{ $counts['Maternal'] / $axisMax * 100 }}%"></div>
                            @endif
                        </div>
                        {{-- Tooltip --}}
                        <div class="pointer-events-none absolute bottom-full mb-1 z-20 hidden group-hover:block group-focus:block bg-inverse-surface text-inverse-on-surface text-[11px] rounded-lg px-sm py-xs shadow-lg whitespace-nowrap">
                            <p class="font-bold">{{ $monthNames[$month - 1] }} {{ $year }}</p>
                            <p class="flex items-center gap-xs"><span class="series-maternal inline-block w-2 h-2 rounded-sm"></span>Maternal: {{ $counts['Maternal'] }}</p>
                            <p class="flex items-center gap-xs"><span class="series-child inline-block w-2 h-2 rounded-sm"></span>Child: {{ $counts['Child'] }}</p>
                            <p>Total: {{ $total }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="absolute inset-x-0 bottom-0 h-5 flex items-end" aria-hidden="true">
                    @foreach($monthNames as $name)
                    <span class="flex-1 text-center text-[10px] text-on-surface-variant leading-none">{{ $name }}</span>
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
                    @foreach($monthlyRegistrations as $month => $counts)
                    <tr class="border-b border-outline-variant/10">
                        <td class="py-xs">{{ $monthNames[$month - 1] }} {{ $year }}</td>
                        <td class="py-xs text-right">{{ $counts['Maternal'] }}</td>
                        <td class="py-xs text-right">{{ $counts['Child'] }}</td>
                        <td class="py-xs text-right font-bold">{{ $totals[$month] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </div>

    {{-- Prenatal care and immunization follow-up --}}
    <div class="col-span-1 xl:col-span-4 space-y-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm mb-md">Prenatal care, {{ $year }}</h4>
            <div class="space-y-sm">
                <div class="flex items-center justify-between p-sm bg-primary-container/10 rounded-lg">
                    <span class="text-label-md">Prenatal visits logged</span>
                    <span class="text-label-md font-bold text-on-surface tabular-nums">{{ number_format($prenatalVisits) }}</span>
                </div>
                <div class="flex items-center justify-between p-sm bg-error-container/10 rounded-lg">
                    <span class="text-label-md flex items-center gap-xs">
                        <span class="material-symbols-outlined text-error text-[18px]" aria-hidden="true">warning</span>
                        Visits flagged High Risk
                    </span>
                    <span class="text-label-md font-bold text-on-surface tabular-nums">{{ number_format($highRiskVisits) }}</span>
                </div>
            </div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm mb-md">Immunization follow-up</h4>
            <div class="flex items-center justify-between p-sm {{ $overdueDoses > 0 ? 'bg-error-container/10' : 'bg-tertiary-fixed-dim/10' }} rounded-lg">
                <span class="text-label-md flex items-center gap-xs">
                    <span class="material-symbols-outlined {{ $overdueDoses > 0 ? 'text-error' : 'text-tertiary' }} text-[18px]" aria-hidden="true">{{ $overdueDoses > 0 ? 'schedule' : 'check_circle' }}</span>
                    Overdue doses (all years)
                </span>
                <span class="text-label-md font-bold text-on-surface tabular-nums">{{ number_format($overdueDoses) }}</span>
            </div>
            <p class="text-xs text-on-surface-variant mt-sm">Scheduled doses whose date has passed without being marked as given.</p>
        </div>
    </div>
</div>

{{-- Child nutrition --}}
<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
    <div class="flex flex-wrap items-baseline justify-between gap-sm mb-md">
        <h4 class="font-headline-sm text-headline-sm">Child nutritional status</h4>
        <p class="text-body-sm text-on-surface-variant">Latest measurement of each child &bull; WHO Child Growth Standards</p>
    </div>
    @if($childrenMeasured === 0)
        <p class="text-body-sm text-on-surface-variant">No growth measurements recorded yet.</p>
    @else
    <table class="w-full text-body-sm tabular-nums">
        <thead>
            <tr class="text-left text-on-surface-variant border-b border-outline-variant/30">
                <th class="py-xs font-medium">Status (most severe finding)</th>
                <th class="py-xs font-medium text-right">Children</th>
                <th class="py-xs font-medium text-right">Share</th>
            </tr>
        </thead>
        <tbody>
            @foreach($nutritionSummary as $status => $count)
            <tr class="border-b border-outline-variant/10">
                <td class="py-xs">@include('partials.growth.status-badge', ['status' => $status])</td>
                <td class="py-xs text-right">{{ number_format($count) }}</td>
                <td class="py-xs text-right">{{ round($count / $childrenMeasured * 100) }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td class="py-xs">Children measured</td>
                <td class="py-xs text-right">{{ number_format($childrenMeasured) }}</td>
                <td class="py-xs text-right">100%</td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>
@endsection
