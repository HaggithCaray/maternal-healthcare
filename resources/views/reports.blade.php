@extends('layouts.app')

@section('title', 'Reports')

@section('content')
@php
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chartMonths = [];
    foreach ($monthlyRegistrations as $month => $counts) {
        $chartMonths[] = ['label' => $monthNames[$month - 1] . ' ' . $year, 'short' => $monthNames[$month - 1]] + $counts;
    }
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
        <button type="button" onclick="window.print()" class="bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all shadow-xs whitespace-nowrap">
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
        <div class="mb-md">
            <h4 class="font-headline-sm text-headline-sm">Registrations per month, {{ $year }}</h4>
            <p class="text-body-sm text-on-surface-variant">New patients by month of registration.</p>
        </div>
        @include('partials.registration-chart', ['emptyMessage' => "No patients were registered in {$year}."])
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
