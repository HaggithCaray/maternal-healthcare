{{-- Next visit, birth plan and standard prenatal care guidance. Expects $record, $gestationalDays and $latestCheckup. --}}
@php
    $nextVisit = $latestCheckup?->next_visit_date;
    $weeks = $gestationalDays !== null ? intdiv($gestationalDays, 7) : null;
@endphp
<div class="bg-surface-container-low p-md rounded-xl border border-outline-variant/30">
    <h4 class="font-label-md text-label-md text-primary uppercase mb-sm">Care Reminders</h4>

    <div class="flex items-center gap-sm p-sm mb-sm rounded-lg {{ $nextVisit && $nextVisit->lt(\Carbon\Carbon::today()) ? 'bg-error-container/20' : 'bg-primary-container/10' }}">
        <span class="material-symbols-outlined text-primary">event</span>
        <div>
            <p class="text-label-sm text-on-surface-variant">Next prenatal visit</p>
            <p class="font-bold text-on-surface text-body-sm">
                @if($nextVisit)
                    {{ $nextVisit->format('D, M d, Y') }}@if($nextVisit->lt(\Carbon\Carbon::today())) <span class="text-error">(missed)</span>@endif
                @else
                    Not scheduled yet
                @endif
            </p>
        </div>
    </div>

    <ul class="space-y-xs text-body-sm text-on-surface list-disc pl-md">
        <li>Take the iron–folic acid supplement every day.</li>
        <li>Get the tetanus-diphtheria (Td) doses the midwife advises.</li>
        @if($weeks !== null && $weeks < 28)
        <li>Screening for gestational diabetes is usually done at 24–28 weeks{{ $weeks >= 20 ? ' — ask at your next visit' : '' }}.</li>
        @endif
    </ul>

    @php $birthPlan = $record?->birth_plan ?? []; @endphp
    <div class="mt-md p-sm rounded-lg bg-surface-container-lowest border border-outline-variant/30">
        <p class="text-label-sm font-bold text-on-surface flex items-center gap-xs">
            <span class="material-symbols-outlined text-[16px] text-primary">home_health</span>
            Birth plan
        </p>
        @if(! empty($birthPlan['facility']) || ! empty($birthPlan['attendant']))
        <p class="text-xs text-on-surface-variant mt-xs">
            {{ $birthPlan['facility'] ?? 'Place not decided' }}@if(! empty($birthPlan['attendant'])) &middot; with {{ $birthPlan['attendant'] }}@endif
        </p>
        @else
        <p class="text-xs text-on-surface-variant mt-xs">
            Not set yet.
            {{ auth()->user()->isAdmin() ? 'Record the planned place of delivery and attendant on Edit Patient.' : 'Decide with your midwife where to give birth, who will attend, and how you will get there.' }}
        </p>
        @endif
    </div>

    <div class="mt-md p-sm rounded-lg bg-error-container/20 border border-error/20">
        <p class="text-label-sm font-bold text-error flex items-center gap-xs">
            <span class="material-symbols-outlined text-[16px]">emergency</span>
            Go to the health facility right away for
        </p>
        <p class="text-xs text-on-surface-variant mt-xs">vaginal bleeding, severe headache or blurred vision, convulsions, high fever, severe belly pain, water breaking, or the baby moving less than usual.</p>
    </div>
    <p class="mt-sm text-[11px] text-on-surface-variant italic">General DOH/WHO prenatal care guidance; follow the midwife's advice for your own care.</p>
</div>
