{{-- Audit log rows. Expects $logs (a collection or paginator of AuditLog with user loaded). --}}
<div class="divide-y divide-outline-variant/10">
    @forelse($logs as $log)
    <div class="flex items-start gap-sm p-md hover:bg-surface-container-high/20 transition-colors">
        <span class="material-symbols-outlined text-[18px] mt-[2px] {{ str_contains($log->action, 'deactivate') || str_contains($log->action, 'reset') ? 'text-error' : 'text-on-surface-variant' }}">
            {{ str_starts_with($log->action, 'view_') ? 'visibility' : (str_contains($log->action, 'sync') ? 'sync' : 'edit_note') }}
        </span>
        <div class="flex-1 min-w-0">
            <p class="text-label-md">
                <span class="font-label-md">{{ $log->user?->name ?? 'System' }}</span>
                <span class="text-on-surface-variant">{{ \Illuminate\Support\Str::of($log->action)->replace('_', ' ')->lower() }}</span>
                @if($log->model_type)
                <span class="text-on-surface-variant">&middot; {{ class_basename($log->model_type) }} #{{ $log->model_id }}</span>
                @endif
            </p>
            <p class="text-label-sm text-on-surface-variant" title="{{ $log->created_at->format('M d, Y g:i:s A') }}">
                {{ $log->created_at->diffForHumans() }}@if($log->ip_address) &middot; {{ $log->ip_address }}@endif
            </p>
        </div>
    </div>
    @empty
    <p class="p-md text-body-sm text-on-surface-variant">No activity recorded yet.</p>
    @endforelse
</div>
