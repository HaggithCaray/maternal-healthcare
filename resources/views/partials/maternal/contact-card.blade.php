{{-- Contact options: staff reach the patient; the patient reaches the care team. Expects $patient. --}}
@php $clinicPhone = config('app.clinic_phone'); @endphp
<div class="bg-surface-container-highest p-md rounded-xl border border-primary/20">
    @if(auth()->user()->isAdmin())
    <div class="flex items-center gap-sm mb-sm">
        <span class="material-symbols-outlined text-primary">contact_phone</span>
        <p class="font-bold text-on-surface">Contact Patient</p>
    </div>
    <dl class="text-body-sm text-on-surface-variant space-y-xs mb-md">
        <div>
            <dt class="inline">Phone:</dt>
            <dd class="inline font-bold text-on-surface"><a href="tel:{{ preg_replace('/[^\d+]/', '', $patient->phone) }}" class="hover:underline">{{ $patient->phone }}</a></dd>
        </div>
        <div>
            <dt class="inline">Emergency contact:</dt>
            <dd class="inline font-bold text-on-surface">
                {{ $patient->emergency_contact_name }}
                @if($patient->emergency_contact_phone)
                &middot; <a href="tel:{{ preg_replace('/[^\d+]/', '', $patient->emergency_contact_phone) }}" class="hover:underline">{{ $patient->emergency_contact_phone }}</a>
                @endif
            </dd>
        </div>
    </dl>
    <div class="flex gap-2">
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $patient->phone) }}" class="grow text-center py-2 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-colors">Call</a>
        <a href="{{ route('sms', ['patient_id' => $patient->id]) }}" class="grow text-center py-2 bg-secondary/10 text-secondary text-xs font-bold rounded-lg hover:bg-secondary hover:text-white transition-colors">Send SMS</a>
        @if($patient->user_id)
        <a href="{{ route('messaging', ['chat_user_id' => $patient->user_id]) }}" class="grow text-center py-2 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-colors">Message</a>
        @endif
    </div>
    @else
    <div class="flex items-center gap-sm mb-sm">
        <span class="material-symbols-outlined text-primary">local_hospital</span>
        <p class="font-bold text-on-surface">Need Help?</p>
    </div>
    <p class="text-body-sm text-on-surface-variant mb-md">
        @if($clinicPhone)
            Barangay Bicao Health Station: <br><span class="font-bold text-on-surface">{{ $clinicPhone }}</span>
        @else
            Message your midwife here any time. In an emergency, go to the nearest health facility.
        @endif
    </p>
    <div class="flex gap-2">
        @if($clinicPhone)
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $clinicPhone) }}" class="grow text-center py-2 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary hover:text-white transition-colors">Call Health Station</a>
        @endif
        <a href="{{ route('messaging') }}" class="grow text-center py-2 bg-secondary/10 text-secondary text-xs font-bold rounded-lg hover:bg-secondary hover:text-white transition-colors">Message Midwife</a>
    </div>
    @endif
</div>
