@extends('layouts.app')

@section('title', 'Maternal')

@section('content')
<section class="bg-surface-container-lowest p-md rounded-xl soft-shadow flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
    <div class="flex items-center gap-md w-full md:w-auto">
        <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-primary-container flex items-center justify-center font-bold text-2xl text-primary shadow-sm shrink-0 border-2 border-primary-container">
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
    <a href="{{ route('patient.portal') }}" class="flex-1 md:flex-initial px-md py-sm border border-primary text-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 hover:bg-primary/5 transition-colors whitespace-nowrap">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Portal
    </a>
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
                <div class="relative flex justify-between gap-1 md:gap-2">
                    <div class="flex flex-col items-center gap-1 md:gap-2">
                        <div class="w-10 h-10 rounded-full bg-secondary text-on-secondary flex items-center justify-center z-10">
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-secondary">Trimester 1</span>
                        <span class="text-[10px] text-on-surface-variant">Completed</span>
                    </div>
                    <div class="flex flex-col items-center gap-1 md:gap-2">
                        <div class="w-10 h-10 rounded-full bg-secondary text-on-secondary flex items-center justify-center z-10 border-4 border-surface-container-lowest">
                            <span class="material-symbols-outlined">child_care</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-secondary">Trimester 2</span>
                        <span class="text-[10px] text-on-surface-variant font-bold">Week 26 (Current)</span>
                    </div>
                    <div class="flex flex-col items-center gap-1 md:gap-2">
                        <div class="w-10 h-10 rounded-full bg-surface-variant text-on-surface-variant flex items-center justify-center z-10">
                            <span class="material-symbols-outlined">auto_awesome</span>
                        </div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Trimester 3</span>
                        <span class="text-[10px] text-on-surface-variant">Starts Week 28</span>
                    </div>
                </div>
            </div>
            <div class="mt-md p-sm bg-secondary-container/20 rounded-lg flex items-center gap-md">
                <div class="flex-grow">
                    <p class="text-body-sm font-bold text-on-secondary-container">Baby's Current Size: Head of Cauliflower</p>
                    <p class="text-xs text-on-secondary-container opacity-80">The baby is about 35cm long and weighs roughly 760g. Lungs are beginning to develop.</p>
                </div>
                <div class="w-12 h-12 bg-white/50 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-secondary">eco</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
                <div class="flex justify-between items-start mb-sm">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Blood Pressure</p>
                        <h5 class="text-headline-md font-bold text-on-surface">118/76 <span class="text-body-sm font-normal">mmHg</span></h5>
                    </div>
                    <div class="bg-tertiary-fixed text-on-tertiary-fixed px-2 py-1 rounded text-[10px] font-bold">NORMAL</div>
                </div>
                <div class="h-24 w-full flex items-end gap-1 px-1">
                    <div class="bg-outline-variant w-full h-[60%] rounded-t-sm opacity-50"></div>
                    <div class="bg-outline-variant w-full h-[65%] rounded-t-sm opacity-50"></div>
                    <div class="bg-outline-variant w-full h-[55%] rounded-t-sm opacity-50"></div>
                    <div class="bg-primary w-full h-[70%] rounded-t-sm"></div>
                </div>
                <p class="text-xs text-on-surface-variant mt-sm">Stable across the last 4 visits.</p>
            </div>
            <div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
                <div class="flex justify-between items-start mb-sm">
                    <div>
                        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider">Weight Gain</p>
                        <h5 class="text-headline-md font-bold text-on-surface">+8.4 <span class="text-body-sm font-normal">kg</span></h5>
                    </div>
                    <div class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded text-[10px] font-bold">ON TRACK</div>
                </div>
                <div class="h-24 w-full flex items-end gap-1 px-1">
                    <div class="bg-outline-variant w-full h-[20%] rounded-t-sm opacity-50"></div>
                    <div class="bg-outline-variant w-full h-[35%] rounded-t-sm opacity-50"></div>
                    <div class="bg-outline-variant w-full h-[50%] rounded-t-sm opacity-50"></div>
                    <div class="bg-secondary w-full h-[65%] rounded-t-sm"></div>
                </div>
                <p class="text-xs text-on-surface-variant mt-sm">Ideal range: 7kg - 11kg for Week 26.</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest rounded-xl soft-shadow overflow-hidden">
            <div class="p-md border-b border-outline-variant/30 flex justify-between items-center">
                <h4 class="font-headline-sm text-headline-sm text-on-surface">Recent Prenatal Visits</h4>
                <button class="text-primary font-label-md text-label-md hover:underline transition-all">View All History</button>
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
                        @forelse($checkups as $c)
                        <tr class="hover:bg-surface-variant/10 transition-colors">
                            <td class="px-md py-md text-body-sm">{{ \Carbon\Carbon::parse($c->date)->format('M d, Y') }}</td>
                            <td class="px-md py-md text-body-sm font-bold">{{ $c->age_of_gestation }}</td>
                            <td class="px-md py-md text-body-sm">{{ $c->bp }} | {{ $c->weight_kg }}kg</td>
                            <td class="px-md py-md text-body-sm">{{ $c->fetal_heart_rate }} bpm</td>
                            <td class="px-md py-md text-body-sm">{{ $c->attendant }}</td>
                            <td class="px-md py-md">
                                <span class="bg-tertiary-fixed text-on-tertiary-fixed px-2 py-1 rounded-full text-[10px] font-bold uppercase">{{ $c->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-md py-12 text-center text-on-surface-variant text-body-sm">
                                No checkup logs recorded.
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
                <button class="flex-grow py-2 bg-secondary/10 text-secondary text-xs font-bold rounded-lg hover:bg-secondary hover:text-white transition-colors">Message Midwife</button>
            </div>
        </div>
    </div>
</div>
@endsection
