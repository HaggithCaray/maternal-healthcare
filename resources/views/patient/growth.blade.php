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
                @include('partials.growth.status-badge', ['status' => $latestGrowth?->status])
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Age: {{ (int) \Carbon\Carbon::parse($patient->dob)->diffInMonths(\Carbon\Carbon::now()) }} Months &bull; {{ $patient->gender }} &bull; Patient ID: #BC-{{ $patient->created_at->format('Y') }}-{{ sprintf('%03d', $patient->id) }}</p>
        </div>
    </div>
    <a href="{{ route('patient.portal') }}" class="flex-1 md:flex-initial px-md py-sm border border-primary text-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-xs hover:bg-primary/5 transition-all">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        Back to Portal
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <div class="col-span-1 lg:col-span-8 space-y-gutter">
        @include('partials.growth.weight-chart')

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
            @include('partials.growth.nutrition')
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
