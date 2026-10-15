{{-- Postnatal care: the routine 48 h / 1 w / 2 w / 6 w visits and the visits logged so far.
     Expects $record, $postpartumVisits, $postpartumSchedule and $patient. --}}
@php
    $delivered = $record && $record->delivery_date;
    $daysPostpartum = $delivered ? \App\Services\PostpartumAssessment::daysPostpartum($record->delivery_date, \Carbon\Carbon::today()) : null;
    $isAdmin = auth()->user()->role === 'admin';

    $stateClasses = [
        'Done' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'Due' => 'bg-primary/10 text-primary',
        'Overdue' => 'bg-error-container text-on-error-container',
        'Upcoming' => 'bg-surface-variant text-on-surface-variant',
    ];
@endphp
<div class="bg-surface-container-lowest rounded-xl soft-shadow overflow-hidden">
    <div class="p-md border-b border-outline-variant/30 flex justify-between items-center gap-md flex-wrap">
        <div>
            <h4 class="font-headline-sm text-headline-sm text-on-surface">Postpartum Care</h4>
            @if($delivered)
            <p class="text-xs text-on-surface-variant mt-1">
                Delivered {{ \Carbon\Carbon::parse($record->delivery_date)->format('M d, Y') }} &bull; {{ $daysPostpartum }} {{ \Illuminate\Support\Str::plural('day', $daysPostpartum) }} postpartum
            </p>
            @endif
        </div>
        @if($isAdmin && $delivered)
        <button type="button" onclick="togglePostpartumModal(true)" class="no-print px-md py-sm bg-primary text-on-primary rounded-lg font-label-md text-label-md flex items-center gap-2 transition-transform active:scale-95 whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Log Postpartum Visit
        </button>
        @endif
    </div>

    @unless($delivered)
    <div class="p-md text-body-sm text-on-surface-variant">
        <p>No delivery date is on file for this mother, so postpartum tracking has not started.</p>
        @if($isAdmin)
        <p class="mt-2">Record the delivery date on the <a href="{{ route('patients.edit', ['patient' => $patient->id]) }}#maternal" class="text-primary font-bold underline">patient edit form</a> to begin tracking the 48-hour, 1-week, 2-week and 6-week postnatal visits.</p>
        @endif
    </div>
    @else
    <div class="p-md border-b border-outline-variant/20">
        <p class="text-label-sm text-on-surface-variant uppercase tracking-wider mb-sm">Routine Postnatal Visits</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-sm">
            @foreach($postpartumSchedule as $slot)
            <div class="rounded-lg border border-outline-variant/30 p-sm text-center">
                <p class="font-bold text-body-sm text-on-surface">{{ $slot['label'] }}</p>
                @if($slot['dueDate'])
                <p class="text-[11px] text-on-surface-variant">due {{ \Carbon\Carbon::parse($slot['dueDate'])->format('M d') }}</p>
                @endif
                <span class="{{ $stateClasses[$slot['state']] ?? '' }} mt-1 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">{{ $slot['state'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm">
                <tr>
                    <th class="px-md py-sm">Date</th>
                    <th class="px-md py-sm">Day Postpartum</th>
                    <th class="px-md py-sm">Vitals (BP/WT)</th>
                    <th class="px-md py-sm">Assessed By</th>
                    <th class="px-md py-sm">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse($postpartumVisits as $visit)
                <tr class="hover:bg-surface-variant/10 transition-colors">
                    <td class="px-md py-md text-body-sm">{{ \Carbon\Carbon::parse($visit->date)->format('M d, Y') }}</td>
                    <td class="px-md py-md text-body-sm font-bold">Day {{ $visit->days_postpartum ?? '—' }}</td>
                    <td class="px-md py-md text-body-sm">{{ $visit->bp ?? '—' }} | {{ $visit->weight_kg ?? '—' }}kg</td>
                    <td class="px-md py-md text-body-sm">{{ $visit->attendant ?? '—' }}</td>
                    <td class="px-md py-md">
                        @include('partials.maternal.status-badge', ['status' => $visit->status])
                        @foreach($visit->risk_flags ?? [] as $flag)
                        <p class="text-[11px] text-on-surface-variant mt-1 max-w-56">{{ $flag['message'] }}</p>
                        @endforeach
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-md py-xl text-center text-on-surface-variant text-body-sm">
                        No postpartum visits logged yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endunless
</div>

@if($isAdmin && $delivered)
<!-- Add Postpartum Visit Modal -->
<div id="postpartumModal" data-modal class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-surface-container-lowest rounded-xl max-w-md w-full p-lg soft-shadow border border-outline-variant/20 relative">
        <div class="flex justify-between items-center mb-md">
            <h4 class="font-headline-sm text-on-surface">Add Postpartum Visit Log</h4>
            <button type="button" onclick="togglePostpartumModal(false)" class="text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('maternal', ['id' => $patient->id]) }}"
            data-offline-type="postpartum_visit" data-offline-label="Postpartum visit" data-offline-context='@json(['maternal_record_id' => $record->id])'>
            @csrf
            <input type="hidden" name="visit_type" value="postpartum">
            <div class="space-y-md">
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Weight (kg) *</label>
                    <input name="weight_kg" required step="0.1" min="25" max="250" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 64.5" type="number">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Blood Pressure *</label>
                    <input name="bp" required pattern="\s*\d{2,3}\s*/\s*\d{2,3}\s*" title="Systolic/diastolic, e.g. 120/80" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="e.g. 120/80" type="text">
                </div>
                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">Clinical Notes</label>
                    <textarea name="notes" maxlength="5000" class="w-full bg-surface-container border border-outline-variant rounded-lg px-md py-sm focus:border-primary" placeholder="Uterine contraction, bleeding, breastfeeding, mood..." rows="3"></textarea>
                </div>
            </div>
            <div class="mt-lg flex justify-end gap-sm">
                <button type="button" onclick="togglePostpartumModal(false)" class="px-md py-sm border border-outline text-on-surface-variant rounded-lg">Cancel</button>
                <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded-lg font-label-md">Save Visit</button>
            </div>
        </form>
    </div>
</div>
@endif
