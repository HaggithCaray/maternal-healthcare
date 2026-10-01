@extends('layouts.app')

@section('title', 'Patient Registration')

@section('content')
<header class="flex flex-col md:flex-row md:items-end justify-between gap-md">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-primary">Patient Registration</h1>
        <p class="text-on-surface-variant max-w-2xl mt-xs">Please complete all steps to register a mother or child into the Maternal Healthcare monitoring system. This information is kept strictly confidential.</p>
    </div>
</header>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
    <aside class="lg:col-span-3">
        <div class="bg-surface-container-lowest p-md rounded-xl soft-drop-shadow sticky top-24">
            <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-md">Registration Progress</h3>
            <nav class="flex flex-col gap-sm">
                @foreach([
                    [1, 'Personal Info', 'Basic demographics'],
                    [2, 'Contact &amp; Address', 'Emergency details'],
                    [3, 'Maternal/Child Details', 'Specific health data'],
                    [4, 'Medical History', 'Past conditions'],
                    [5, 'Review &amp; Submit', 'Confirm and register'],
                ] as $step)
                <div class="flex items-start gap-md group cursor-pointer" onclick="goToStep({{ $step[0] }})">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-primary text-on-primary font-bold transition-colors" id="step-icon-{{ $step[0] }}">{{ $step[0] }}</div>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-primary" id="step-label-{{ $step[0] }}">{!! $step[1] !!}</span>
                        <span class="text-xs text-on-surface-variant">{{ $step[2] }}</span>
                    </div>
                </div>
                @if(!$loop->last)
                <div class="ml-5 h-6 w-px bg-outline-variant"></div>
                @endif
                @endforeach
            </nav>
            <div class="mt-xl pt-md border-t border-outline-variant/30">
                <div class="bg-primary-container/20 p-sm rounded-lg flex gap-sm">
                    <span class="material-symbols-outlined text-primary" data-icon="info">info</span>
                    <p class="text-xs text-primary leading-tight">All fields marked with an asterisk (*) are mandatory for system compliance.</p>
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
                <div class="step-transition" id="form-step-1">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Personal Information</h2>
                        <p class="text-body-sm text-on-surface-variant">Register a new patient to the barangay database.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">First Name *</label>
                            <input name="first_name" value="{{ old('first_name') }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Maria" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Last Name *</label>
                            <input name="last_name" value="{{ old('last_name') }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Santos" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Date of Birth *</label>
                            <input name="dob" value="{{ old('dob') }}" required data-max-today class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="date">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Gender *</label>
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
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Occupation</label>
                            <input name="occupation" value="{{ old('occupation') }}" maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Housewife, Teacher, Vendor" type="text">
                        </div>
                    </div>
                </div>

                <div class="step-transition hidden" id="form-step-2">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Contact &amp; Location</h2>
                        <p class="text-body-sm text-on-surface-variant">Ensure we have the correct information to reach the patient in case of emergencies.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Phone Number *</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-outline bg-surface-container text-on-surface-variant text-sm">+63</span>
                                <input name="phone" value="{{ old('phone') }}" required maxlength="30" class="w-full rounded-r-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="917 123 4567" type="tel">
                            </div>
                        </div>
                        <div class="flex flex-col gap-xs" data-maternal-only>
                            <label class="font-label-md text-label-md text-on-surface-variant">Email Address</label>
                            <input name="email" value="{{ old('email') }}" maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="example@email.com" type="email">
                            <p class="text-xs text-on-surface-variant">Becomes the mother's patient portal login.</p>
                        </div>
                        <div class="flex flex-col gap-xs hidden" data-child-only>
                            <label class="font-label-md text-label-md text-on-surface-variant">Patient Portal</label>
                            <p class="text-body-sm text-on-surface-variant">A child doesn't get a login. Link the mother in step 3 and she will see this child in her portal.</p>
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Permanent Address *</label>
                            <textarea name="address" required maxlength="500" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="House number, Street, Purok..." rows="3">{{ old('address') }}</textarea>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Barangay</label>
                            <input name="barangay" class="w-full rounded-lg border-outline bg-surface-container text-on-surface-variant px-sm py-base" readonly value="Bicao" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Emergency Contact Person *</label>
                            <input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" required maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="Name of Relative" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Emergency Contact Phone *</label>
                            <input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" required maxlength="30" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="Phone of emergency contact" type="tel">
                        </div>
                    </div>
                </div>

                <div class="step-transition hidden" id="form-step-3">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Maternal &amp; Child Details</h2>
                        <p class="text-body-sm text-on-surface-variant">Categorize the patient to trigger the appropriate monitoring protocols.</p>
                    </div>
                    <div class="bg-primary-container/10 p-md rounded-xl border border-primary/20 mb-lg">
                        <label class="font-label-md text-label-md text-primary mb-sm block">Registration Type</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                            <div class="p-md rounded-lg border-2 border-primary bg-surface-container-lowest flex items-center gap-md cursor-pointer" onclick="document.getElementById('reg_maternal').click()">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined" data-icon="pregnant_woman">pregnant_woman</span>
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md">Prenatal Care</p>
                                    <p class="text-xs text-on-surface-variant">Expecting mothers</p>
                                </div>
                                <input @checked(old('registration_type', 'Maternal') === 'Maternal') class="ml-auto text-primary" name="registration_type" id="reg_maternal" value="Maternal" type="radio">
                            </div>
                            <div class="p-md rounded-lg border border-outline-variant bg-surface-container-lowest flex items-center gap-md cursor-pointer hover:border-primary/50 transition-colors" onclick="document.getElementById('reg_child').click()">
                                <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                                    <span class="material-symbols-outlined" data-icon="child_care">child_care</span>
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md">Child Health</p>
                                    <p class="text-xs text-on-surface-variant">Pediatric monitoring</p>
                                </div>
                                <input @checked(old('registration_type') === 'Child') class="ml-auto text-primary" name="registration_type" id="reg_child" value="Child" type="radio">
                            </div>
                        </div>
                    </div>

                    <!-- Maternal Specific Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md" id="maternalFields">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Last Menstrual Period (LMP)</label>
                            <input name="lmp" value="{{ old('lmp') }}" data-max-today class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="date">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Number of Previous Pregnancies (Gravida)</label>
                            <input name="gravida" value="{{ old('gravida') }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" min="0" max="30" step="1" placeholder="0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Number of Previous Births (Para)</label>
                            <input name="para" value="{{ old('para') }}" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" min="0" max="30" step="1" placeholder="0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">PhilHealth Number</label>
                            <input name="philhealth_number" value="{{ old('philhealth_number') }}" maxlength="50" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="XX-XXXXXXXXX-X" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Planned Place of Delivery</label>
                            <input name="birth_plan_facility" value="{{ old('birth_plan_facility') }}" maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Carmen District Hospital" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Planned Birth Attendant</label>
                            <input name="birth_plan_attendant" value="{{ old('birth_plan_attendant') }}" maxlength="255" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Midwife, doctor" type="text">
                        </div>
                    </div>

                    <!-- Child Specific Fields (Hidden by default) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md hidden" id="childFields">
                        <div class="flex flex-col gap-xs md:col-span-2">
                            <label class="font-label-md text-label-md text-on-surface-variant">Mother (registered maternal patient)</label>
                            <select name="mother_id" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                                <option value="">&mdash; Not registered / unknown &mdash;</option>
                                @foreach($mothers as $mother)
                                <option value="{{ $mother->id }}" @selected(old('mother_id') == $mother->id)>{{ $mother->last_name }}, {{ $mother->first_name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-on-surface-variant">Linking the mother lets her see this child's growth and vaccines in her patient portal.</p>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Birth Weight (kg)</label>
                            <input name="birth_weight_kg" value="{{ old('birth_weight_kg') }}" step="0.01" min="0.3" max="7" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 3.2" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Birth Height (cm)</label>
                            <input name="birth_height_cm" value="{{ old('birth_height_cm') }}" step="0.1" min="20" max="70" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 50.0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Birth Type</label>
                            <select name="birth_type" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                                @foreach(\App\Services\PatientRegistration::BIRTH_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('birth_type', 'Single') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Delivery Type</label>
                            <select name="delivery_type" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base cursor-pointer">
                                @foreach(\App\Services\PatientRegistration::DELIVERY_TYPES as $type)
                                <option value="{{ $type }}" @selected(old('delivery_type', 'Normal') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <p class="font-label-md text-label-md text-on-surface-variant">Birth Doses</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">
                                @foreach(['has_bcg_at_birth' => 'BCG given at birth', 'has_hepb_at_birth' => 'Hepatitis B given at birth'] as $field => $label)
                                <label class="flex items-center gap-sm p-sm rounded-lg border border-outline-variant cursor-pointer hover:bg-surface-container">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, '1') === '1') class="w-5 h-5 rounded text-primary focus:ring-primary">
                                    <span class="text-sm text-on-surface-variant">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-on-surface-variant">Uncheck a dose the baby did not receive; it will stay on the schedule as due.</p>
                        </div>
                    </div>
                </div>

                <div class="step-transition hidden" id="form-step-4">
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
                        <label class="font-label-md text-label-md text-on-surface-variant mb-xs block">Known Allergies</label>
                        <textarea name="allergies" maxlength="1000" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="List any medicine or food allergies..." rows="3">{{ old('allergies') }}</textarea>
                    </div>
                </div>

                <div class="step-transition hidden" id="form-step-5">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Review &amp; Submit</h2>
                        <p class="text-body-sm text-on-surface-variant">Confirm details and submit registration to the barangay health network.</p>
                    </div>
                    <div class="p-lg bg-surface-container rounded-xl border border-outline-variant/30 text-center">
                        <span class="material-symbols-outlined text-primary text-6xl mb-md">task_alt</span>
                        <h4 class="font-headline-sm text-on-surface">Ready to Register</h4>
                        <p class="text-body-sm text-on-surface-variant mt-xs max-w-md mx-auto">Please click the complete button below to create the patient profile and initialize their monitoring charts.</p>
                    </div>
                    <div class="mt-lg p-md bg-secondary-container/10 border border-secondary/20 rounded-xl flex items-start gap-sm">
                        <span class="material-symbols-outlined text-secondary" data-icon="verified_user">verified_user</span>
                        <div class="flex flex-col gap-xs">
                            <p class="font-label-md text-label-md text-secondary">Data Protection Shield</p>
                            <p class="text-xs text-on-secondary-container">By submitting, you confirm that the patient has consented to the storage of this information for clinical use within the health network.</p>
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
    let currentStep = 1;
    const totalSteps = 5;

    function updateProgress() {
        for (let i = 1; i <= totalSteps; i++) {
            const icon = document.getElementById(`step-icon-${i}`);
            const label = document.getElementById(`step-label-${i}`);

            if (i < currentStep) {
                icon.classList.remove('bg-primary', 'bg-surface-variant', 'text-on-primary', 'text-on-surface-variant');
                icon.classList.add('bg-tertiary', 'text-on-tertiary');
                icon.innerHTML = '<span class="material-symbols-outlined text-sm" style="font-variation-settings: \'FILL\' 0, \'wght\' 700;">check</span>';
                label.classList.remove('text-primary', 'text-on-surface-variant');
                label.classList.add('text-tertiary');
            } else if (i === currentStep) {
                icon.classList.remove('bg-tertiary', 'bg-surface-variant', 'text-on-tertiary', 'text-on-surface-variant');
                icon.classList.add('bg-primary', 'text-on-primary');
                icon.innerHTML = i;
                label.classList.remove('text-tertiary', 'text-on-surface-variant');
                label.classList.add('text-primary');
            } else {
                icon.classList.remove('bg-primary', 'bg-tertiary', 'text-on-primary', 'text-on-tertiary');
                icon.classList.add('bg-surface-variant', 'text-on-surface-variant');
                icon.innerHTML = i;
                label.classList.remove('text-primary', 'text-tertiary');
                label.classList.add('text-on-surface-variant');
            }
        }

        for (let i = 1; i <= totalSteps; i++) {
            const stepEl = document.getElementById(`form-step-${i}`);
            stepEl.classList.toggle('hidden', i !== currentStep);
        }

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        prevBtn.classList.toggle('invisible', currentStep === 1);

        if (currentStep === totalSteps) {
            nextBtn.innerHTML = 'Complete Registration';
            nextBtn.classList.remove('bg-primary');
            nextBtn.classList.add('bg-secondary');
        } else {
            nextBtn.innerHTML = '<span>Next Step</span> <span class="material-symbols-outlined">arrow_forward</span>';
            nextBtn.classList.add('bg-primary');
            nextBtn.classList.remove('bg-secondary');
        }
    }

    const registrationForm = document.getElementById('registrationForm');

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
        if (currentStep < totalSteps) {
            const stepFields = [...document.getElementById(`form-step-${currentStep}`).querySelectorAll('input, select, textarea')];
            if (reportFirstInvalid(stepFields)) {
                return;
            }
            currentStep++;
            updateProgress();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else if (!reportFirstInvalid([...registrationForm.elements])) {
            // requestSubmit (not submit) fires the submit event, so an offline registration is
            // saved on the device by offline.js instead of being lost.
            registrationForm.requestSubmit();
        }
    }

    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            updateProgress();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function goToStep(step) {
        currentStep = step;
        updateProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Show the fields for the chosen registration type. The other type's fields are disabled, so
    // they are neither checked nor sent (e.g. an LMP typed before switching to Child).
    function showTypeFields(type) {
        [
            ...[...document.querySelectorAll('#maternalFields, [data-maternal-only]')].map((el) => [el, 'Maternal']),
            ...[...document.querySelectorAll('#childFields, [data-child-only]')].map((el) => [el, 'Child']),
        ].forEach(([section, fieldsType]) => {
            section.classList.toggle('hidden', type !== fieldsType);
            section.querySelectorAll('input, select, textarea').forEach((field) => {
                field.disabled = type !== fieldsType;
            });
        });
    }

    document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
        radio.addEventListener('change', (e) => showTypeFields(e.target.value));
    });
    const checkedType = () => document.querySelector('input[name="registration_type"]:checked')?.value ?? 'Maternal';
    showTypeFields(checkedType());
    // A reset (after saving offline) puts the type back to its default; match the fields to it.
    registrationForm.addEventListener('reset', () => setTimeout(() => showTypeFields(checkedType())));

    // Dates can't be in the future. Set from this device's clock, since the form may be an offline copy.
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    document.querySelectorAll('input[data-max-today]').forEach((field) => { field.max = today; });

    updateProgress();

    // After the server rejected the form, open the step with the first problem.
    const errorFields = @json($errors->keys());
    if (errorFields.length > 0) {
        // "medical_history.Asthma" is the error key for the field named "medical_history[Asthma]".
        const firstError = [...registrationForm.elements].find((field) =>
            errorFields.some((key) => field.name.split('[')[0] === key.split('.')[0]));
        if (firstError) {
            goToStep(stepOf(firstError));
        }
    }
</script>
@endpush
