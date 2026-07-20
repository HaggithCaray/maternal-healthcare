@extends('layouts.app')

@section('title', 'Maternal')

@section('content')
<section class="bg-surface-container-lowest p-md rounded-xl soft-shadow flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
    <div class="flex items-center gap-md w-full md:w-auto">
        <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-primary-container flex items-center justify-center font-bold text-2xl text-primary border-4 border-surface-container shadow-sm shrink-0">
            {{ strtoupper(substr($patient->first_name, 0, 2)) }}
        </div>
        <div>
            <div class="flex items-center gap-sm">
                <h3 class="font-headline-md text-headline-md text-on-surface">{{ $patient->first_name }} {{ $patient->last_name }}</h3>
                <span class="bg-primary/10 text-primary px-3 py-1 rounded-full font-label-sm text-label-sm">{{ $patient->status }}</span>
            </div>
            <p class="text-body-md text-on-surface-variant">Patient ID: #MC-{{ $patient->created_at->format('Y') }}-{{ sprintf('%03d', $patient->id) }} &bull; Age: {{ \Carbon\Carbon::parse($patient->dob)->age }} &bull; G{{ $record->gravida ?? 1 }}P{{ $record->para ?? 0 }} (Pregnant)</p>
        </div>
    </div>
    <div class="flex gap-sm w-full md:w-auto">
        @if(auth()->user()->role === 'admin')
        <button onclick="toggleModal(true)" class="flex-grow md:flex-initial px-md py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 transition-transform active:scale-95 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">add</span>
            New Visit Log
        </button>
        @endif
        <button class="flex-1 md:flex-initial px-md py-sm border border-outline-variant text-primary rounded-lg font-label-md text-label-md transition-colors hover:bg-surface-variant whitespace-nowrap">
            Print Health Summary
        </button>
    </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-12 gap-md">
    <div class="md:col-span-8 space-y-md">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
            @php 
                $gestationWeeks = $record && $record->lmp ? min(40, max(0, \Carbon\Carbon::parse($record->lmp)->diffInWeeks(\Carbon\Carbon::now()))) : 26;
                $pct = min(100, round(($gestationWeeks / 40) * 100));
            @endphp
            <div class="flex justify-between items-center mb-md">
                <h4 class="font-headline-sm text-headline-sm text-on-surface">Pregnancy Progress</h4>
                <div class="text-right">
                    <span class="text-body-sm text-on-surface-variant">Estimated Due Date:</span>
                    <p class="font-bold text-primary">{{ $record && $record->edd ? \Carbon\Carbon::parse($record->edd)->format('F d, Y') : 'N/A' }}</p>
                </div>
            </div>
            <div class="relative pt-8 pb-4 px-2 sm:px-4">
                <div class="absolute top-1/2 left-0 w-full h-2 bg-surface-variant rounded-full -translate-y-1/2"></div>
                <div class="absolute top-1/2 left-0 h-2 bg-secondary rounded-full -translate-y-1/2 transition-all duration-1000" style="width: {{ $pct }}%;"></div>
                <div class="relative flex justify-between gap-2">
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-secondary text-on-secondary flex items-center justify-center z-10">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-secondary">Trimester 1</span>
                        <span class="text-[10px] text-on-surface-variant">Completed</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full {{ $gestationWeeks >= 13 ? 'bg-secondary text-on-secondary' : 'bg-surface-variant text-on-surface-variant' }} flex items-center justify-center z-10 border-4 border-surface-container-lowest">
                            <span class="material-symbols-outlined">child_care</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-secondary">Trimester 2</span>
                        <span class="text-[10px] text-on-surface-variant font-bold">Week {{ $gestationWeeks }} (Current)</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full {{ $gestationWeeks >= 28 ? 'bg-secondary text-on-secondary' : 'bg-surface-variant text-on-surface-variant' }} flex items-center justify-center z-10">
                            <span class="material-symbols-outlined">auto_awesome</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Trimester 3</span>
                        <span class="text-[10px] text-on-surface-variant">Starts Week 28</span>
                    </div>
                </div>
            </div>
            <div class="mt-md p-sm bg-secondary-container/20 rounded-lg flex items-center gap-md">
                <div class="flex-grow">
                    <p class="text-body-sm font-bold text-on-secondary-container">Pregnancy Gestation Status</p>
                    <p class="text-xs text-on-secondary-container opacity-80">Currently in Week {{ $gestationWeeks }}. Regular checks of weight and blood pressure are recommended to ensure safe delivery.</p>
                </div>
                <div class="w-12 h-12 bg-white/50 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-secondary">eco</span>
                </div>
            </div>
        </div>

        @php
            $latestCheckup = $checkups->sortByDesc('date')->first();
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
                <div class="flex justify-between items-start mb-sm">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Blood Pressure</p>
                        <h5 class="text-headline-md font-bold text-on-surface">{{ $latestCheckup?->bp ?? '120/80' }} <span class="text-body-sm font-normal">mmHg</span></h5>
                    </div>
                    <div class="bg-tertiary-fixed text-on-tertiary-fixed px-2 py-1 rounded text-[10px] font-bold">NORMAL</div>
                </div>
                <p class="text-xs text-on-surface-variant mt-sm">Stable blood pressure monitoring.</p>
            </div>
            <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
                <div class="flex justify-between items-start mb-sm">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Latest Weight</p>
                        <h5 class="text-headline-md font-bold text-on-surface">{{ $latestCheckup?->weight_kg ?? '60' }} <span class="text-body-sm font-normal">kg</span></h5>
                    </div>
                    <div class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded text-[10px] font-bold">ON TRACK</div>
                </div>
                <p class="text-xs text-on-surface-variant mt-sm">Continuous weight checks.</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl soft-shadow overflow-hidden">
            <div class="p-md border-b border-outline-variant/30 flex justify-between items-center">
                <h4 class="font-headline-sm text-headline-sm text-on-surface">Recent Prenatal Visits</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm">
                        <tr>
                            <th class="px-md py-sm">Date</th>
                            <th class="px-md py-sm">Week</th>
                            <th class="px-md py-sm">Vitals (BP/WT)</th>
                            <th class="px-md py-sm">Fetal Heart</th>
                            <th class="px-md py-sm">Assessed By</th>
                            <th class="px-md py-sm">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($checkups as $checkup)
                        <tr class="hover:bg-surface-variant/10 transition-colors">
                            <td class="px-md py-md text-body-sm">{{ \Carbon\Carbon::parse($checkup->date)->format('M d, Y') }}</td>
                            <td class="px-md py-md text-body-sm font-bold">{{ $checkup->age_of_gestation }}</td>
                            <td class="px-md py-md text-body-sm">{{ $checkup->bp }} | {{ $checkup->weight_kg }}kg</td>
                            <td class="px-md py-md text-body-sm">{{ $checkup->fetal_heart_rate }} bpm</td>
                            <td class="px-md py-md text-body-sm">{{ $checkup->attendant }}</td>
                            <td class="px-md py-md">
                                <span class="bg-tertiary-fixed text-on-tertiary-fixed px-2 py-1 rounded-full text-[10px] font-bold uppercase">{{ $checkup->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-md py-xl text-center text-on-surface-variant text-body-sm">
                                No prenatal checkup logs found for this patient.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="md:col-span-4 space-y-md">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
            <h4 class="font-headline-sm text-headline-sm text-on-surface mb-md">Risk Profile</h4>
            <div class="space-y-sm">
                <div class="flex items-center justify-between p-sm border border-outline-variant/30 rounded-lg">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-tertiary">check_circle</span>
                        <span class="text-body-sm text-on-surface">Preeclampsia Risk</span>
                    </div>
                    <span class="text-[10px] font-bold text-tertiary">LOW</span>
                </div>
                <div class="flex items-center justify-between p-sm border border-outline-variant/30 rounded-lg">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-error">warning</span>
                        <span class="text-body-sm text-on-surface">Gestational Diabetes</span>
                    </div>
                    <span class="text-[10px] font-bold text-error">PENDING</span>
                </div>
                <div class="flex items-center justify-between p-sm border border-outline-variant/30 rounded-lg">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-tertiary">check_circle</span>
                        <span class="text-body-sm text-on-surface">Iron Levels (Hgb)</span>
                    </div>
                    <span class="text-[10px] font-bold text-tertiary">OPTIMAL</span>
                </div>
            </div>
            <p class="mt-md text-[11px] text-on-surface-variant italic">Last assessment: Mar 12, 2024 by Midwife Elena.</p>
        </div>

        <div class="bg-surface-container-low p-md rounded-xl border border-outline-variant/30">
            <h4 class="font-label-md text-label-md text-primary uppercase mb-sm">To-Do This Week</h4>
            <ul class="space-y-sm">
                <li class="flex gap-sm">
                    <input checked class="mt-1 rounded text-primary focus:ring-primary h-4 w-4" type="checkbox">
                    <span class="text-body-sm text-on-surface-variant line-through">Second dose of Tetanus Toxoid</span>
                </li>
                <li class="flex gap-sm">
                    <input class="mt-1 rounded text-primary focus:ring-primary h-4 w-4" type="checkbox">
                    <span class="text-body-sm text-on-surface">Pick up Iron/Folic supplements</span>
                </li>
                <li class="flex gap-sm">
                    <input class="mt-1 rounded text-primary focus:ring-primary h-4 w-4" type="checkbox">
                    <span class="text-body-sm text-on-surface">Fasting for 8 hours before OGTT lab</span>
                </li>
                <li class="flex gap-sm">
                    <input class="mt-1 rounded text-primary focus:ring-primary h-4 w-4" type="checkbox">
                    <span class="text-body-sm text-on-surface">Update birth plan preferences</span>
                </li>
            </ul>
        </div>

        <div class="bg-surface-container-highest p-md rounded-xl border border-primary/20">
            <div class="flex items-center gap-sm mb-sm">
                <span class="material-symbols-outlined text-primary">local_hospital</span>
                <p class="font-bold text-on-surface">Emergency Contact</p>
            </div>
            <p class="text-body-sm text-on-surface-variant mb-md">Brgy. Health Hotline: <br><span class="font-bold text-on-surface">0917-555-0123</span></p>
            <div class="flex gap-2">
                <button class="flex-grow py-2 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-colors">Call Clinic</button>
                <button class="flex-grow py-2 bg-secondary/10 text-secondary text-xs font-bold rounded-lg hover:bg-secondary hover:text-white transition-colors">SMS Patient</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Prenatal Visit Modal -->
<div id="visitModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-surface-container-lowest rounded-xl max-w-md w-full p-lg soft-shadow border border-outline-variant/20 relative">
        <div class="flex justify-between items-center mb-md">
            <h4 class="font-headline-sm text-on-surface">Add Prenatal Visit Log</h4>
            <button onclick="toggleModal(false)" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('maternal', ['id' => $patient->id]) }}">
            @csrf
            <div class="space-y-md">
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Weight (kg) *</label>
                    <input name="weight_kg" required step="0.1" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 68.2" type="number">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Blood Pressure *</label>
                    <input name="bp" required class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 120/80" type="text">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Fetal Heart Rate (bpm) *</label>
                    <input name="fetal_heart_rate" required class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 144" type="number">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Clinical Notes</label>
                    <textarea name="notes" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="Observe for swelling, check vitamins..." rows="3"></textarea>
                </div>
            </div>
            <div class="mt-lg flex justify-end gap-sm">
                <button type="button" onclick="toggleModal(false)" class="px-md py-sm border border-outline text-on-surface-variant rounded-lg">Cancel</button>
                <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-md">Save Visit</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleModal(show) {
        document.getElementById('visitModal').classList.toggle('hidden', !show);
    }

    document.querySelectorAll('table tbody tr').forEach(row => {
        row.addEventListener('click', () => {
            console.log('Viewing detailed log for this visit...');
        });
    });
</script>
@endpush
@endsection
