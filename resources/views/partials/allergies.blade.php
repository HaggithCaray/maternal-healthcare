@if(filled($allergies ?? null))
<p class="mt-xs flex items-start gap-1 text-body-sm font-bold text-error" title="Recorded allergies">
    <span class="material-symbols-outlined text-[18px]">warning</span>
    <span>Allergies: {{ $allergies }}</span>
</p>
@endif
