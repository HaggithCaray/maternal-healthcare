@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div class="flex items-center gap-sm">
        <a href="{{ route('admin') }}" class="p-xs text-on-surface-variant hover:bg-surface-variant rounded-lg transition-all" aria-label="Back to Admin">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h3 class="font-headline-lg text-headline-lg text-primary">Activity Log</h3>
            <p class="text-on-surface-variant font-body-md">
                Every record viewed or changed, newest first{{ $filterUser ? ' — ' . $filterUser->name . ' only' : '' }}.
            </p>
        </div>
    </div>
    @if($filterUser)
    <a href="{{ route('admin.activity') }}" class="px-md py-sm border border-outline-variant rounded-lg font-label-md hover:bg-surface-container-high">Show everyone</a>
    @endif
</div>

<div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 overflow-hidden">
    @include('admin.partials.activity-list', ['logs' => $logs])

    @if($logs->hasPages())
    <div class="flex items-center justify-between p-md border-t border-outline-variant/10">
        <span class="text-label-sm text-on-surface-variant">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }} &bull; {{ number_format($logs->total()) }} entries</span>
        <div class="flex gap-sm">
            @if(! $logs->onFirstPage())
            <a href="{{ $logs->previousPageUrl() }}" class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high">Newer</a>
            @endif
            @if($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}" class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high">Older</a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
