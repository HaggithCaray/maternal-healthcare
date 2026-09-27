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
        @include('partials.maternal.pregnancy-progress')

        @include('partials.maternal.vitals')

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
                            <td class="px-md py-md text-body-sm font-bold">{{ $c->age_of_gestation ?? '—' }}</td>
                            <td class="px-md py-md text-body-sm">{{ $c->bp }} | {{ $c->weight_kg }}kg</td>
                            <td class="px-md py-md text-body-sm">{{ $c->fetal_heart_rate ? $c->fetal_heart_rate . ' bpm' : '—' }}</td>
                            <td class="px-md py-md text-body-sm">{{ $c->attendant }}</td>
                            <td class="px-md py-md">
                                @include('partials.maternal.status-badge', ['status' => $c->status])
                                @foreach($c->risk_flags ?? [] as $flag)
                                <p class="text-[11px] text-on-surface-variant mt-1 max-w-56">{{ $flag['message'] }}</p>
                                @endforeach
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
        @include('partials.maternal.risk-profile')

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
