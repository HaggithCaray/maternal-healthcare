@extends('layouts.app')

@section('title', 'SMS')

@section('content')
<div class="grid grid-cols-12 gap-gutter">
    @if(session('success'))
    <div class="col-span-12 p-md bg-emerald-500/10 text-emerald-600 rounded-xl border border-emerald-500/20 flex items-center gap-xs">
        <span class="material-symbols-outlined text-emerald-500">check_circle</span>
        <span class="text-body-sm font-bold">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="col-span-12 p-md bg-error/10 text-error rounded-xl border border-error/20 flex items-center gap-xs">
        <span class="material-symbols-outlined text-error">error</span>
        <span class="text-body-sm font-bold">{{ session('error') }}</span>
    </div>
    @endif

    @foreach([
        ['icon' => 'send', 'tone' => 'primary', 'value' => $stats['sent'], 'label' => 'Messages sent', 'note' => number_format($stats['sentThisMonth']) . ' this month'],
        ['icon' => 'group', 'tone' => 'secondary', 'value' => $stats['reachableMothers'] + $stats['reachableChildren'], 'label' => 'Patients with a phone number', 'note' => $stats['reachableMothers'] . ' mothers · ' . $stats['reachableChildren'] . ' children'],
        ['icon' => 'error', 'tone' => 'error', 'value' => $stats['failed'], 'label' => 'Failed deliveries', 'note' => $stats['failed'] > 0 ? 'Check the gateway, then resend' : 'None'],
    ] as $card)
    <div class="col-span-12 md:col-span-4 bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/20">
        <div class="flex items-center justify-between mb-sm">
            <span class="p-2 rounded-lg material-symbols-outlined {{ ['primary' => 'bg-primary/10 text-primary', 'secondary' => 'bg-secondary/10 text-secondary', 'error' => 'bg-error/10 text-error'][$card['tone']] }}">{{ $card['icon'] }}</span>
        </div>
        <h3 class="font-headline-sm text-headline-sm font-bold text-on-surface">{{ number_format($card['value']) }}</h3>
        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $card['label'] }}</p>
        <p class="text-[11px] text-outline mt-xs">{{ $card['note'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-12 gap-gutter items-start">
    <div class="col-span-12 lg:col-span-8 space-y-gutter">
        <form method="POST" action="{{ route('sms') }}" class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/20">
            @csrf
            <div class="flex items-center justify-between mb-md">
                <h4 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-xs">
                    <span class="material-symbols-outlined text-primary">edit_square</span>
                    New Notification
                </h4>
                <div class="flex gap-xs">
                    <button type="submit" class="px-md py-1.5 rounded-full bg-primary text-on-primary font-label-md text-label-md hover:opacity-90 transition-all flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        Send Now
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="space-y-xs">
                    <label class="font-label-md text-label-md text-on-surface">Recipient Patient *</label>
                    <select name="patient_id" id="smsPatient" required class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-sm py-2 px-3 bg-surface-container-lowest">
                        <option value="">Select Patient...</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}" data-first-name="{{ $p->first_name }}" @selected(old('patient_id', request('patient_id')) == $p->id)>{{ $p->last_name }}, {{ $p->first_name }} ({{ $p->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-xs">
                    <label class="font-label-md text-label-md text-on-surface">Select Template</label>
                    <select id="smsTemplate" onchange="applyTemplate()" class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-sm py-2 px-3 bg-surface-container-lowest">
                        <option value="">Custom Message</option>
                        <option value="Hi {name}, this is Bicao Health Station reminding you of your prenatal check-up. Please bring your Mother and Child Book.">Prenatal Visit Reminder</option>
                        <option value="Hi, this is Bicao Health Station. {name} is due for the next vaccine dose. Please visit the health station and bring the yellow immunization card.">Vaccine Dose Reminder</option>
                        <option value="Hi {name}, please visit Bicao Health Station for a follow-up. Message us here if you have questions.">Follow-up Request</option>
                    </select>
                </div>
                <div class="md:col-span-2 space-y-xs">
                    <label class="font-label-md text-label-md text-on-surface">Message Content *</label>
                    <textarea name="message" id="smsContent" required class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-sm py-2 px-3 bg-surface-container-lowest" placeholder="Type your message here..." rows="3"></textarea>
                    <div class="flex justify-between items-center text-[11px] text-outline">
                        <span id="charCounter" class="font-bold">0 characters / 0 segment</span>
                    </div>
                </div>
            </div>
        </form>

        <section class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/20 overflow-hidden">
            <div class="px-md py-sm bg-surface-container-low border-b border-outline-variant/20 flex justify-between items-center">
                <h4 class="font-label-md text-label-md text-on-surface-variant">Message History</h4>
                <span class="text-label-sm text-outline">Latest 50</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-lowest border-b border-outline-variant/10 text-outline text-[12px] uppercase tracking-wider">
                            <th class="px-md py-4 font-semibold">Recipient</th>
                            <th class="px-md py-4 font-semibold">Message Type</th>
                            <th class="px-md py-4 font-semibold">Sent Time</th>
                            <th class="px-md py-4 font-semibold">Status</th>
                            <th class="px-md py-4 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($smsMessages as $msg)
                        <tr class="hover:bg-surface-container/30 transition-colors">
                            <td class="px-md py-4">
                                <p class="font-label-md text-label-md text-on-surface">{{ $msg->patient->first_name ?? 'N/A' }} {{ $msg->patient->last_name ?? '' }}</p>
                                <p class="text-xs text-outline">{{ $msg->phone_number }}</p>
                            </td>
                            <td class="px-md py-4 text-body-sm text-on-surface-variant">{{ $msg->type }} Notification</td>
                            <td class="px-md py-4 text-body-sm text-on-surface-variant">{{ ($msg->sent_at ? \Carbon\Carbon::parse($msg->sent_at) : $msg->created_at)->format('M d, Y, h:i A') }}</td>
                            <td class="px-md py-4">
                                @if($msg->status === 'Sent')
                                <span class="inline-flex items-center gap-xs px-2 py-0.5 rounded-full bg-tertiary/10 text-tertiary text-[11px] font-bold">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span> Sent
                                </span>
                                @else
                                <span class="inline-flex items-center gap-xs px-2 py-0.5 rounded-full bg-error/10 text-error text-[11px] font-bold">
                                    <span class="material-symbols-outlined text-[14px]">error</span> {{ $msg->status }}
                                </span>
                                @endif
                            </td>
                            <td class="px-md py-4 text-right">
                                @if($msg->status !== 'Sent' && $msg->patient)
                                <a href="{{ route('sms', ['patient_id' => $msg->patient_id]) }}" class="text-xs text-primary font-bold hover:underline">Resend</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-md py-12 text-center text-on-surface-variant text-body-sm">
                                No SMS notifications found in history.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-span-12 lg:col-span-4 space-y-gutter">
        <section class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/20">
            <h4 class="font-label-md text-label-md text-on-surface uppercase tracking-wider mb-md">Gateway Configuration</h4>
            <form action="{{ route('sms.settings') }}" method="POST" class="space-y-sm">
                @csrf
                <div class="space-y-xs">
                    <label class="font-label-sm text-label-sm text-on-surface-variant block font-bold">Gateway URL *</label>
                    <input type="url" name="url" value="{{ $gatewaySettings['url'] }}" placeholder="http://192.168.1.100:8080" required
                           class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-sm py-2 px-3 bg-surface-container-lowest">
                </div>
                <div class="grid grid-cols-2 gap-sm">
                    <div class="space-y-xs">
                        <label class="font-label-sm text-label-sm text-on-surface-variant block font-bold">Username</label>
                        <input type="text" name="username" value="{{ $gatewaySettings['username'] }}" placeholder="Username"
                               class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-sm py-2 px-3 bg-surface-container-lowest">
                    </div>
                    <div class="space-y-xs">
                        <label class="font-label-sm text-label-sm text-on-surface-variant block font-bold">Password</label>
                        <input type="password" name="password" value="{{ $gatewaySettings['password'] }}" placeholder="Password"
                               class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-sm py-2 px-3 bg-surface-container-lowest">
                    </div>
                </div>
                <div class="flex gap-xs pt-xs">
                    <button type="submit" class="flex-1 py-2 text-center text-body-sm font-semibold bg-primary text-on-primary rounded-lg hover:opacity-90 transition-all">
                        Save Settings
                    </button>
                    <button type="button" id="btnTestConnection" onclick="testConnection()" class="px-3 py-2 text-center text-body-sm font-semibold text-secondary hover:bg-secondary/5 rounded-lg border border-secondary/20 transition-all flex items-center justify-center gap-xs">
                        <span id="testSpinner" class="hidden animate-spin material-symbols-outlined text-[16px]">sync</span>
                        <span>Test</span>
                    </button>
                </div>
            </form>
        </section>

        <div id="gatewayStatusCard" class="relative bg-gradient-to-br {{ $gatewayStatus ? 'from-primary to-primary-container' : 'from-error to-error-container' }} rounded-xl p-md overflow-hidden text-on-primary transition-all duration-300">
            <div class="relative z-10">
                <h5 class="font-headline-sm text-headline-sm font-bold">Gateway Status</h5>
                <p id="gatewayStatusText" class="text-body-sm opacity-90 mb-md">
                    {{ $gatewayStatus ? 'SMS gateway is currently active and processing messages at optimal speed.' : 'SMS gateway is offline. Please check your configuration and ensure the Android app is active.' }}
                </p>
                <div class="flex items-center gap-xs">
                    <span id="gatewayStatusDot" class="w-2 h-2 rounded-full {{ $gatewayStatus ? 'bg-tertiary animate-pulse' : 'bg-white' }}"></span>
                    <span id="gatewayStatusLabel" class="text-[12px] font-bold">{{ $gatewayStatus ? 'Stable Connection' : 'Disconnected' }}</span>
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-20">
                <span class="material-symbols-outlined !text-[120px]">cell_tower</span>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    const smsContent = document.getElementById('smsContent');
    const charCounter = document.getElementById('charCounter');
    
    function updateCharCount() {
        const text = smsContent.value;
        const chars = text.length;
        
        // SMS segments rule:
        // Standard SMS is 160 characters.
        // If message is longer than 160 characters, it gets split into parts of 153 characters.
        let segments = 0;
        if (chars > 0) {
            segments = chars <= 160 ? 1 : Math.ceil(chars / 153);
        }
        
        charCounter.textContent = `${chars} character${chars !== 1 ? 's' : ''} / ${segments} segment${segments !== 1 ? 's' : ''}`;
    }
    
    if (smsContent) {
        smsContent.addEventListener('input', updateCharCount);
    }
    
    function applyTemplate() {
        const val = document.getElementById('smsTemplate').value;
        if (val) {
            const option = document.getElementById('smsPatient').selectedOptions[0];
            const name = option && option.dataset.firstName ? option.dataset.firstName : 'there';
            document.getElementById('smsContent').value = val.replaceAll('{name}', name);
            updateCharCount();
        }
    }
    
    function testConnection() {
        const btn = document.getElementById('btnTestConnection');
        const spinner = document.getElementById('testSpinner');
        
        btn.disabled = true;
        spinner.classList.remove('hidden');
        
        fetch("{{ route('sms.status') }}")
            .then(res => res.json())
            .then(data => {
                const card = document.getElementById('gatewayStatusCard');
                const text = document.getElementById('gatewayStatusText');
                const dot = document.getElementById('gatewayStatusDot');
                const label = document.getElementById('gatewayStatusLabel');
                
                if (data.success && data.status === 'online') {
                    card.classList.remove('from-error', 'to-error-container');
                    card.classList.add('from-primary', 'to-primary-container');
                    text.textContent = 'SMS gateway is currently active and processing messages at optimal speed.';
                    dot.className = 'w-2 h-2 rounded-full bg-tertiary animate-pulse';
                    label.textContent = 'Stable Connection';
                } else {
                    card.classList.remove('from-primary', 'to-primary-container');
                    card.classList.add('from-error', 'to-error-container');
                    text.textContent = 'SMS gateway is offline. Please check your configuration and ensure the Android app is active.';
                    dot.className = 'w-2 h-2 rounded-full bg-white';
                    label.textContent = 'Disconnected';
                }
            })
            .catch(err => {
                console.error(err);
            })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('hidden');
            });
    }

    // Trigger count on load
    updateCharCount();
</script>
@endpush
@endsection
