{{-- Pregnancy progress from the recorded LMP. Expects $record and $gestationalDays (null when LMP is unknown). --}}
@php
    $gestationWeeks = $gestationalDays !== null ? intdiv($gestationalDays, 7) : null;
    $trimester = \App\Services\PrenatalAssessment::trimester($gestationalDays);
    $pct = $gestationalDays !== null ? min(100, round(($gestationalDays / 280) * 100)) : 0;
    $stepClass = fn (bool $reached) => $reached ? 'bg-secondary text-on-secondary' : 'bg-surface-variant text-on-surface-variant';
@endphp
<div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
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
            @foreach([1 => ['Weeks 1–13', 'pregnant_woman'], 2 => ['Weeks 14–27', 'child_care'], 3 => ['Week 28 to birth', 'auto_awesome']] as $number => [$range, $icon])
            <div class="flex flex-col items-center gap-1 md:gap-2">
                <div class="w-10 h-10 rounded-full {{ $stepClass($trimester !== null && $trimester >= $number) }} flex items-center justify-center z-10 border-4 border-surface-container-lowest">
                    <span class="material-symbols-outlined" @if($trimester !== null && $trimester > $number) style="font-variation-settings: 'FILL' 1;" @endif>{{ $trimester !== null && $trimester > $number ? 'check' : $icon }}</span>
                </div>
                <span class="font-label-sm text-label-sm {{ $trimester === $number ? 'text-secondary' : 'text-on-surface-variant' }}">Trimester {{ $number }}</span>
                <span class="text-[10px] text-on-surface-variant {{ $trimester === $number ? 'font-bold' : '' }}">
                    {{ $trimester === $number ? "Week {$gestationWeeks} (Current)" : $range }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="mt-md p-sm bg-secondary-container/20 rounded-lg flex items-center gap-md">
        <div class="grow">
            @if($gestationalDays === null)
            <p class="text-body-sm font-bold text-on-secondary-container">Gestational age unknown</p>
            <p class="text-xs text-on-secondary-container opacity-80">No last menstrual period (LMP) is on file. Record the LMP on the patient's profile to track weeks and the due date.</p>
            @else
            <p class="text-body-sm font-bold text-on-secondary-container">{{ \App\Services\PrenatalAssessment::formatGestationalAge($gestationalDays) }} pregnant &bull; Trimester {{ $trimester }}</p>
            <p class="text-xs text-on-secondary-container opacity-80">
                @if($gestationalDays >= 42 * 7)
                    Past 42 weeks — this pregnancy is post-term and should be referred for delivery.
                @elseif($gestationalDays >= 37 * 7)
                    Full term. Keep the birth plan ready and watch for signs of labor.
                @else
                    Counted from the LMP. Regular blood pressure and weight checks are recommended at every visit.
                @endif
            </p>
            @endif
        </div>
        <div class="w-12 h-12 bg-white/50 rounded-full flex items-center justify-center">
            <span class="material-symbols-outlined text-secondary">eco</span>
        </div>
    </div>
</div>
