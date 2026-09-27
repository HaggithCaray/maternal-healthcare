@extends('layouts.app')

@section('title', 'Manage Account')

@section('content')
@include('partials.flash')

<div class="flex items-center gap-sm mb-lg">
    <a href="{{ route('admin') }}" class="p-xs text-on-surface-variant hover:bg-surface-variant rounded-lg transition-all" aria-label="Back to Admin">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">{{ $user->name }}</h3>
        <p class="text-on-surface-variant font-body-md">
            {{ $user->roleLabel() }} &bull; {{ $user->is_active ? 'Active' : 'Deactivated' }} &bull;
            Last sign-in: {{ $user->last_login_at ? $user->last_login_at->format('M d, Y g:i A') : 'never' }}
        </p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-gutter">
    <div class="col-span-1 xl:col-span-7 space-y-gutter">
        {{-- Details --}}
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm mb-md">Account details</h4>
            @if($user->isAdmin())
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-md">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-xs">
                    <label for="name" class="font-label-md text-label-md text-on-surface-variant">Full name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="text">
                    @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col gap-xs">
                    <label for="email" class="font-label-md text-label-md text-on-surface-variant">Email (used to sign in)</label>
                    <input id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="email">
                    @error('email')<p class="text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-md py-sm rounded-lg bg-primary text-on-primary font-label-md flex items-center gap-xs hover:opacity-90 transition-all">
                        <span class="material-symbols-outlined">save</span>
                        Save Details
                    </button>
                </div>
            </form>
            @else
            <dl class="space-y-sm text-body-sm">
                <div class="flex justify-between gap-sm"><dt class="text-on-surface-variant">Name</dt><dd class="font-bold">{{ $user->name }}</dd></div>
                <div class="flex justify-between gap-sm"><dt class="text-on-surface-variant">Email (used to sign in)</dt><dd class="font-bold">{{ $user->email }}</dd></div>
            </dl>
            @if($user->patient)
            <p class="text-xs text-on-surface-variant mt-md">
                This is a patient portal login. Change the name or email on
                <a href="{{ route('patients.edit', $user->patient->id) }}" class="text-primary font-bold hover:underline">{{ $user->patient->first_name }} {{ $user->patient->last_name }}'s patient record</a>
                &mdash; the login follows the record.
            </p>
            @endif
            @endif
        </div>

        {{-- Access --}}
        <div class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm mb-md">Access</h4>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm py-sm border-b border-outline-variant/20">
                <div>
                    <p class="font-label-md text-label-md">Reset password</p>
                    <p class="text-xs text-on-surface-variant">Creates a new temporary password and signs the account out of other devices.</p>
                </div>
                <form method="POST" action="{{ route('admin.users.password', $user) }}" onsubmit="return confirm('Generate a new temporary password for {{ addslashes($user->name) }}? The old password will stop working.')">
                    @csrf
                    <button type="submit" class="px-md py-sm rounded-lg border border-primary text-primary font-label-md flex items-center gap-xs hover:bg-primary-container/20 whitespace-nowrap">
                        <span class="material-symbols-outlined">key</span>
                        Reset Password
                    </button>
                </form>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm pt-sm">
                <div>
                    <p class="font-label-md text-label-md">{{ $user->is_active ? 'Deactivate account' : 'Reactivate account' }}</p>
                    <p class="text-xs text-on-surface-variant">
                        {{ $user->is_active
                            ? 'The person can no longer sign in. Their records and history are kept.'
                            : 'Allow this person to sign in again with their current password.' }}
                    </p>
                </div>
                @if($user->is_active && $user->is(auth()->user()))
                <p class="text-xs text-on-surface-variant">You can't deactivate your own account.</p>
                @else
                <form method="POST" action="{{ route('admin.users.status', $user) }}" @if($user->is_active) onsubmit="return confirm('Deactivate {{ addslashes($user->name) }}? They will be signed out and unable to sign in.')" @endif>
                    @csrf
                    <button type="submit" class="px-md py-sm rounded-lg font-label-md flex items-center gap-xs whitespace-nowrap {{ $user->is_active ? 'border border-error text-error hover:bg-error-container/30' : 'bg-primary text-on-primary hover:opacity-90' }}">
                        <span class="material-symbols-outlined">{{ $user->is_active ? 'block' : 'check_circle' }}</span>
                        {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-span-1 xl:col-span-5 bg-surface-container-lowest rounded-xl soft-drop-shadow border border-outline-variant/10 overflow-hidden h-fit">
        <div class="flex items-center justify-between p-md border-b border-outline-variant/10">
            <h4 class="font-headline-sm text-headline-sm">Recent activity by this account</h4>
            <a href="{{ route('admin.activity', ['user' => $user->id]) }}" class="text-primary text-label-sm font-label-sm hover:underline">View all</a>
        </div>
        @include('admin.partials.activity-list', ['logs' => $activity])
    </div>
</div>
@endsection
