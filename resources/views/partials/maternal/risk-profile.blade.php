{{-- Risk factors from the patient's profile and latest visit. Expects $riskFactors and $latestCheckup. --}}
<div class="bg-surface-container-lowest p-md rounded-xl soft-shadow">
    <h4 class="font-headline-sm text-headline-sm text-on-surface mb-md">Risk Profile</h4>
    <div class="space-y-sm">
        @forelse($riskFactors as $factor)
        @php $isHigh = $factor['level'] === \App\Services\PrenatalAssessment::HIGH_RISK; @endphp
        <div class="flex items-start justify-between gap-sm p-sm border {{ $isHigh ? 'border-error/30 bg-error-container/10' : 'border-amber-500/30 bg-amber-50' }} rounded-lg">
            <div class="flex items-start gap-sm">
                <span class="material-symbols-outlined {{ $isHigh ? 'text-error' : 'text-amber-600' }}">{{ $isHigh ? 'warning' : 'info' }}</span>
                <span class="text-body-sm text-on-surface">{{ $factor['message'] }}</span>
            </div>
            <span class="text-[10px] font-bold uppercase whitespace-nowrap {{ $isHigh ? 'text-error' : 'text-amber-700' }}">{{ $factor['level'] }}</span>
        </div>
        @empty
        <div class="flex items-center gap-sm p-sm border border-outline-variant/30 rounded-lg">
            <span class="material-symbols-outlined text-tertiary">check_circle</span>
            <span class="text-body-sm text-on-surface">No risk factors identified.</span>
        </div>
        @endforelse
    </div>
    <p class="mt-md text-[11px] text-on-surface-variant italic">
        @if($latestCheckup)
            Based on the profile and the visit on {{ \Carbon\Carbon::parse($latestCheckup->date)->format('M d, Y') }}{{ $latestCheckup->attendant ? ' by ' . $latestCheckup->attendant : '' }}.
        @else
            Based on the patient profile. No prenatal visit recorded yet.
        @endif
        Screening aid only — clinical judgment decides referral.
    </p>
</div>
