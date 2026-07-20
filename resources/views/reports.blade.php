@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Reports &amp; Analytics</h3>
        <p class="text-on-surface-variant font-body-md">Generate and export reports on maternal and child health data.</p>
    </div>
    <button class="bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all shadow-sm whitespace-nowrap">
        <span class="material-symbols-outlined">download</span>
        Export Report
    </button>
</div>

<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 mb-lg">
    <div class="flex items-center gap-lg flex-wrap">
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-on-surface-variant">calendar_month</span>
            <div>
                <p class="text-label-sm text-on-surface-variant">From</p>
                <p class="text-label-md font-label-md">Oct 1, 2023</p>
            </div>
        </div>
        <span class="material-symbols-outlined text-outline">arrow_forward</span>
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-on-surface-variant">calendar_month</span>
            <div>
                <p class="text-label-sm text-on-surface-variant">To</p>
                <p class="text-label-md font-label-md">Oct 31, 2023</p>
            </div>
        </div>
        <div class="flex items-center gap-sm border-l border-outline-variant/20 pl-lg">
            <span class="material-symbols-outlined text-on-surface-variant">category</span>
            <select class="bg-surface-container border-none rounded-lg px-md py-xs text-label-md font-label-md text-on-surface focus:ring-1 focus:ring-primary outline-none">
                <option>All Reports</option>
                <option>Monthly Summary</option>
                <option>Immunization</option>
                <option>Maternal Health</option>
                <option>Growth Monitoring</option>
            </select>
        </div>
        <button class="ml-auto bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center gap-xs hover:opacity-90 active:scale-95 transition-all">
            <span class="material-symbols-outlined">refresh</span>
            Generate
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-md mb-lg">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-start justify-between mb-sm">
            <span class="material-symbols-outlined text-primary text-3xl">assessment</span>
            <span class="px-sm py-xs bg-primary-container/20 text-primary rounded-full text-label-sm font-label-sm">Updated</span>
        </div>
        <p class="font-label-md text-on-surface">Monthly Summary</p>
        <p class="text-xs text-on-surface-variant mt-xs">Comprehensive overview of all patient activities, visits, and outcomes for the selected period.</p>
        <div class="flex justify-between items-center mt-md pt-sm border-t border-outline-variant/10">
            <span class="text-label-sm text-on-surface-variant">Last: Oct 1, 2023</span>
            <button class="text-primary text-label-sm font-label-sm flex items-center gap-xs hover:underline">
                <span class="material-symbols-outlined text-[16px]">download</span>
                Download
            </button>
        </div>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-start justify-between mb-sm">
            <span class="material-symbols-outlined text-secondary text-3xl">immunology</span>
            <span class="px-sm py-xs bg-secondary-container/20 text-secondary rounded-full text-label-sm font-label-sm">Quarterly</span>
        </div>
        <p class="font-label-md text-on-surface">Immunization Coverage</p>
        <p class="text-xs text-on-surface-variant mt-xs">Vaccination rates by barangay, coverage gaps, and wastage analysis for the current quarter.</p>
        <div class="flex justify-between items-center mt-md pt-sm border-t border-outline-variant/10">
            <span class="text-label-sm text-on-surface-variant">Last: Q3 2023</span>
            <button class="text-primary text-label-sm font-label-sm flex items-center gap-xs hover:underline">
                <span class="material-symbols-outlined text-[16px]">download</span>
                Download
            </button>
        </div>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-start justify-between mb-sm">
            <span class="material-symbols-outlined text-tertiary text-3xl">demography</span>
            <span class="px-sm py-xs bg-tertiary-fixed-dim/20 text-tertiary rounded-full text-label-sm font-label-sm">Annual</span>
        </div>
        <p class="font-label-md text-on-surface">Demographic Data</p>
        <p class="text-xs text-on-surface-variant mt-xs">Population breakdown by age group, gender, and barangay with year-over-year comparison.</p>
        <div class="flex justify-between items-center mt-md pt-sm border-t border-outline-variant/10">
            <span class="text-label-sm text-on-surface-variant">Last: 2023</span>
            <button class="text-primary text-label-sm font-label-sm flex items-center gap-xs hover:underline">
                <span class="material-symbols-outlined text-[16px]">download</span>
                Download
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter mb-lg">
    <div class="col-span-1 xl:col-span-7 bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
        <div class="flex items-center justify-between mb-md">
            <h4 class="font-headline-sm text-headline-sm">Report Generation Trends</h4>
            <div class="flex bg-surface-container rounded-lg p-xs">
                <button class="px-md py-xs bg-surface-container-lowest rounded-md text-label-sm font-label-sm shadow-sm">Weekly</button>
                <button class="px-md py-xs text-on-surface-variant text-label-sm font-label-sm">Monthly</button>
                <button class="px-md py-xs text-on-surface-variant text-label-sm font-label-sm">Yearly</button>
            </div>
        </div>
        <div class="relative h-56 w-full bg-surface-container-low rounded-lg overflow-hidden flex items-end px-md pb-md">
            <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#00478d 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
            <div class="flex items-end justify-between w-full h-4/5 gap-xs z-10">
                @php $weeks = [65, 80, 55, 92, 70, 45, 85]; $labels = ['Week 39', 'Week 40', 'Week 41', 'Week 42', 'Week 43', 'Week 44', 'Week 45']; @endphp
                @foreach($weeks as $i => $val)
                <div class="w-full relative group cursor-pointer">
                    <div class="bg-primary rounded-t-sm hover:bg-primary/80 transition-colors" style="height: {{ $val }}%"></div>
                    <div class="absolute -top-7 left-1/2 -translate-x-1/2 bg-inverse-surface text-inverse-on-surface text-[10px] px-xs py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">{{ $val }} reports</div>
                    <p class="text-[9px] text-on-surface-variant text-center mt-1">{{ $labels[$i] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-span-1 xl:col-span-5 bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
        <h4 class="font-headline-sm text-headline-sm mb-md">Quick Stats</h4>
        <div class="space-y-sm">
            <div class="flex items-center justify-between p-sm bg-primary-container/10 rounded-lg">
                <span class="text-label-md">Reports Generated (Oct)</span>
                <span class="text-label-md font-bold text-primary">248</span>
            </div>
            <div class="flex items-center justify-between p-sm bg-secondary-container/10 rounded-lg">
                <span class="text-label-md">Avg. Generation Time</span>
                <span class="text-label-md font-bold text-secondary">2.4s</span>
            </div>
            <div class="flex items-center justify-between p-sm bg-tertiary-fixed-dim/10 rounded-lg">
                <span class="text-label-md">Scheduled Reports</span>
                <span class="text-label-md font-bold text-tertiary">12</span>
            </div>
            <div class="flex items-center justify-between p-sm bg-error-container/10 rounded-lg">
                <span class="text-label-md">Failed Last 7 Days</span>
                <span class="text-label-md font-bold text-error">3</span>
            </div>
            <div class="flex items-center justify-between p-sm bg-surface-container rounded-lg">
                <span class="text-label-md">Storage Used</span>
                <span class="text-label-md font-bold">2.4 GB</span>
            </div>
        </div>
    </div>
</div>

<div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10">
    <div class="flex items-center justify-between p-md border-b border-outline-variant/10">
        <h4 class="font-headline-sm text-headline-sm">Recent Reports</h4>
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-on-surface-variant cursor-pointer hover:text-primary transition-colors">grid_view</span>
            <span class="material-symbols-outlined text-primary cursor-pointer">view_list</span>
        </div>
    </div>
    <div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="text-left text-label-sm text-on-surface-variant border-b border-outline-variant/10">
                <th class="p-md font-medium">Report Name</th>
                <th class="p-md font-medium">Type</th>
                <th class="p-md font-medium">Generated By</th>
                <th class="p-md font-medium">Date</th>
                <th class="p-md font-medium">Size</th>
                <th class="p-md font-medium">Status</th>
                <th class="p-md font-medium"></th>
            </tr>
        </thead>
        <tbody>
            @php
                $reports = [
                    ['Monthly Summary - Oct 2023', 'Summary', 'Dr. Maria Santos', 'Oct 31, 2023', '2.4 MB', 'completed', 'primary'],
                    ['Immunization Coverage Q3', 'Immunization', 'Nurse Elena', 'Oct 28, 2023', '1.8 MB', 'completed', 'secondary'],
                    ['Demographic Report 2023', 'Demographic', 'Admin', 'Oct 25, 2023', '4.2 MB', 'completed', 'tertiary'],
                    ['Maternal Health Outcomes', 'Maternal', 'Dr. Cruz', 'Oct 22, 2023', '3.1 MB', 'completed', 'primary'],
                    ['Growth Monitoring Batch', 'Growth', 'Nurse Reyes', 'Oct 20, 2023', '1.2 MB', 'failed', 'error'],
                    ['Vaccine Wastage Analysis', 'Immunization', 'Admin', 'Oct 18, 2023', '0.8 MB', 'completed', 'secondary'],
                    ['Barangay Coverage Report', 'Summary', 'Dr. Maria Santos', 'Oct 15, 2023', '5.6 MB', 'completed', 'primary'],
                ];
            @endphp
            @foreach($reports as $report)
            <tr class="border-b border-outline-variant/5 hover:bg-surface-container-high/20 transition-colors">
                <td class="p-md">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-{{ $report[5] === 'failed' ? 'error' : $report[6] }}">description</span>
                        <span class="text-label-md font-label-md">{{ $report[0] }}</span>
                    </div>
                </td>
                <td class="p-md">
                    <span class="px-sm py-xs bg-{{ $report[6] }}-container/20 text-{{ $report[6] }} rounded-full text-label-sm font-label-sm">
                        {{ $report[1] }}
                    </span>
                </td>
                <td class="p-md text-label-md text-on-surface-variant">{{ $report[2] }}</td>
                <td class="p-md text-label-md text-on-surface-variant">{{ $report[3] }}</td>
                <td class="p-md text-label-md text-on-surface-variant">{{ $report[4] }}</td>
                <td class="p-md">
                    @if($report[5] === 'completed')
                    <span class="flex items-center gap-xs text-tertiary text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-tertiary rounded-full"></span>
                        Completed
                    </span>
                    @else
                    <span class="flex items-center gap-xs text-error text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-error rounded-full"></span>
                        Failed
                    </span>
                    @endif
                </td>
                <td class="p-md">
                    <button class="text-primary text-label-sm font-label-sm flex items-center gap-xs hover:underline">
                        <span class="material-symbols-outlined text-[16px]">download</span>
                        Download
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="flex items-center justify-between p-md border-t border-outline-variant/10">
        <span class="text-label-sm text-on-surface-variant">Showing 7 of 42 reports</span>
        <div class="flex gap-sm">
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">Previous</button>
            <button class="px-sm py-xs bg-primary text-on-primary rounded-lg text-label-sm">1</button>
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">2</button>
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">3</button>
            <button class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">Next</button>
        </div>
    </div>
</div>
@endsection
