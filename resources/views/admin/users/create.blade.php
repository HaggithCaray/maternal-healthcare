@extends('layouts.app')

@section('title', 'Add Healthcare Worker')

@section('content')
<div class="flex items-center gap-sm mb-lg">
    <a href="{{ route('admin') }}" class="p-xs text-on-surface-variant hover:bg-surface-variant rounded-lg transition-all" aria-label="Back to Admin">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
        <h3 class="font-headline-lg text-headline-lg text-primary">Add Healthcare Worker</h3>
        <p class="text-on-surface-variant font-body-md">Creates a staff login. Patient logins are created when you register a patient with an email.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.store') }}" class="bg-surface-container-lowest rounded-xl p-md soft-drop-shadow border border-outline-variant/10 max-w-xl space-y-md">
    @csrf
    <div class="flex flex-col gap-xs">
        <label for="name" class="font-label-md text-label-md text-on-surface-variant">Full name *</label>
        <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Midwife Ana Reyes" type="text">
        @error('name')<p class="text-xs text-error">{{ $message }}</p>@enderror
    </div>
    <div class="flex flex-col gap-xs">
        <label for="email" class="font-label-md text-label-md text-on-surface-variant">Email *</label>
        <input id="email" name="email" value="{{ old('email') }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="name@example.com" type="email">
        @error('email')<p class="text-xs text-error">{{ $message }}</p>@enderror
    </div>
    <p class="text-xs text-on-surface-variant">A temporary password is shown once after saving. Give it to the new staff member so they can sign in and change it.</p>
    <div class="flex justify-end gap-sm pt-sm">
        <a href="{{ route('admin') }}" class="px-md py-sm rounded-lg text-on-surface-variant font-label-md hover:bg-surface-variant transition-all">Cancel</a>
        <button type="submit" class="px-md py-sm rounded-lg bg-primary text-on-primary font-label-md flex items-center gap-xs hover:opacity-90 transition-all">
            <span class="material-symbols-outlined">person_add</span>
            Create Account
        </button>
    </div>
</form>
@endsection
