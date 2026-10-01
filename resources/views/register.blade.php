@extends('layouts.app')

@section('title', 'Patient Registration')

@php
    $input = 'w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base';
    $label = 'font-label-md text-label-md text-on-surface-variant';
@endphp

@section('content')
<header class="flex flex-col md:flex-row md:items-end justify-between gap-md">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-primary">Patient Registration</h1>
        <p class="text-on-surface-variant max-w-2xl mt-xs">Register an expecting mother or a child into the Maternal Healthcare monitoring system. This information is kept strictly confidential.</p>
    </div>
</header>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <aside class="lg:col-span-3">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow sticky top-24">
            <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-md">Registration Progress</h3>
            <nav class="flex flex-col gap-sm">
                @foreach([
                    [1, 'Patient Type &amp; Info', 'Who is being registered', 'Patient Type &amp; Info', 'Who is being registered', null],
                    [2, 'Contact &amp; Address', 'Phone and emergency contact', 'Mother &amp; Contact', 'Link the mother', null],
                    [3, 'Pregnancy Details', 'LMP, history, birth plan', 'Birth Details', 'Weight, length, birth doses', null],
                    [4, 'Medical History', 'Past conditions', '', '', 'Maternal'],
                    [5, 'Review &amp; Submit', 'Confirm and register', 'Review &amp; Submit', 'Confirm and register', null],
                ] as [$number, $maternalTitle, $maternalHint, $childTitle, $childHint, $only])
                <div class="flex items-start gap-md group cursor-pointer" onclick="goToStep({{ $number }})" @if($only) data-maternal-only @endif>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-primary text-on-primary font-bold transition-colors shrink-0" id="step-icon-{{ $number }}">{{ $number }}</div>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-primary" id="step-label-{{ $number }}">
                            <span data-maternal-only>{!! $maternalTitle !!}</span>
                            @unless($only)<span data-child-only class="hidden">{!! $childTitle !!}</span>@endunless
                        </span>
                        <span class="text-xs text-on-surface-variant">
                            <span data-maternal-only>{{ $maternalHint }}</span>
                            @unless($only)<span data-child-only class="hidden">{{ $childHint }}</span>@endunless
                        </span>
                    </div>
                </div>
                @if(!$loop->last)
                <div class="ml-5 h-6 w-px bg-outline-variant" @if($only) data-maternal-only @endif></div>
                @endif
                @endforeach
            </nav>
            <div class="mt-xl pt-md border-t border-outline-variant/30">
                <div class="bg-primary-container/20 p-sm rounded-lg flex gap-sm">
                    <span class="material-symbols-outlined text-primary" data-icon="info">info</span>
                    <p class="text-xs text-primary leading-tight">All fields marked with an asterisk (*) are required.</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="lg:col-span-9 flex flex-col gap-gutter">
        <div class="bg-surface-container-lowest p-gutter rounded-xl soft-drop-shadow min-h-[600px] flex flex-col">
            @if($errors->any())
            <div class="p-md mb-lg bg-error/10 text-error rounded-xl border border-error/20" role="alert">
                <p class="font-label-md text-label-md">The patient was not registered. Please fix the following:</p>
                <ul class="list-disc ml-md mt-xs">
                    @foreach($errors->all() as $error)
                    <li class="text-body-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="grow" id="registrationForm">
                @csrf

                {{-- Step 1: who is being registered --}}
                <div class="step-transition" id="form-step-1">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Who are you registering?</h2>
                        <p class="text-body-sm text-on-surface-variant">The next steps only ask what applies to this patient.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-md mb-lg">
                        @foreach([
                            ['Maternal', 'reg_maternal', 'pregnant_woman', 'Expecting Mother', 'Prenatal care', 'bg-primary/10 text-primary'],
                            ['Child', 'reg_child', 'child_care', 'Child', 'Growth and vaccines', 'bg-secondary/10 text-secondary'],
                        ] as [$type, $id, $icon, $title, $hint, $iconClass])
                        <label for="{{ $id }}" data-type-card="{{ $type }}" class="p-md rounded-lg border border-outline-variant bg-surface-container-lowest flex items-center gap-md cursor-pointer hover:border-primary/50 transition-colors">
                            <div class="w-10 h-10 rounded-full {{ $iconClass }} flex items-center justify-center">
                                <span class="material-symbols-outlined">{{ $icon }}</span>
                            </div>
                            <div>
                                <p class="font-label-md text-label-md">{{ $title }}</p>
                                <p class="text-xs text-on-surface-variant">{{ $hint }}</p>
                            </div>
                            <input @checked(old('registration_type', 'Maternal') === $type) class="ml-auto text-primary" name="registration_type" id="{{ $id }}" value="{{ $type }}" type="radio">
                        </label>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">First Name *</label>
                            <input name="first_name" value="{{ old('first_name') }}" required maxlength="255" class="{{ $input }}" placeholder="e.g. Maria" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Last Name *</label>
                            <input name="last_name" value="{{ old('last_name') }}" required maxlength="255" class="{{ $input }}" placeholder="e.g. Santos" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Date of Birth *</label>
                            <input name="dob" value="{{ old('dob') }}" required data-max-today class="{{ $input }}" type="date">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}"><span data-maternal-only>Gender</span><span data-child-only class="hidden">Sex</span> *</label>
                            <div class="flex gap-md mt-xs">
                                <label class="flex items-center gap-xs cursor-pointer">
                                    <input name="gender" value="Female" @checked(old('gender', 'Female') === 'Female') class="text-primary focus:ring-primary" type="radio">
                                    <span>Female</span>
                                </label>
                                <label class="flex items-center gap-xs cursor-pointer">
                                    <input name="gender" value="Male" @checked(old('gender') === 'Male') class="text-primary focus:ring-primary" type="radio">
                                    <span>Male</span>
                                </label>
                            </div>
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs" data-maternal-only>
                            <label class="{{ $label }}">Occupation</label>
                            <input name="occupation" value="{{ old('occupation') }}" maxlength="255" class="{{ $input }}" placeholder="e.g. Housewife, Teacher, Vendor" type="text">
                        </div>
                    </div>
                </div>

                {{-- Step 2: contact (a child linked to its mother uses hers) --}}
                <div class="step-transition hidden" id="form-step-2">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">
                            <span data-maternal-only>Contact &amp; Location</span><span data-child-only class="hidden">Mother &amp; Contact</span>
                        </h2>
                        <p class="text-body-sm text-on-surface-variant">
                            <span data-maternal-only>Ensure we have the correct information to reach the patient in case of emergencies.</span>
                            <span data-child-only class="hidden">Link the child to the mother so she sees the child in her portal and her contact details are used.</span>
                        </p>
                    </div>

                    <div class="flex flex-col gap-xs mb-lg hidden" data-child-only>
                        <label class="{{ $label }}">Mother (registered patient)</label>
                        <select name="mother_id" id="motherSelect" class="{{ $input }} cursor-pointer">
                            <option value="">&mdash; Mother not registered &mdash;</option>
                            @foreach($mothers as $mother)
                            <option value="{{ $mother->id }}" @selected(old('mother_id') == $mother->id)>{{ $mother->last_name }}, {{ $mother->first_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="motherContactNote" class="hidden p-md bg-primary-container/15 border border-primary/20 rounded-xl flex items-start gap-sm">
                        <span class="material-symbols-outlined text-primary">family_restroom</span>
                        <p class="text-body-sm text-on-surface">The mother's address, phone and emergency contact are used for this child and stay up to date when her record changes. Nothing else to fill in here.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md" id="contactFields">
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}"><span data-maternal-only>Phone Number</span><span data-child-only class="hidden">Parent / Guardian Phone</span> *</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-outline bg-surface-container text-on-surface-variant text-sm">+63</span>
                                <input name="phone" value="{{ old('phone') }}" required maxlength="30" class="w-full rounded-r-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="917 123 4567" type="tel">
                            </div>
                        </div>
                        <div class="flex flex-col gap-xs" data-maternal-only>
                            <label class="{{ $label }}">Email Address</label>
                            <input name="email" value="{{ old('email') }}" maxlength="255" class="{{ $input }}" placeholder="example@email.com" type="email">
                            <p class="text-xs text-on-surface-variant">Becomes her patient portal login.</p>
                        </div>
                        <div class="flex flex-col gap-xs hidden" data-child-only>
                            <label class="{{ $label }}">Parent / Guardian Name *</label>
                            <input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" required maxlength="255" class="{{ $input }}" placeholder="Who looks after the child" type="text">
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <label class="{{ $label }}">Permanent Address *</label>
                            <textarea name="address" required maxlength="500" class="{{ $input }}" placeholder="House number, Street, Purok..." rows="3">{{ old('address') }}</textarea>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Barangay</label>
                            <input name="barangay" class="w-full rounded-lg border-outline bg-surface-container text-on-surface-variant px-sm py-base" readonly value="Bicao" type="text">
                        </div>
                        <div class="flex flex-col gap-xs" data-maternal-only>
                            <label class="{{ $label }}">Emergency Contact Person *</label>
                            <input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" required maxlength="255" class="{{ $input }}" placeholder="Name of Relative" type="text">
                        </div>
                        <div class="flex flex-col gap-xs" data-maternal-only>
                            <label class="{{ $label }}">Emergency Contact Phone *</label>
                            <input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" required maxlength="30" class="{{ $input }}" placeholder="Phone of emergency contact" type="tel">
                        </div>
                    </div>
                </div>

                {{-- Step 3: pregnancy or birth details --}}
                <div class="step-transition hidden" id="form-step-3">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">
                            <span data-maternal-only>Pregnancy Details</span><span data-child-only class="hidden">Birth Details</span>
                        </h2>
                        <p class="text-body-sm text-on-surface-variant">These start the right monitoring: prenatal checkups for a mother, growth and the vaccine schedule for a child.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md" id="maternalFields">
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Last Menstrual Period (LMP)</label>
                            <input name="lmp" value="{{ old('lmp') }}" data-max-today class="{{ $input }}" type="date">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Number of Previous Pregnancies (Gravida)</label>
                            <input name="gravida" value="{{ old('gravida') }}" class="{{ $input }}" min="0" max="30" step="1" placeholder="0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Number of Previous Births (Para)</label>
                            <input name="para" value="{{ old('para') }}" class="{{ $input }}" min="0" max="30" step="1" placeholder="0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">PhilHealth Number</label>
                            <input name="philhealth_number" value="{{ old('philhealth_number') }}" maxlength="50" class="{{ $input }}" placeholder="XX-XXXXXXXXX-X" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Planned Place of Delivery</label>
                            <input name="birth_plan_facility" value="{{ old('birth_plan_facility') }}" maxlength="255" class="{{ $input }}" placeholder="e.g. Carmen District Hospital" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Planned Birth Attendant</label>
                            <input name="birth_plan_attendant" value="{{ old('birth_plan_attendant') }}" maxlength="255" class="{{ $input }}" placeholder="e.g. Midwife, doctor" type="text">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md hidden" id="childFields">
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Birth Weight (kg)</label>
                            <input name="birth_weight_kg" value="{{ old('birth_weight_kg') }}" step="0.01" min="0.3" max="7" class="{{ $input }}" placeholder="e.g. 3.2" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Birth Length (cm)</label>
                            <input name="birth_height_cm" value="{{ old('birth_height_cm') }}" step="0.1" min="20" max="70" class="{{ $input }}" placeholder="e.g. 50.0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Birth Type</label>
                            <select name="birth_type" class="{{ $input }} cursor-pointer">
                                @foreach(\App\Services\PatientRegistration::BIRTH_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('birth_type', 'Single') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="{{ $label }}">Delivery Type</label>
                            <select name="delivery_type" class="{{ $input }} cursor-pointer">
                                @foreach(\App\Services\PatientRegistration::DELIVERY_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('delivery_type', 'Normal') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <p class="{{ $label }}">Birth Doses</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">
                                @foreach(['has_bcg_at_birth' => 'BCG given at birth', 'has_hepb_at_birth' => 'Hepatitis B given at birth'] as $field => $doseLabel)
                                <label class="flex items-center gap-sm p-sm rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, '1') === '1') class="w-5 h-5 rounded text-primary focus:ring-primary">
                                    <span class="text-sm text-on-surface-variant">{{ $doseLabel }}</span>
                                </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-on-surface-variant">Uncheck a dose the baby did not receive; it will stay on the schedule as due.</p>
                        </div>
                    </div>
                </div>

                {{-- Step 4: the mother's medical history (skipped for a child) --}}
                <div class="step-transition hidden" id="form-step-4">
                    <div data-maternal-only>
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Medical History</h2>
                        <p class="text-body-sm text-on-surface-variant">Check all that apply to the patient's history.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
                        @foreach(\App\Models\MaternalRecord::CONDITIONS as $i => $condition)
                        <div class="flex items-center p-sm rounded-lg border border-outline-variant hover:bg-surface-container transition-colors cursor-pointer">
                            <input name="medical_history[{{ $condition }}]" value="1" @checked(old("medical_history.{$condition}")) class="w-5 h-5 rounded text-primary focus:ring-primary mr-sm" id="hist_{{ $i + 1 }}" type="checkbox">
                            <label class="text-on-surface-variant text-sm cursor-pointer" for="hist_{{ $i + 1 }}">{{ $condition }}</label>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-lg">
                        <label class="{{ $label }} mb-xs block">Known Allergies</label>
                        <textarea name="allergies" maxlength="1000" class="{{ $input }}" placeholder="List any medicine or food allergies..." rows="3">{{ old('allergies') }}</textarea>
                    </div>
                    </div>
                </div>

                {{-- Step 5: review --}}
                <div class="step-transition hidden" id="form-step-5">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Review &amp; Submit</h2>
                        <p class="text-body-sm text-on-surface-variant">Confirm details and submit the registration.</p>
                    </div>
                    <div class="p-lg bg-surface-container rounded-xl border border-outline-variant/30 text-center">
                        <span class="material-symbols-outlined text-primary text-6xl mb-md">task_alt</span>
                        <h4 class="font-headline-sm text-on-surface">Ready to Register</h4>
                        <p class="text-body-sm text-on-surface-variant mt-xs max-w-md mx-auto">
                            <span data-maternal-only>This creates the mother's record and starts her prenatal monitoring.</span>
                            <span data-child-only class="hidden">This creates the child's record, a growth chart and the vaccine schedule.</span>
                        </p>
                    </div>
                    <div class="mt-lg p-md bg-secondary-container/10 border border-secondary/20 rounded-xl flex items-start gap-sm">
                        <span class="material-symbols-outlined text-secondary" data-icon="verified_user">verified_user</span>
                        <div class="flex flex-col gap-xs">
                            <p class="font-label-md text-label-md text-secondary">Data Protection Shield</p>
                            <p class="text-xs text-on-secondary-container">By submitting, you confirm that the patient (or the child's parent or guardian) has consented to the storage of this information for clinical use within the health network.</p>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mt-auto pt-xl border-t border-outline-variant/30 flex items-center justify-between gap-md">
                <button class="flex items-center gap-xs px-md py-sm rounded-lg text-on-surface-variant font-label-md text-label-md hover:bg-surface-variant transition-all invisible" id="prevBtn" onclick="prevStep()">
                    <span class="material-symbols-outlined" data-icon="arrow_back">arrow_back</span>
                    Previous
                </button>
                <div class="flex items-center gap-md">
                    <a href="{{ route('records') }}" class="hidden md:flex items-center gap-xs text-secondary font-label-md text-label-md hover:underline decoration-2 underline-offset-4 whitespace-nowrap">Cancel Registration</a>
                    <button class="flex items-center gap-xs px-xl py-sm rounded-lg bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:shadow-lg active:scale-95 transition-all whitespace-nowrap" id="nextBtn" onclick="nextStep()">
                        <span>Next Step</span>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-surface-variant/30 p-md rounded-xl border border-outline-variant/30 flex flex-col md:flex-row items-center gap-md">
            <div class="w-12 h-12 shrink-0 rounded-full bg-secondary text-on-secondary flex items-center justify-center">
                <span class="material-symbols-outlined" data-icon="lightbulb">lightbulb</span>
            </div>
            <div class="text-center md:text-left">
                <h4 class="font-label-md text-label-md text-on-surface">Works without internet</h4>
                <p class="text-body-sm text-on-surface-variant">If the connection drops, finish the form anyway: the registration is saved on this device and sent automatically when you are back online.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .step-transition {
        transition: all 0.3s ease-in-out;
    }
</style>
<script>
    const registrationForm = document.getElementById('registrationForm');
    const motherSelect = document.getElementById('motherSelect');
    const checkedType = () => document.querySelector('input[name="registration_type"]:checked')?.value ?? 'Maternal';

    // A child skips the mother's medical history (step 4).
    const stepsFor = (type) => (type === 'Child' ? [1, 2, 3, 5] : [1, 2, 3, 4, 5]);
    let currentStep = 1;

    function updateProgress() {
        const steps = stepsFor(checkedType());
        const position = steps.indexOf(currentStep);

        steps.forEach((step, index) => {
            const icon = document.getElementById(`step-icon-${step}`);
            const label = document.getElementById(`step-label-${step}`);
            const done = index < position;
            const current = index === position;

            icon.classList.remove('bg-primary', 'bg-tertiary', 'bg-surface-variant', 'text-on-primary', 'text-on-tertiary', 'text-on-surface-variant');
            icon.classList.add(...(done ? ['bg-tertiary', 'text-on-tertiary'] : current ? ['bg-primary', 'text-on-primary'] : ['bg-surface-variant', 'text-on-surface-variant']));
            icon.innerHTML = done
                ? '<span class="material-symbols-outlined text-sm" style="font-variation-settings: \'FILL\' 0, \'wght\' 700;">check</span>'
                : String(index + 1);

            label.classList.remove('text-primary', 'text-tertiary', 'text-on-surface-variant');
            label.classList.add(done ? 'text-tertiary' : current ? 'text-primary' : 'text-on-surface-variant');
        });

        for (let step = 1; step <= 5; step++) {
            document.getElementById(`form-step-${step}`).classList.toggle('hidden', step !== currentStep);
        }

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const last = position === steps.length - 1;

        prevBtn.classList.toggle('invisible', position === 0);
        nextBtn.innerHTML = last
            ? 'Complete Registration'
            : '<span>Next Step</span> <span class="material-symbols-outlined">arrow_forward</span>';
        nextBtn.classList.toggle('bg-primary', !last);
        nextBtn.classList.toggle('bg-secondary', last);
    }

    function stepOf(field) {
        const step = field.closest('[id^="form-step-"]');
        return step ? Number(step.id.replace('form-step-', '')) : 1;
    }

    // Show the browser's message on the first invalid field, on its own step.
    function reportFirstInvalid(fields) {
        const invalid = fields.find((field) => field.willValidate && !field.checkValidity());
        if (!invalid) {
            return false;
        }
        goToStep(stepOf(invalid));
        invalid.reportValidity();
        return true;
    }

    function nextStep() {
        const steps = stepsFor(checkedType());
        const position = steps.indexOf(currentStep);

        if (position < steps.length - 1) {
            const stepFields = [...document.getElementById(`form-step-${currentStep}`).querySelectorAll('input, select, textarea')];
            if (reportFirstInvalid(stepFields)) {
                return;
            }
            goToStep(steps[position + 1]);
        } else if (!reportFirstInvalid([...registrationForm.elements])) {
            // requestSubmit (not submit) fires the submit event, so an offline registration is
            // saved on the device by offline.js instead of being lost.
            registrationForm.requestSubmit();
        }
    }

    function prevStep() {
        const steps = stepsFor(checkedType());
        const position = steps.indexOf(currentStep);
        if (position > 0) {
            goToStep(steps[position - 1]);
        }
    }

    function goToStep(step) {
        const steps = stepsFor(checkedType());
        currentStep = steps.includes(step) ? step : steps[0];
        updateProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Show only what applies to the chosen patient type. Fields that are hidden are also disabled,
    // so they are neither checked nor sent (e.g. an LMP typed before switching to Child).
    function showTypeFields() {
        const type = checkedType();
        const followsMother = type === 'Child' && motherSelect.value !== '';

        const sections = [
            ...[...document.querySelectorAll('#maternalFields, [data-maternal-only]')].map((el) => [el, type === 'Maternal']),
            ...[...document.querySelectorAll('#childFields, [data-child-only]')].map((el) => [el, type === 'Child']),
        ];
        sections.forEach(([section, shown]) => section.classList.toggle('hidden', !shown));

        // A child linked to a registered mother uses her contact details; nothing to fill in.
        document.getElementById('contactFields').classList.toggle('hidden', followsMother);
        document.getElementById('motherContactNote').classList.toggle('hidden', !followsMother);

        registrationForm.querySelectorAll('input, select, textarea').forEach((field) => {
            const hiddenSection = field.closest('#maternalFields.hidden, #childFields.hidden, [data-maternal-only].hidden, [data-child-only].hidden, #contactFields.hidden');
            field.disabled = hiddenSection !== null;
        });

        document.querySelectorAll('[data-type-card]').forEach((card) => {
            const selected = card.dataset.typeCard === type;
            card.classList.toggle('border-2', selected);
            card.classList.toggle('border-primary', selected);
            card.classList.toggle('border-outline-variant', !selected);
        });

        if (!stepsFor(type).includes(currentStep)) {
            currentStep = 5;
        }
        updateProgress();
    }

    document.querySelectorAll('input[name="registration_type"]').forEach((radio) => radio.addEventListener('change', showTypeFields));
    motherSelect.addEventListener('change', showTypeFields);
    // A reset (after saving offline) puts the type back to its default; match the fields to it.
    registrationForm.addEventListener('reset', () => setTimeout(showTypeFields));

    // Dates can't be in the future. Set from this device's clock, since the form may be an offline copy.
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    document.querySelectorAll('input[data-max-today]').forEach((field) => { field.max = today; });

    showTypeFields();

    // After the server rejected the form, open the step with the first problem.
    const errorFields = @json($errors->keys());
    if (errorFields.length > 0) {
        // "medical_history.Asthma" is the error key for the field named "medical_history[Asthma]".
        const firstError = [...registrationForm.elements].find((field) => !field.disabled
            && errorFields.some((key) => field.name.split('[')[0] === key.split('.')[0]));
        if (firstError) {
            goToStep(stepOf(firstError));
        }
    }
</script>
@endpush
