@php
    $status = $status ?? \App\Services\WhoGrowthStandards::NOT_ASSESSED;
    $badgeClasses = match (true) {
        str_starts_with($status, 'Severely') => 'bg-error text-on-error',
        in_array($status, ['Wasted', 'Underweight', 'Stunted'], true) => 'bg-error-container text-on-error-container',
        in_array($status, ['Overweight', 'Obese', \App\Services\WhoGrowthStandards::RECHECK], true) => 'bg-amber-100 text-amber-800',
        $status === \App\Services\WhoGrowthStandards::NORMAL => 'bg-tertiary-fixed text-on-tertiary-fixed',
        default => 'bg-surface-variant text-on-surface-variant',
    };
@endphp
<span class="px-sm py-xs {{ $badgeClasses }} rounded-full text-label-sm font-label-sm whitespace-nowrap">{{ $status === \App\Services\WhoGrowthStandards::NORMAL ? 'Normal Growth' : $status }}</span>
