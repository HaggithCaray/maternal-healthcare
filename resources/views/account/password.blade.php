@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
@include('partials.flash')

<div class="mb-lg">
    <h3 class="font-headline-lg text-headline-lg text-primary">Change Password</h3>
    <p class="text-on-surface-variant font-body-md">Replace the temporary password you were given with one only you know.</p>
</div>

<form method="POST" action="{{ route('account.password.update') }}" class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 max-w-xl space-y-md">
    @csrf
    @method('PUT')
    <div class="flex flex-col gap-xs">
        <label for="current_password" class="font-label-md text-label-md text-on-surface-variant">Current password</label>
        <input id="current_password" name="current_password" required autocomplete="current-password" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="password">
        @error('current_password')<p class="text-xs text-error">{{ $message }}</p>@enderror
    </div>
    <div class="flex flex-col gap-xs">
        <label for="password" class="font-label-md text-label-md text-on-surface-variant">New password</label>
        <input id="password" name="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="password" aria-describedby="password-help">
        <p id="password-help" class="text-xs text-on-surface-variant">At least 8 characters, with letters and numbers.</p>
        @error('password')<p class="text-xs text-error">{{ $message }}</p>@enderror
    </div>
    <div class="flex flex-col gap-xs">
        <label for="password_confirmation" class="font-label-md text-label-md text-on-surface-variant">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="password">
    </div>
    <div class="flex justify-end pt-sm">
        <button type="submit" class="px-md py-sm rounded-lg bg-primary text-on-primary font-label-md flex items-center gap-xs hover:opacity-90 transition-all">
            <span class="material-symbols-outlined">lock_reset</span>
            Change Password
        </button>
    </div>
</form>
@endsection
