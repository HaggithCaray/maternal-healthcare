@extends('layouts.app')

@section('title', 'Maternal')

@section('content')
@include('partials.flash')
@if($errors->any())
<div class="p-md mb-lg bg-error/10 text-error rounded-xl border border-error/20">
    @foreach($errors->all() as $error)
    <p class="text-body-sm font-bold">{{ $error }}</p>
    @endforeach
</div>
@endif

<section class="bg-surface-container-lowest p-md rounded-xl soft-shadow flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
    <div class="flex items-center gap-md w-full md:w-auto">
        <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-primary-container flex items-center justify-center font-bold text-2xl text-primary border-4 border-surface-container shadow-xs shrink-0">
            {{ strtoupper(substr($patient->first_name, 0, 2)) }}
        </div>
        <div>
            <div class="flex items-center gap-sm">
                <h3 class="font-headline-md text-headline-md text-on-surface">{{ $patient->first_name }} {{ $patient->last_name }}</h3>
                <span class="bg-primary/10 text-primary px-3 py-1 rounded-full font-label-sm text-label-sm">{{ $patient->status }}</span>
            </div>
            <p class="text-body-md text-on-surface-variant">Patient ID: #MC-{{ $patient->created_at->format('Y') }}-{{ sprintf('%03d', $patient->id) }} &bull; Age: {{ \Carbon\Carbon::parse($patient->dob)->age }} &bull; G{{ $record->gravida ?? 1 }}P{{ $record->para ?? 0 }} (Pregnant)</p>
            @include('partials.allergies', ['allergies' => $record?->allergies])
        </div>
    </div>
    <div class="flex gap-sm w-full md:w-auto flex-wrap">
        @if(auth()->user()->role === 'admin' && $maternalPatients->count())
        <div class="relative" id="mother-switcher">
            <button type="button" onclick="toggleMotherSwitcher()" class="w-full md:w-auto px-md py-sm border border-outline-variant rounded-lg font-label-md text-label-md text-primary flex items-center justify-center gap-2 hover:bg-surface-variant transition-colors whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                Switch Mother
                <span class="material-symbols-outlined text-[18px] transition-transform" id="mother-switcher-chevron">expand_more</span>
            </button>
            <div id="mother-switcher-menu" class="hidden absolute right-0 top-full mt-2 w-72 bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/20 z-50 p-sm">
                <input type="text" id="mother-switcher-search" oninput="filterMothers(this.value)" placeholder="Search mothers..." class="w-full mb-sm px-md py-sm bg-surface-container border border-outline-variant/50 rounded-lg focus:border-primary focus:ring-1 focus:ring-primary text-sm outline-hidden">
                <ul id="mother-switcher-list" class="max-h-64 overflow-y-auto">
                    @foreach($maternalPatients as $m)
                    <li>
                        <a href="{{ route('maternal', ['id' => $m->id]) }}" data-name="{{ strtolower($m->first_name . ' ' . $m->last_name) }}" class="mother-option block px-md py-sm rounded-lg text-sm {{ $m->id === $patient->id ? 'bg-primary-container/30 text-primary font-bold' : 'text-on-surface hover:bg-surface-variant/50' }} transition-colors">
                            {{ $m->first_name }} {{ $m->last_name }}
                            @if($m->id === $patient->id)
                            <span class="material-symbols-outlined text-[16px] align-middle">check</span>
                            @endif
                        </a>
                    </li>
                    @endforeach
                </ul>
                <p id="mother-switcher-empty" class="hidden px-md py-sm text-sm text-on-surface-variant text-center">No mothers found.</p>
            </div>
        </div>
        @endif
        @if(auth()->user()->role === 'admin')
        <button onclick="toggleModal(true)" class="grow md:flex-initial px-md py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center justify-center gap-2 transition-transform active:scale-95 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">add</span>
            New Visit Log
        </button>
        @endif
        <button type="button" onclick="window.print()" class="no-print flex-1 md:flex-initial px-md py-sm border border-outline-variant text-primary rounded-lg font-label-md text-label-md transition-colors hover:bg-surface-variant whitespace-nowrap flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">print</span>
            Print Health Summary
        </button>
    </div>
</section>

<div class="grid grid-cols-1 md:grid-cols-12 gap-md">
    <div class="md:col-span-8 space-y-md">
        @include('partials.maternal.pregnancy-progress')

        @include('partials.maternal.vitals')

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
                            <td class="px-md py-md text-body-sm font-bold">{{ $checkup->age_of_gestation ?? '—' }}</td>
                            <td class="px-md py-md text-body-sm">{{ $checkup->bp }} | {{ $checkup->weight_kg }}kg</td>
                            <td class="px-md py-md text-body-sm">{{ $checkup->fetal_heart_rate ? $checkup->fetal_heart_rate . ' bpm' : '—' }}</td>
                            <td class="px-md py-md text-body-sm">{{ $checkup->attendant }}</td>
                            <td class="px-md py-md">
                                @include('partials.maternal.status-badge', ['status' => $checkup->status])
                                @foreach($checkup->risk_flags ?? [] as $flag)
                                <p class="text-[11px] text-on-surface-variant mt-1 max-w-56">{{ $flag['message'] }}</p>
                                @endforeach
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

        @include('partials.maternal.postpartum')
    </div>

    <div class="md:col-span-4 space-y-md">
        @include('partials.maternal.risk-profile')

        @include('partials.maternal.care-reminders')

        @include('partials.maternal.contact-card')
    </div>
</div>

<!-- Add Prenatal Visit Modal -->
<div id="visitModal" data-modal class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-surface-container-lowest rounded-xl max-w-md w-full p-lg soft-shadow border border-outline-variant/20 relative">
        <div class="flex justify-between items-center mb-md">
            <h4 class="font-headline-sm text-on-surface">Add Prenatal Visit Log</h4>
            <button onclick="toggleModal(false)" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('maternal', ['id' => $patient->id]) }}"
            @if($record) data-offline-type="maternal_checkup" data-offline-label="Prenatal visit" data-offline-context='@json(['maternal_record_id' => $record->id])' @endif>
            @csrf
            <div class="space-y-md">
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Weight (kg) *</label>
                    <input name="weight_kg" required step="0.1" min="25" max="250" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 68.2" type="number">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Blood Pressure *</label>
                    <input name="bp" required pattern="\s*\d{2,3}\s*/\s*\d{2,3}\s*" title="Systolic/diastolic, e.g. 120/80" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 120/80" type="text">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Fetal Heart Rate (bpm)</label>
                    <input name="fetal_heart_rate" min="50" max="250" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 144" type="number">
                    <p class="text-xs text-on-surface-variant">Leave blank if not yet audible (usually before 12 weeks).</p>
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Clinical Notes</label>
                    <textarea name="notes" maxlength="5000" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="Observe for swelling, check vitamins..." rows="3"></textarea>
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

    function togglePostpartumModal(show) {
        document.getElementById('postpartumModal')?.classList.toggle('hidden', !show);
    }

    function toggleMotherSwitcher() {
        const menu = document.getElementById('mother-switcher-menu');
        const chevron = document.getElementById('mother-switcher-chevron');
        const search = document.getElementById('mother-switcher-search');
        const opening = menu.classList.contains('hidden');
        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180', opening);
        if (opening) {
            search.value = '';
            filterMothers('');
            search.focus();
        }
    }

    function filterMothers(term) {
        const q = term.toLowerCase().trim();
        const options = document.querySelectorAll('.mother-option');
        let visible = 0;
        options.forEach(opt => {
            const match = opt.dataset.name.includes(q);
            opt.parentElement.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        document.getElementById('mother-switcher-empty').classList.toggle('hidden', visible > 0);
    }

    // Close the switcher when clicking anywhere outside it.
    document.addEventListener('click', function (e) {
        const switcher = document.getElementById('mother-switcher');
        if (switcher && !switcher.contains(e.target)) {
            document.getElementById('mother-switcher-menu')?.classList.add('hidden');
            document.getElementById('mother-switcher-chevron')?.classList.remove('rotate-180');
        }
    });

    document.querySelectorAll('table tbody tr').forEach(row => {
        row.addEventListener('click', () => {
            console.log('Viewing detailed log for this visit...');
        });
    });
</script>
@endpush
@endsection
