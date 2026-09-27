{{-- Next visit and standard prenatal care guidance for the current trimester. Expects $gestationalDays and $latestCheckup. --}}
@php
    $trimester = \App\Services\PrenatalAssessment::trimester($gestationalDays);
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
        @if($trimester === 3 || $trimester === null)
        <li>Finish the birth plan: where to give birth, who will go with you, and transport.</li>
        @endif
    </ul>

    <div class="mt-md p-sm rounded-lg bg-error-container/20 border border-error/20">
        <p class="text-label-sm font-bold text-error flex items-center gap-xs">
            <span class="material-symbols-outlined text-[16px]">emergency</span>
            Go to the health facility right away for
        </p>
        <p class="text-xs text-on-surface-variant mt-xs">vaginal bleeding, severe headache or blurred vision, convulsions, high fever, severe belly pain, water breaking, or the baby moving less than usual.</p>
    </div>
    <p class="mt-sm text-[11px] text-on-surface-variant italic">General DOH/WHO prenatal care guidance; follow the midwife's advice for your own care.</p>
</div>
