@extends('layouts.app')

@section('title', 'Growth')

@section('content')
@include('partials.flash')
@if($errors->any())
<div class="p-md mb-lg bg-error/10 text-error rounded-xl border border-error/20">
    @foreach($errors->all() as $error)
    <p class="text-body-sm font-bold">{{ $error }}</p>
    @endforeach
</div>
@endif

<div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow flex flex-col md:flex-row items-start md:items-center justify-between gap-md">
    <div class="flex items-center gap-md w-full md:w-auto">
        <div class="w-20 h-20 rounded-xl bg-primary-container/20 flex items-center justify-center overflow-hidden border-2 border-primary/10 font-bold text-primary text-2xl">
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
    <div class="flex gap-sm w-full md:w-auto">
        @if(auth()->user()->role === 'admin')
        <button onclick="toggleModal(true)" class="grow md:flex-initial px-md py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-xs hover:opacity-90 active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Log New Metrics
        </button>
        @endif
        <button type="button" onclick="window.print()" class="no-print flex-1 md:flex-initial px-md py-sm border border-secondary text-secondary rounded-lg font-label-md text-label-md hover:bg-secondary/5 transition-all flex items-center justify-center gap-xs">
            <span class="material-symbols-outlined text-[20px]">print</span>
            Print Growth Record
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <div class="col-span-1 lg:col-span-8 space-y-gutter">
        @include('partials.growth.weight-chart')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
            @include('partials.growth.milestones')
            @include('partials.growth.nutrition')
        </div>
    </div>

    <div class="col-span-1 lg:col-span-4 space-y-gutter">
        @php
            $latestGrowth = $growthMeasurements->sortByDesc('date')->first();
            $wt = $latestGrowth?->weight_kg ?? ($childRecord->birth_weight_kg ?? 3.5);
            $ht = ($latestGrowth?->height_cm ?? ($childRecord->birth_height_cm ?? 50)) / 100;
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
                    <h5 class="text-headline-md font-headline-md text-primary">{{ $latestGrowth?->height_cm ?? ($childRecord->birth_height_cm ?? 50) }} cm</h5>
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

        @include('partials.growth.reminders')
    </div>
</div>

<!-- Log New Metrics Modal -->
<div id="metricsModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-surface-container-lowest rounded-xl max-w-md w-full p-lg soft-shadow border border-outline-variant/20 relative">
        <div class="flex justify-between items-center mb-md">
            <h4 class="font-headline-sm text-on-surface">Log Growth Metrics</h4>
            <button onclick="toggleModal(false)" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('growth', ['id' => $patient->id]) }}">
            @csrf
            <div class="space-y-md">
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Weight (kg) *</label>
                    <input name="weight_kg" required step="0.1" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 11.5" type="number">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Height (cm) *</label>
                    <input name="height_cm" required step="0.1" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 82.4" type="number">
                </div>
            </div>
            <div class="mt-lg flex justify-end gap-sm">
                <button type="button" onclick="toggleModal(false)" class="px-md py-sm border border-outline text-on-surface-variant rounded-lg">Cancel</button>
                <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-md">Save Metrics</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleModal(show) {
        document.getElementById('metricsModal').classList.toggle('hidden', !show);
    }
</script>
@endpush
@endsection
