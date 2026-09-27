@extends('layouts.app')

@section('title', 'Edit Patient - ' . $patient->first_name . ' ' . $patient->last_name)

@section('content')
<header class="flex flex-col md:flex-row md:items-end justify-between gap-md mb-lg">
    <div>
        <div class="flex items-center gap-sm mb-xs">
            <a href="{{ route('records') }}" class="p-xs text-on-surface-variant hover:bg-surface-variant rounded-lg transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h1 class="font-headline-lg text-headline-lg text-primary">Edit Patient</h1>
        </div>
        <p class="text-on-surface-variant max-w-2xl ml-10">Editing record for <strong>{{ $patient->first_name }} {{ $patient->last_name }}</strong> &mdash; ID: #BC-{{ \Carbon\Carbon::parse($patient->created_at)->format('Y') }}-{{ sprintf('%03d', $patient->id) }}</p>
    </div>
    <div class="flex items-center gap-sm">
        @if($patient->registration_type === 'Maternal')
        <span class="flex items-center gap-xs px-sm py-xs rounded-full text-xs font-bold bg-secondary-container/20 text-secondary border border-secondary/20">
            <span class="material-symbols-outlined text-[16px]">pregnant_woman</span> Maternal
        </span>
        @else
        <span class="flex items-center gap-xs px-sm py-xs rounded-full text-xs font-bold bg-primary-container/20 text-primary border border-primary/20">
            <span class="material-symbols-outlined text-[16px]">child_care</span> Child
        </span>
        @endif
    </div>
</header>

@if($errors->any())
<div class="bg-error-container/30 border border-error/20 p-md rounded-xl mb-lg flex items-start gap-sm">
    <span class="material-symbols-outlined text-error">error</span>
    <div>
        <p class="font-label-md text-label-md text-error">Please fix the following errors:</p>
        <ul class="mt-xs text-sm text-on-error-container list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@include('partials.flash')

<div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow mb-lg flex flex-col sm:flex-row sm:items-center justify-between gap-md">
    <div class="flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary bg-primary-container/20 p-xs rounded-lg">account_circle</span>
        <div>
            <p class="font-label-md text-label-md text-on-surface">Patient Portal Account</p>
            @if($patient->user)
            <p class="text-body-sm text-on-surface-variant">Signs in as <strong>{{ $patient->user->email }}</strong></p>
            @elseif($patient->email)
            <p class="text-body-sm text-on-surface-variant">No portal account yet &mdash; one can be created for <strong>{{ $patient->email }}</strong>.</p>
            @else
            <p class="text-body-sm text-on-surface-variant">No portal account. Add an email address below to enable one.</p>
            @endif
        </div>
    </div>
    @if($patient->user || $patient->email)
    <form method="POST" action="{{ route('patients.portal-password', $patient->id) }}"
          onsubmit="return confirm('{{ $patient->user ? 'Generate a new temporary password? The old one will stop working.' : 'Create a portal account for this patient?' }}')">
        @csrf
        <button type="submit" class="flex items-center gap-xs px-md py-sm rounded-lg border border-primary text-primary font-label-md text-label-md hover:bg-primary-container/20 transition-all whitespace-nowrap">
            <span class="material-symbols-outlined">key</span>
            {{ $patient->user ? 'Reset Password' : 'Create Portal Account' }}
        </button>
    </form>
    @endif
</div>

<form method="POST" action="{{ route('patients.update', $patient->id) }}" id="editPatientForm">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
        <aside class="lg:col-span-3">
            <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow sticky top-24">
                <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-md">Sections</h3>
                <nav class="flex flex-col gap-sm" id="sectionNav">
                    <a href="#personal" class="flex items-center gap-md px-sm py-xs rounded-lg text-primary font-bold bg-secondary-container/30 transition-colors section-link" data-section="personal">
                        <span class="material-symbols-outlined text-[20px]">person</span>
                        <span class="font-label-md text-label-md">Personal Info</span>
                    </a>
                    <a href="#contact" class="flex items-center gap-md px-sm py-xs rounded-lg text-on-surface-variant hover:bg-surface-variant/50 transition-colors section-link" data-section="contact">
                        <span class="material-symbols-outlined text-[20px]">contact_phone</span>
                        <span class="font-label-md text-label-md">Contact & Address</span>
                    </a>
                    @if($patient->registration_type === 'Maternal')
                    <a href="#maternal" class="flex items-center gap-md px-sm py-xs rounded-lg text-on-surface-variant hover:bg-surface-variant/50 transition-colors section-link" data-section="maternal">
                        <span class="material-symbols-outlined text-[20px]">pregnant_woman</span>
                        <span class="font-label-md text-label-md">Maternal Details</span>
                    </a>
                    @endif
                    @if($patient->registration_type === 'Child')
                    <a href="#child" class="flex items-center gap-md px-sm py-xs rounded-lg text-on-surface-variant hover:bg-surface-variant/50 transition-colors section-link" data-section="child">
                        <span class="material-symbols-outlined text-[20px]">child_care</span>
                        <span class="font-label-md text-label-md">Child Details</span>
                    </a>
                    @endif
                </nav>
                <div class="mt-xl pt-md border-t border-outline-variant/30">
                    <div class="bg-primary-container/20 p-sm rounded-lg flex gap-sm">
                        <span class="material-symbols-outlined text-primary" data-icon="info">info</span>
                        <p class="text-xs text-primary leading-tight">Changes are saved immediately upon submission.</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="lg:col-span-9 flex flex-col gap-gutter">
            {{-- Personal Information --}}
            <div id="personal" class="bg-surface-container-lowest p-gutter rounded-xl soft-drop-shadow">
                <div class="mb-lg">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Personal Information</h2>
                    <p class="text-body-sm text-on-surface-variant">Update the patient's basic demographics.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">First Name *</label>
                        <input name="first_name" value="{{ old('first_name', $patient->first_name) }}" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="text">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Last Name *</label>
                        <input name="last_name" value="{{ old('last_name', $patient->last_name) }}" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="text">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Date of Birth *</label>
                        <input name="dob" value="{{ old('dob', $patient->dob ? \Carbon\Carbon::parse($patient->dob)->format('Y-m-d') : '') }}" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="date">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Gender *</label>
                        <div class="flex gap-md mt-xs">
                            <label class="flex items-center gap-xs cursor-pointer">
                                <input name="gender" value="Female" {{ old('gender', $patient->gender) === 'Female' ? 'checked' : '' }} class="text-primary focus:ring-primary" type="radio">
                                <span>Female</span>
                            </label>
                            <label class="flex items-center gap-xs cursor-pointer">
                                <input name="gender" value="Male" {{ old('gender', $patient->gender) === 'Male' ? 'checked' : '' }} class="text-primary focus:ring-primary" type="radio">
                                <span>Male</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Occupation</label>
                        <input name="occupation" value="{{ old('occupation', $patient->occupation) }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Housewife, Teacher, Vendor" type="text">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Status *</label>
                        <select name="status" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                            @foreach(['Active', 'Due for Visit', 'High Risk', 'Completed'] as $statusOption)
                            <option value="{{ $statusOption }}" {{ old('status', $patient->status) === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Contact & Address --}}
            <div id="contact" class="bg-surface-container-lowest p-gutter rounded-xl soft-drop-shadow">
                <div class="mb-lg">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Contact & Location</h2>
                    <p class="text-body-sm text-on-surface-variant">Ensure we have the correct information to reach the patient in case of emergencies.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Phone Number *</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-outline bg-surface-container text-on-surface-variant text-sm">+63</span>
                            <input name="phone" value="{{ old('phone', $patient->phone) }}" required class="w-full rounded-r-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="917 123 4567" type="tel">
                        </div>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Email Address</label>
                        <input name="email" value="{{ old('email', $patient->email) }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="example@email.com" type="email">
                    </div>
                    <div class="md:col-span-2 flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Permanent Address *</label>
                        <textarea name="address" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="House number, Street, Purok..." rows="3">{{ old('address', $patient->address) }}</textarea>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Barangay</label>
                        <input class="w-full rounded-lg border-outline bg-surface-container text-on-surface-variant px-sm py-base" readonly value="{{ $patient->barangay }}" type="text">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Emergency Contact Person *</label>
                        <input name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="Name of Relative" type="text">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Emergency Contact Phone *</label>
                        <input name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone) }}" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="Phone of emergency contact" type="tel">
                    </div>
                </div>
            </div>

            {{-- Maternal Details --}}
            @if($patient->registration_type === 'Maternal' && $patient->maternalRecord)
            <div id="maternal" class="bg-surface-container-lowest p-gutter rounded-xl soft-drop-shadow">
                <div class="mb-lg">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Maternal Details</h2>
                    <p class="text-body-sm text-on-surface-variant">Update prenatal care information for this patient.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Last Menstrual Period (LMP)</label>
                        <input name="lmp" value="{{ old('lmp', $patient->maternalRecord->lmp ? \Carbon\Carbon::parse($patient->maternalRecord->lmp)->format('Y-m-d') : '') }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="date">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Gravida</label>
                        <input name="gravida" value="{{ old('gravida', $patient->maternalRecord->gravida) }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" min="0" placeholder="0" type="number">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Para</label>
                        <input name="para" value="{{ old('para', $patient->maternalRecord->para) }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" min="0" placeholder="0" type="number">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">PhilHealth Number</label>
                        <input name="philhealth_number" value="{{ old('philhealth_number', $patient->maternalRecord->philhealth_number) }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="XX-XXXXXXXXX-X" type="text">
                    </div>
                </div>
                <div class="mt-md">
                    <label class="font-label-md text-label-md text-on-surface-variant mb-xs block">Medical History</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
                        @php
                            $checkedConditions = \App\Models\MaternalRecord::normalizeConditions(old('medical_history', $patient->maternalRecord->medical_history ?? []));
                        @endphp
                        @foreach(\App\Models\MaternalRecord::CONDITIONS as $i => $condition)
                        @php
                            $checked = in_array($condition, $checkedConditions, true);
                        @endphp
                        <div class="flex items-center p-sm rounded-lg border border-outline-variant hover:bg-surface-container transition-colors cursor-pointer">
                            <input name="medical_history[{{ $condition }}]" value="1" {{ $checked ? 'checked' : '' }} class="w-5 h-5 rounded text-primary focus:ring-primary mr-sm" id="hist_{{ $i + 1 }}" type="checkbox">
                            <label class="text-on-surface-variant text-sm cursor-pointer" for="hist_{{ $i + 1 }}">{{ $condition }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-md">
                    <label class="font-label-md text-label-md text-on-surface-variant mb-xs block">Known Allergies</label>
                    <textarea name="allergies" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="List any medicine or food allergies..." rows="3">{{ old('allergies', $patient->maternalRecord->allergies) }}</textarea>
                </div>
            </div>
            @endif

            {{-- Child Details --}}
            @if($patient->registration_type === 'Child' && $patient->childRecord)
            <div id="child" class="bg-surface-container-lowest p-gutter rounded-xl soft-drop-shadow">
                <div class="mb-lg">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Child Details</h2>
                    <p class="text-body-sm text-on-surface-variant">Update birth and screening information for this child.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div class="flex flex-col gap-xs md:col-span-2">
                        <label class="font-label-md text-label-md text-on-surface-variant">Mother (registered maternal patient)</label>
                        <select name="mother_id" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                            <option value="">&mdash; Not registered / unknown &mdash;</option>
                            @foreach($mothers as $mother)
                            <option value="{{ $mother->id }}" @selected(old('mother_id', $patient->childRecord->mother_id) == $mother->id)>{{ $mother->last_name }}, {{ $mother->first_name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-on-surface-variant">The linked mother can see this child's growth and vaccines in her patient portal.</p>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Birth Weight (kg)</label>
                        <input name="birth_weight_kg" value="{{ old('birth_weight_kg', $patient->childRecord->birth_weight_kg) }}" step="0.01" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 3.2" type="number">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Birth Height (cm)</label>
                        <input name="birth_height_cm" value="{{ old('birth_height_cm', $patient->childRecord->birth_height_cm) }}" step="0.1" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 50.0" type="number">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Head Circumference (cm)</label>
                        <input name="head_circumference_cm" value="{{ old('head_circumference_cm', $patient->childRecord->head_circumference_cm) }}" step="0.1" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 34.0" type="number">
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Birth Type</label>
                        <select name="birth_type" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                            @foreach(['Single', 'Twin', 'Triplet', 'Multiple'] as $type)
                            <option value="{{ $type }}" {{ old('birth_type', $patient->childRecord->birth_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-label-md text-on-surface-variant">Delivery Type</label>
                        <select name="delivery_type" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                            @foreach(['Normal', 'C-Section', 'Assisted'] as $type)
                            <option value="{{ $type }}" {{ old('delivery_type', $patient->childRecord->delivery_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-md">
                    <label class="font-label-md text-label-md text-on-surface-variant mb-xs block">Newborn Screenings</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
                        @foreach([
                            'has_newborn_screening' => 'Newborn Screening',
                            'has_hearing_screening' => 'Hearing Screening',
                            'has_eye_prophylaxis' => 'Eye Prophylaxis',
                            'has_vitamin_k' => 'Vitamin K',
                            'has_bcg_at_birth' => 'BCG at Birth',
                            'has_hepb_at_birth' => 'Hepatitis B at Birth',
                        ] as $field => $label)
                        <div class="flex items-center p-sm rounded-lg border border-outline-variant hover:bg-surface-container transition-colors cursor-pointer">
                            <input name="{{ $field }}" value="1" {{ old($field, $patient->childRecord->{$field}) ? 'checked' : '' }} class="w-5 h-5 rounded text-primary focus:ring-primary mr-sm" id="screen_{{ $field }}" type="checkbox">
                            <label class="text-on-surface-variant text-sm cursor-pointer" for="screen_{{ $field }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Submit --}}
            <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow flex items-center justify-between gap-md">
                <a href="{{ route('records') }}" class="flex items-center gap-xs px-md py-sm rounded-lg text-on-surface-variant font-label-md text-label-md hover:bg-surface-variant transition-all">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Cancel
                </a>
                <button type="submit" class="flex items-center gap-xs px-xl py-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:shadow-lg active:scale-95 transition-all whitespace-nowrap">
                    <span class="material-symbols-outlined">save</span>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.section-link').forEach(link => {
        link.addEventListener('click', function(e) {
            document.querySelectorAll('.section-link').forEach(l => {
                l.classList.remove('text-primary', 'font-bold', 'bg-secondary-container/30');
                l.classList.add('text-on-surface-variant');
            });
            this.classList.add('text-primary', 'font-bold', 'bg-secondary-container/30');
            this.classList.remove('text-on-surface-variant');
        });
    });
</script>
@endpush
