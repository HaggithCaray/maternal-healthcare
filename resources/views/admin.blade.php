@extends('layouts.app')

@section('title', 'Admin')

@section('content')
@include('partials.flash')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-md mb-lg">
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Admin</h3>
        <p class="text-on-surface-variant font-body-md">Manage who can sign in, and review what was done in the system.</p>
    </div>
    <div class="flex gap-sm w-full sm:w-auto">
        <a href="{{ route('admin.activity') }}" class="flex-1 sm:flex-initial px-md py-sm border border-outline-variant rounded-lg font-label-md flex items-center justify-center gap-xs hover:bg-surface-container-high transition-all">
            <span class="material-symbols-outlined">receipt_long</span>
            Activity Log
        </a>
        <a href="{{ route('admin.users.create') }}" class="flex-1 sm:flex-initial bg-primary text-on-primary px-md py-sm rounded-lg font-label-md flex items-center justify-center gap-xs hover:opacity-90 active:scale-95 transition-all shadow-xs">
            <span class="material-symbols-outlined">person_add</span>
            Add Healthcare Worker
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-md mb-lg">
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">Healthcare worker accounts</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs">{{ number_format($counts['staff']) }}</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">Patient portal accounts</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs">{{ number_format($counts['patients']) }}</p>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow border border-outline-variant/10">
        <p class="text-label-sm text-on-surface-variant">Deactivated accounts</p>
        <p class="text-headline-md font-bold text-on-surface mt-xs">{{ number_format($counts['inactive']) }}</p>
    </div>
</div>

<div class="bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 mb-lg">
    <form method="GET" action="{{ route('admin') }}" class="flex items-center justify-between p-md border-b border-outline-variant/10 flex-col md:flex-row gap-sm">
        <div class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary">manage_accounts</span>
            <h4 class="font-headline-sm text-headline-sm">User Accounts</h4>
        </div>
        <div class="flex flex-wrap items-center gap-sm w-full md:w-auto">
            <div class="relative flex-1 md:flex-initial">
                <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Search name or email..." class="bg-surface-container border-none rounded-lg pl-xl pr-md py-xs text-label-sm outline-hidden focus:ring-1 focus:ring-primary w-full md:w-56">
            </div>
            <select name="role" onchange="this.form.submit()" class="bg-surface-container border-none rounded-lg px-md py-xs text-label-sm outline-hidden focus:ring-1 focus:ring-primary" aria-label="Role">
                <option value="all" @selected($role === 'all')>All roles</option>
                <option value="admin" @selected($role === 'admin')>Healthcare workers</option>
                <option value="user" @selected($role === 'user')>Patients</option>
            </select>
            <select name="status" onchange="this.form.submit()" class="bg-surface-container border-none rounded-lg px-md py-xs text-label-sm outline-hidden focus:ring-1 focus:ring-primary" aria-label="Status">
                <option value="all" @selected($status === 'all')>Any status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Deactivated</option>
            </select>
            <button type="submit" class="px-md py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high">Search</button>
        </div>
    </form>
    <div class="overflow-x-auto">
    <table class="w-full">
        <thead>
            <tr class="text-left text-label-sm text-on-surface-variant border-b border-outline-variant/10 bg-surface-container-high/10">
                <th class="p-md font-medium">User</th>
                <th class="p-md font-medium">Role</th>
                <th class="p-md font-medium">Patient record</th>
                <th class="p-md font-medium">Last sign-in</th>
                <th class="p-md font-medium">Status</th>
                <th class="p-md font-medium"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr class="border-b border-outline-variant/5 hover:bg-surface-container-high/20 transition-colors">
                <td class="p-md">
                    <div class="flex items-center gap-sm">
                        <div class="w-9 h-9 rounded-full {{ $user->is_active ? 'bg-primary-container/20' : 'bg-surface-container' }} flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined {{ $user->is_active ? 'text-primary' : 'text-outline' }} text-[18px]" style="font-variation-settings: 'FILL' 1;">{{ $user->isAdmin() ? 'medical_services' : 'person' }}</span>
                        </div>
                        <div>
                            <p class="text-label-md font-label-md">
                                {{ $user->name }}
                                @if($user->is(auth()->user()))<span class="text-label-sm text-on-surface-variant font-normal">(you)</span>@endif
                            </p>
                            <p class="text-label-sm text-on-surface-variant">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="p-md">
                    <span class="px-sm py-xs {{ $user->isAdmin() ? 'bg-primary-container/20 text-primary' : 'bg-secondary-container/30 text-on-secondary-container' }} rounded-full text-label-sm font-label-sm whitespace-nowrap">{{ $user->roleLabel() }}</span>
                </td>
                <td class="p-md text-label-md text-on-surface-variant">
                    @if($user->patient)
                        <a href="{{ route('patients.edit', $user->patient->id) }}" class="text-primary hover:underline">{{ $user->patient->first_name }} {{ $user->patient->last_name }}</a>
                    @else
                        &mdash;
                    @endif
                </td>
                <td class="p-md text-label-md text-on-surface-variant whitespace-nowrap" title="{{ $user->last_login_at?->format('M d, Y g:i A') }}">
                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                </td>
                <td class="p-md">
                    @if($user->is_active)
                    <span class="flex items-center gap-xs text-tertiary text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-tertiary rounded-full"></span>
                        Active
                    </span>
                    @else
                    <span class="flex items-center gap-xs text-on-surface-variant text-label-sm font-label-sm">
                        <span class="w-1.5 h-1.5 bg-outline rounded-full"></span>
                        Deactivated
                    </span>
                    @endif
                </td>
                <td class="p-md text-right">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-primary text-label-sm font-label-sm hover:underline">Manage</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-lg text-center text-body-sm text-on-surface-variant">No accounts match these filters.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($users->total() > 0)
    <div class="flex items-center justify-between p-md border-t border-outline-variant/10 gap-sm flex-wrap">
        <span class="text-label-sm text-on-surface-variant">
            Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} accounts
        </span>
        @if($users->hasPages())
        <div class="flex gap-sm">
            @if($users->onFirstPage())
            <span class="px-sm py-xs border border-outline-variant/40 rounded-lg text-label-sm text-on-surface-variant/50">Previous</span>
            @else
            <a href="{{ $users->previousPageUrl() }}" class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">Previous</a>
            @endif
            <span class="px-sm py-xs bg-primary text-on-primary rounded-lg text-label-sm">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
            @if($users->hasMorePages())
            <a href="{{ $users->nextPageUrl() }}" class="px-sm py-xs border border-outline-variant rounded-lg text-label-sm hover:bg-surface-container-high transition-colors">Next</a>
            @else
            <span class="px-sm py-xs border border-outline-variant/40 rounded-lg text-label-sm text-on-surface-variant/50">Next</span>
            @endif
        </div>
        @endif
    </div>
    @endif
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter">
    <div class="col-span-1 xl:col-span-8 bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 overflow-hidden">
        <div class="flex items-center justify-between p-md border-b border-outline-variant/10">
            <div class="flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary">receipt_long</span>
                <h4 class="font-headline-sm text-headline-sm">Recent Activity</h4>
            </div>
            <a href="{{ route('admin.activity') }}" class="text-primary text-label-sm font-label-sm hover:underline">View all</a>
        </div>
        @include('admin.partials.activity-list', ['logs' => $recentActivity])
    </div>
    <div class="col-span-1 xl:col-span-4 bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 h-fit">
        <div class="flex items-center gap-sm mb-md">
            <span class="material-symbols-outlined text-secondary">dns</span>
            <h4 class="font-label-md text-on-surface">System</h4>
        </div>
        <dl class="space-y-sm">
            @foreach($system as $label => $value)
            <div class="flex items-center justify-between gap-sm text-label-sm">
                <dt class="text-on-surface-variant">{{ $label }}</dt>
                <dd class="font-bold text-on-surface text-right">{{ $value }}</dd>
            </div>
            @endforeach
        </dl>
        <a href="{{ route('sms') }}" class="block mt-md text-primary text-label-sm font-label-sm hover:underline">SMS gateway settings &rarr;</a>
    </div>
</div>
@endsection
