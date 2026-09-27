@php
    $badgeClasses = match ($status) {
        \App\Services\PrenatalAssessment::HIGH_RISK => 'bg-error-container text-on-error-container',
        \App\Services\PrenatalAssessment::MONITOR => 'bg-amber-100 text-amber-800',
        default => 'bg-tertiary-fixed text-on-tertiary-fixed',
    };
@endphp
<span class="{{ $badgeClasses }} px-2 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">{{ $status }}</span>
