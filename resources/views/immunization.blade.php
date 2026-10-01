@extends('layouts.app')

@section('title', 'Immunization')

@push('styles')
<style>
    .timeline-line::before {
        content: '';
        position: absolute;
        left: 19px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: repeating-linear-gradient(to bottom, #d4e5f5 0%, #d4e5f5 50%, transparent 50%, transparent 100%);
        background-size: 2px 8px;
    }
</style>
@endpush

@section('content')
@include('partials.flash')
@if($errors->any())
<div class="p-md mb-lg bg-error/10 text-error rounded-xl border border-error/20">
    @foreach($errors->all() as $error)
    <p class="text-body-sm font-bold">{{ $error }}</p>
    @endforeach
</div>
@endif

<section class="bg-surface-container-lowest p-md rounded-2xl soft-shadow flex flex-col md:flex-row gap-md items-start">
    <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-primary-container flex items-center justify-center font-bold text-3xl text-primary border border-primary-container shrink-0">
        {{ strtoupper(substr($patient->first_name, 0, 2)) }}
    </div>
    <div class="flex-1 space-y-2">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary">{{ $patient->first_name }} {{ $patient->last_name }}</h3>
                <p class="text-outline font-label-md">ID: BCHC-{{ $patient->created_at->format('Y') }}-{{ sprintf('%03d', $patient->id) }} &bull; {{ (int) \Carbon\Carbon::parse($patient->dob)->diffInMonths(\Carbon\Carbon::now()) }} Months Old</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                @php
                    $totalDoses = $immunizations->count();
                    $givenDoses = $immunizations->where('status', 'Given')->count();
                    $pct = $totalDoses > 0 ? round(($givenDoses / $totalDoses) * 100) : 0;
                    $latestGrowth = $childRecord?->growthMeasurements?->sortByDesc('date')?->first();
                @endphp
                @if($pct === 100)
                <span class="px-3 py-1 bg-tertiary-fixed text-on-tertiary-fixed rounded-full text-xs font-bold uppercase tracking-wide">Fully Protected</span>
                @else
                <span class="px-3 py-1 bg-secondary-fixed text-on-secondary-fixed rounded-full text-xs font-bold uppercase tracking-wide">Partially Protected</span>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 pt-4 border-t border-outline-variant/20">
            <div>
                <p class="text-[10px] text-outline uppercase font-bold tracking-widest">Weight</p>
                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $latestGrowth?->weight_kg ?? ($childRecord->birth_weight_kg ?? '3.0') }} kg</p>
            </div>
            <div>
                <p class="text-[10px] text-outline uppercase font-bold tracking-widest">Height</p>
                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $latestGrowth?->height_cm ?? ($childRecord->birth_height_cm ?? '50') }} cm</p>
            </div>
            <div>
                <p class="text-[10px] text-outline uppercase font-bold tracking-widest">Gender</p>
                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $patient->gender }}</p>
            </div>
            <div>
                <p class="text-[10px] text-outline uppercase font-bold tracking-widest">Completed</p>
                <div class="flex items-center gap-2">
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ $pct }}%</p>
                    <div class="w-full bg-surface-variant h-1.5 rounded-full overflow-hidden">
                        <div class="bg-tertiary h-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-8 space-y-6">
        <div class="bg-surface-container-lowest rounded-2xl soft-shadow p-md">
            <div class="flex justify-between items-center mb-6">
                <h4 class="font-headline-sm text-headline-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">timeline</span>
                    Visual Vaccine Timeline
                </h4>
            </div>
            <div class="relative py-4 flex justify-between px-2 mb-10 overflow-x-auto hide-scrollbar min-w-0">
                <div class="absolute top-1/2 left-0 w-full h-[2px] bg-surface-variant -translate-y-1/2 z-0"></div>
                <div class="absolute top-1/2 left-0 h-[2px] bg-tertiary -translate-y-1/2 z-0 transition-all duration-500" style="width: {{ $pct }}%;"></div>
                @php
                    $hasBirth = $immunizations->where('vaccine_name', 'BCG')->where('status', 'Given')->count() > 0;
                    $has6w = $immunizations->where('vaccine_name', 'Pentavalent (DPT-HepB-Hib)')->where('dose_number', 1)->where('status', 'Given')->count() > 0;
                    $has10w = $immunizations->where('vaccine_name', 'Pentavalent (DPT-HepB-Hib)')->where('dose_number', 2)->where('status', 'Given')->count() > 0;
                    $has14w = $immunizations->where('vaccine_name', 'Pentavalent (DPT-HepB-Hib)')->where('dose_number', 3)->where('status', 'Given')->count() > 0;
                    $has9m = $immunizations->where('vaccine_name', 'MMR')->where('dose_number', 1)->where('status', 'Given')->count() > 0;
                    $has12m = $immunizations->where('vaccine_name', 'MMR')->where('dose_number', 2)->where('status', 'Given')->count() > 0;

                    $milestones = [
                        ['label' => 'Birth', 'done' => $hasBirth],
                        ['label' => '6 Wks', 'done' => $has6w],
                        ['label' => '10 Wks', 'done' => $has10w],
                        ['label' => '14 Wks', 'done' => $has14w],
                        ['label' => '9 Mos', 'done' => $has9m],
                        ['label' => '12 Mos', 'done' => $has12m],
                    ];
                @endphp
                @foreach($milestones as $ms)
                <div class="relative z-10 flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs ring-4 ring-surface-container-lowest shadow-xs
                        @if($ms['done']) bg-tertiary text-white
                        @else bg-surface-variant text-outline @endif">
                        @if($ms['done'])
                            <span class="material-symbols-outlined text-sm">check</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold @if(!empty($ms['current'])) text-primary @else text-outline @endif uppercase">{{ $ms['label'] }}</span>
                </div>
                @endforeach
            </div>

            <div class="mt-8 border border-outline-variant/30 rounded-xl">
                <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-6 py-4 font-label-md text-label-md text-primary">Vaccine Name</th>
                            <th class="px-6 py-4 font-label-md text-label-md text-primary">Dose</th>
                            <th class="px-6 py-4 font-label-md text-label-md text-primary">Date Given</th>
                            <th class="px-6 py-4 font-label-md text-label-md text-primary">Provider</th>
                            <th class="px-6 py-4 font-label-md text-label-md text-primary">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($immunizations as $v)
                        <tr class="hover:bg-surface-bright transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ $v->vaccine_name }}</p>
                                <p class="text-xs text-outline">Target Date: {{ \Carbon\Carbon::parse($v->scheduled_date)->format('M d, Y') }}</p>
                            </td>
                            <td class="px-6 py-4 text-body-sm">Dose {{ $v->dose_number }}</td>
                            <td class="px-6 py-4 text-body-sm">{{ $v->given_date ? \Carbon\Carbon::parse($v->given_date)->format('M d, Y') : 'Pending' }}</td>
                            <td class="px-6 py-4 text-body-sm">{{ $v->administered_by ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                @if($v->status === 'Given')
                                <span class="px-2 py-1 bg-tertiary-fixed text-on-tertiary-fixed text-[10px] font-bold rounded-md uppercase">GIVEN</span>
                                @else
                                    @if(auth()->user()->role === 'admin')
                                    <form method="POST" action="{{ route('immunization', ['id' => $patient->id]) }}" class="inline"
                                        data-offline-type="immunization_update" data-offline-label="{{ $v->vaccine_name }} dose {{ $v->dose_number }}" data-offline-once>
                                        @csrf
                                        <input type="hidden" name="immunization_id" value="{{ $v->id }}">
                                        <button type="submit" class="px-2 py-1 bg-primary text-on-primary text-[10px] font-bold rounded-md uppercase hover:bg-primary/95 transition-all">Mark Given</button>
                                    </form>
                                    @else
                                    <span class="px-2 py-1 bg-error-container text-error text-[10px] font-bold rounded-md uppercase">SCHEDULED</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant text-body-sm">
                                No vaccine records scheduled.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-secondary-container/10 border border-secondary-container rounded-2xl p-md relative overflow-hidden">
                <div class="relative z-10">
                    <h5 class="font-headline-sm text-headline-sm text-on-secondary-container mb-2">Vaccine Tip</h5>
                    <p class="text-sm text-on-secondary-container/80 leading-relaxed">@php $nextDose = $immunizations->where('status', 'Scheduled')->sortBy('scheduled_date')->first(); @endphp @if($nextDose)After {{ $patient->first_name }}'s next vaccine ({{ $nextDose->vaccine_name }}, {{ \Carbon\Carbon::parse($nextDose->scheduled_date)->format('M d') }}), keep the child comfortable and give plenty of fluids or breast milk. Mild fever and soreness are common and pass in a day or two.@else All scheduled vaccines are complete. Keep the immunization card for school and future check-ups.@endif</p>
                </div>
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-8xl opacity-10 text-secondary">lightbulb</span>
            </div>
            @php $lastGiven = $immunizations->where('status', 'Given')->sortByDesc('given_date')->first(); @endphp
            <div class="bg-surface-container p-md rounded-2xl border border-outline-variant/30">
                <h5 class="font-headline-sm text-headline-sm text-primary mb-1">Last Vaccine Given</h5>
                @if($lastGiven)
                <p class="text-xs text-outline">{{ $lastGiven->vaccine_name }} (dose {{ $lastGiven->dose_number }}) on {{ \Carbon\Carbon::parse($lastGiven->given_date)->format('M d, Y') }}{{ $lastGiven->administered_by ? ' by ' . $lastGiven->administered_by : '' }}.</p>
                @if($lastGiven->remarks)<p class="text-xs text-outline mt-1">Remarks: {{ $lastGiven->remarks }}</p>@endif
                @else
                <p class="text-xs text-outline">No doses recorded yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="lg:col-span-4 space-y-8">
        <div class="bg-surface-container-lowest rounded-2xl soft-shadow p-md h-full flex flex-col">
            <h4 class="font-headline-sm text-headline-sm mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">schedule</span>
                Status Check
            </h4>
            <div class="space-y-6 flex-1">
                @php
                    $overdue = $immunizations->where('status', 'Scheduled')->where('scheduled_date', '<', \Carbon\Carbon::now());
                    $upcoming = $immunizations->where('status', 'Scheduled')->where('scheduled_date', '>=', \Carbon\Carbon::now())->sortBy('scheduled_date')->take(2);
                @endphp
                @if($overdue->count() > 0)
                <div>
                    <h5 class="text-[10px] font-bold text-error uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        Overdue Vaccines ({{ $overdue->count() }})
                    </h5>
                    @foreach($overdue as $ov)
                    <div class="bg-error-container/30 border border-error-container p-4 rounded-xl flex gap-4 items-start mb-2">
                        <div class="bg-error-container w-10 h-10 rounded-lg flex items-center justify-center text-error shrink-0">
                            <span class="material-symbols-outlined">report</span>
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-on-error-container text-sm">{{ $ov->vaccine_name }} (Dose {{ $ov->dose_number }})</p>
                            <p class="text-xs text-on-error-container/70 mb-2">Target: {{ \Carbon\Carbon::parse($ov->scheduled_date)->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="timeline-line relative pl-10 space-y-6">
                    <h5 class="text-[10px] font-bold text-primary uppercase tracking-widest mb-3 ml-[-40px]">Upcoming Schedule</h5>
                    @forelse($upcoming as $up)
                    <div class="relative">
                        <div class="absolute -left-[31px] top-1 w-5 h-5 bg-primary rounded-full ring-4 ring-surface-container-lowest z-10"></div>
                        <div class="bg-surface-container-low border border-outline-variant/20 p-4 rounded-xl">
                            <p class="font-bold text-primary text-sm">{{ $up->vaccine_name }}</p>
                            <p class="text-xs text-outline">Dose {{ $up->dose_number }} · Scheduled: {{ \Carbon\Carbon::parse($up->scheduled_date)->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="text-xs text-on-surface-variant">No upcoming vaccines scheduled.</div>
                    @endforelse
                </div>
            </div>
            <div class="mt-auto pt-6 border-t border-outline-variant/30">
                <div class="flex items-center justify-between mb-4">
                    <p class="font-label-md text-label-md">Next Clinic Day</p>
                    <p class="font-bold text-primary">Every Thursday</p>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center">
                    <div class="text-[10px] text-outline">S</div>
                    <div class="text-[10px] text-outline">M</div>
                    <div class="text-[10px] text-outline">T</div>
                    <div class="text-[10px] text-outline">W</div>
                    <div class="text-[10px] text-outline">T</div>
                    <div class="text-[10px] text-outline">F</div>
                    <div class="text-[10px] text-outline">S</div>
                    <div class="text-[10px] py-1">10</div>
                    <div class="text-[10px] py-1">11</div>
                    <div class="text-[10px] py-1">12</div>
                    <div class="text-[10px] py-1">13</div>
                    <div class="text-[10px] py-1 bg-primary text-on-primary rounded-md font-bold">14</div>
                    <div class="text-[10px] py-1">15</div>
                    <div class="text-[10px] py-1">16</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="flex flex-col sm:flex-row items-center justify-between gap-md bg-inverse-surface p-md rounded-2xl text-white">
    <div class="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-tertiary-fixed">verified_user</span>
            <p class="text-sm">Official WHO/DOH Immunization Schedule Applied</p>
        </div>
        <div class="hidden sm:block h-4 w-px bg-white/20"></div>
        @php $lastUpdate = $immunizations->max('updated_at'); @endphp
        <p class="text-sm text-white/60">Last updated: {{ $lastUpdate ? \Carbon\Carbon::parse($lastUpdate)->format('M d, Y g:i A') : '—' }}</p>
    </div>
    <button type="button" onclick="window.print()" class="no-print px-4 sm:px-6 py-2 bg-secondary text-white rounded-xl text-sm font-bold shadow-lg hover:brightness-110 active:scale-95 transition-all whitespace-nowrap flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">print</span>
        Print Record
    </button>
</div>
@endsection
