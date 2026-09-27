@if(session('success'))
<div class="p-md mb-lg bg-emerald-500/10 text-emerald-600 rounded-xl border border-emerald-500/20 flex items-center gap-xs">
    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
    <span class="text-body-sm font-bold">{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="p-md mb-lg bg-error/10 text-error rounded-xl border border-error/20 flex items-center gap-xs">
    <span class="material-symbols-outlined text-error">error</span>
    <span class="text-body-sm font-bold">{{ session('error') }}</span>
</div>
@endif

@if(session('portal_credentials'))
<div class="p-md mb-lg bg-primary-container/20 rounded-xl border border-primary/30 flex items-start gap-sm" id="portal-credentials">
    <span class="material-symbols-outlined text-primary">key</span>
    <div class="flex-1">
        <p class="font-label-md text-label-md text-primary">Patient portal login for {{ session('portal_credentials.name') }}</p>
        <p class="text-body-sm text-on-surface mt-xs">Email: <strong>{{ session('portal_credentials.email') }}</strong></p>
        <p class="text-body-sm text-on-surface">Temporary password: <strong class="font-mono tracking-wider">{{ session('portal_credentials.password') }}</strong></p>
        <p class="text-xs text-on-surface-variant mt-xs">Give this to the patient now &mdash; it is shown only once. They sign in by choosing <strong>Patient</strong> on the login page.</p>
    </div>
</div>
@endif
