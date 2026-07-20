@extends('layouts.app')

@section('title', 'Growth')

@section('content')
<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow flex flex-col md:flex-row items-start md:items-center justify-between gap-md">
    <div class="flex items-center gap-md w-full md:w-auto">
        <div class="w-20 h-20 rounded-xl bg-primary-container/20 flex items-center justify-center overflow-hidden border-2 border-primary/10 font-bold text-primary text-xl">
            {{ strtoupper(substr($patient->first_name, 0, 2)) }}
        </div>
        <div>
            <div class="flex items-center gap-sm">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $patient->first_name }} {{ $patient->last_name }}</h3>
                <span class="px-sm py-xs bg-tertiary-fixed text-on-tertiary-fixed rounded-full text-label-sm font-label-sm">Normal Growth</span>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Age: {{ \Carbon\Carbon::parse($patient->dob)->diffInMonths(\Carbon\Carbon::now()) }} Months &bull; {{ $patient->gender }} &bull; Patient ID: #BC-{{ $patient->created_at->format('Y') }}-{{ sprintf('%03d', $patient->id) }}</p>
        </div>
    </div>
    <a href="{{ route('patient.portal') }}" class="flex-1 md:flex-initial px-md py-sm border border-primary text-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-xs hover:bg-primary/5 transition-all">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        Back to Portal
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <div class="col-span-1 lg:col-span-8 space-y-gutter">
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
            <div class="flex items-center justify-between mb-lg">
                <div>
                    <h4 class="font-headline-sm text-headline-sm">Growth Velocity</h4>
                    <p class="text-body-sm text-on-surface-variant">Weight and Height trajectory vs. WHO standard</p>
                </div>
                <div class="flex bg-surface-container rounded-lg p-xs">
                    <button class="px-md py-xs bg-surface-container-lowest rounded-md text-label-sm font-label-sm shadow-sm">Weight</button>
                    <button class="px-md py-xs text-on-surface-variant text-label-sm font-label-sm">Height</button>
                    <button class="px-md py-xs text-on-surface-variant text-label-sm font-label-sm">BMI</button>
                </div>
            </div>
            <div class="relative h-64 w-full bg-surface-container-low rounded-lg overflow-hidden flex items-end px-md pb-md">
                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#00478d 0.5px, transparent 0.5px); background-size: 24px 24px;"></div>
                <div class="flex items-end justify-between w-full h-4/5 gap-xs md:gap-sm z-10">
                    @php $bars = [40, 45, 52, 60, 68, 75, 82]; @endphp
                    @foreach($bars as $i => $h)
                    <div class="w-full @if($i === 6) bg-primary/30 border-t-2 border-primary @else bg-primary/20 @endif rounded-t-sm relative group cursor-pointer" style="height: {{ $h }}%">
                        @if($i === 0)
                        <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-inverse-surface text-inverse-on-surface text-[10px] px-xs py-1 rounded hidden group-hover:block">8.2kg</div>
                        @elseif($i === 6)
                        <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-inverse-surface text-inverse-on-surface text-[10px] px-xs py-1 rounded">11.5kg</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-between mt-sm text-label-sm text-on-surface-variant px-md">
                <span>6m</span><span>8m</span><span>10m</span><span>12m</span><span>14m</span><span>16m</span><span>Today</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
                <h4 class="font-headline-sm text-headline-sm mb-md flex items-center gap-sm">
                    <span class="material-symbols-outlined text-secondary">verified</span>
                    Dev. Milestones
                </h4>
                <div class="space-y-sm">
                    <div class="flex items-center gap-sm p-sm bg-secondary-container/10 rounded-lg">
                        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        <div>
                            <p class="text-label-md font-label-md">Walking independently</p>
                            <p class="text-label-sm text-on-surface-variant">Achieved at 14 months</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-sm p-sm bg-secondary-container/10 rounded-lg">
                        <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                        <div>
                            <p class="text-label-md font-label-md">Speaking 5-10 words</p>
                            <p class="text-label-sm text-on-surface-variant">Achieved at 17 months</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-sm p-sm border border-outline-variant border-dashed rounded-lg opacity-60">
                        <span class="material-symbols-outlined text-outline">pending</span>
                        <div>
                            <p class="text-label-md font-label-md">Points to body parts</p>
                            <p class="text-label-sm text-on-surface-variant">Expected next milestone</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
                <h4 class="font-headline-sm text-headline-sm mb-md flex items-center gap-sm">
                    <span class="material-symbols-outlined text-tertiary">restaurant</span>
                    Nutritional Status
                </h4>
                <div class="p-md rounded-xl bg-tertiary-fixed-dim/20 border-l-4 border-tertiary text-center">
                    <p class="text-headline-md font-headline-md text-tertiary">WELL-NOURISHED</p>
                    <p class="text-body-sm text-on-surface-variant mt-xs">Weight-for-age: Percentile 65%</p>
                </div>
                <div class="mt-md space-y-xs">
                    <div class="flex justify-between text-label-sm">
                        <span>Daily Protein Intake</span>
                        <span class="text-tertiary font-bold">Optimal</span>
                    </div>
                    <div class="w-full bg-surface-container rounded-full h-2">
                        <div class="bg-tertiary h-full rounded-full" style="width: 85%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-1 lg:col-span-4 space-y-gutter">
        @php
            $latestG = $growthMeasurements->sortByDesc('date')->first();
            $wt = $latestG?->weight_kg ?? $childRecord->birth_weight_kg ?? 3.0;
            $ht = ($latestG?->height_cm ?? $childRecord->birth_height_cm ?? 50.0) / 100.0;
            $bmi = $ht > 0 ? round($wt / ($ht * $ht), 1) : 0;
        @endphp
        <div class="space-y-sm">
            <div class="bg-primary text-on-primary rounded-xl p-md soft-drop-shadow flex items-center justify-between">
                <div>
                    <p class="text-label-sm font-label-sm opacity-80">Current Weight</p>
                    <h5 class="text-headline-md font-headline-md">{{ $wt }} kg</h5>
                </div>
                <div class="w-12 h-12 bg-on-primary/10 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">weight</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow flex items-center justify-between border border-primary/5">
                <div>
                    <p class="text-label-sm font-label-sm text-on-surface-variant">Height</p>
                    <h5 class="text-headline-md font-headline-md text-primary">{{ $latestG?->height_cm ?? $childRecord->birth_height_cm ?? 50.0 }} cm</h5>
                </div>
                <div class="w-12 h-12 bg-primary-container/20 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">straighten</span>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow flex items-center justify-between border border-primary/5">
                <div>
                    <p class="text-label-sm font-label-sm text-on-surface-variant">BMI Score</p>
                    <h5 class="text-headline-md font-headline-md text-secondary">{{ $bmi }}</h5>
                </div>
                <div class="w-12 h-12 bg-secondary-container/20 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">health_metrics</span>
                </div>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow">
            <h4 class="font-label-md text-label-md uppercase tracking-wider text-on-surface-variant mb-md">Reminders</h4>
            <div class="space-y-sm">
                <div class="p-sm bg-error-container/20 border border-error/10 rounded-lg flex gap-sm">
                    <span class="material-symbols-outlined text-error">priority_high</span>
                    <div>
                        <p class="text-label-md font-label-md text-on-error-container">Immunization Overdue</p>
                        <p class="text-label-sm text-on-error-container/70">MMR 2nd Dose - Oct 12, 2023</p>
                    </div>
                </div>
                <div class="p-sm bg-primary-container/10 border border-primary/10 rounded-lg flex gap-sm">
                    <span class="material-symbols-outlined text-primary">calendar_today</span>
                    <div>
                        <p class="text-label-md font-label-md text-on-primary-fixed-variant">Next Checkup Due</p>
                        <p class="text-label-sm text-on-primary-fixed-variant/70">Scheduled for Jan 15, 2024</p>
                    </div>
                </div>
            </div>
            <button class="w-full mt-lg py-sm text-primary font-label-md text-label-md hover:underline">Contact My Midwife</button>
        </div>

        <div class="rounded-xl overflow-hidden relative h-40 md:h-48 soft-drop-shadow bg-gradient-to-br from-tertiary/10 to-tertiary/20 flex flex-col justify-between p-md border border-tertiary/10">
            <span class="material-symbols-outlined text-tertiary text-[48px]" style="font-variation-settings: 'FILL' 1;">restaurant</span>
            <div>
                <p class="text-tertiary font-bold text-label-md">Professional Nutrition Advice</p>
                <p class="text-xs text-on-surface-variant mt-xs">Curated advice for toddler developmental milestones.</p>
            </div>
        </div>
    </div>
</div>
@endsection
