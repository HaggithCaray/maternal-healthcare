{{-- Tabs for a mother with more than one child. Expects $siblings, $patient and $route (growth or immunization). --}}
@if(isset($siblings) && $siblings->count() > 1)
<nav class="flex flex-wrap gap-sm mb-md no-print" aria-label="Choose a child">
    @foreach($siblings as $sibling)
    @php $isCurrent = $sibling->id === $patient->id; @endphp
    <a href="{{ route($route, ['id' => $sibling->id]) }}"
       class="flex items-center gap-xs px-md py-xs rounded-full border text-label-md font-label-md transition-colors {{ $isCurrent ? 'bg-primary text-on-primary border-primary' : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant hover:bg-surface-container' }}"
       @if($isCurrent) aria-current="page" @endif>
        <span class="material-symbols-outlined text-[18px]">child_care</span>
        {{ $sibling->first_name }}
        <span class="{{ $isCurrent ? 'opacity-80' : 'text-outline' }}">({{ (int) \Carbon\Carbon::parse($sibling->dob)->diffInMonths(\Carbon\Carbon::now()) }}mo)</span>
    </a>
    @endforeach
</nav>
@endif
