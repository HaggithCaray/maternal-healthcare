@extends('layouts.app')

@section('title', 'Patient Registration')

@section('content')
<header class="flex flex-col md:flex-row md:items-end justify-between gap-md">
    <div>
        <h1 class="font-headline-lg text-headline-lg text-primary">Patient Registration</h1>
        <p class="text-on-surface-variant max-w-2xl mt-xs">Please complete all steps to register a mother or child into the Maternal Healthcare monitoring system. This information is kept strictly confidential.</p>
    </div>
    <button class="flex items-center justify-center gap-sm px-md py-sm rounded-lg border border-secondary text-secondary font-label-md text-label-md hover:bg-secondary/5 transition-all whitespace-nowrap">
        <span class="material-symbols-outlined" data-icon="save">save</span>
        Save Draft
    </button>
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
                    [5, 'Document Upload', 'Verify identity'],
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
            <form method="POST" action="{{ route('register') }}" class="flex-grow" id="registrationForm">
                @csrf
                <div class="step-transition" id="form-step-1">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Personal Information</h2>
                        <p class="text-body-sm text-on-surface-variant">Register a new patient to the barangay database.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">First Name *</label>
                            <input name="first_name" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Maria" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Last Name *</label>
                            <input name="last_name" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Santos" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Date of Birth *</label>
                            <input name="dob" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="date">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Gender *</label>
                            <div class="flex gap-md mt-xs">
                                <label class="flex items-center gap-xs cursor-pointer">
                                    <input name="gender" value="Female" checked class="text-primary focus:ring-primary" type="radio">
                                    <span>Female</span>
                                </label>
                                <label class="flex items-center gap-xs cursor-pointer">
                                    <input name="gender" value="Male" class="text-primary focus:ring-primary" type="radio">
                                    <span>Male</span>
                                </label>
                            </div>
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Occupation</label>
                            <input name="occupation" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. Housewife, Teacher, Vendor" type="text">
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
                                <input name="phone" required class="w-full rounded-r-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="917 123 4567" type="tel">
                            </div>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Email Address</label>
                            <input name="email" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="example@email.com" type="email">
                        </div>
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Permanent Address *</label>
                            <textarea name="address" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="House number, Street, Purok..." rows="3"></textarea>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Barangay</label>
                            <input name="barangay" class="w-full rounded-lg border-outline bg-surface-container text-on-surface-variant px-sm py-base" readonly value="Bicao" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Emergency Contact Person *</label>
                            <input name="emergency_contact_name" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="Name of Relative" type="text">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Emergency Contact Phone *</label>
                            <input name="emergency_contact_phone" required class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="Phone of emergency contact" type="tel">
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
                                <input checked class="ml-auto text-primary" name="registration_type" id="reg_maternal" value="Maternal" type="radio">
                            </div>
                            <div class="p-md rounded-lg border border-outline-variant bg-surface-container-lowest flex items-center gap-md cursor-pointer hover:border-primary/50 transition-colors" onclick="document.getElementById('reg_child').click()">
                                <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center text-secondary">
                                    <span class="material-symbols-outlined" data-icon="child_care">child_care</span>
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md">Child Health</p>
                                    <p class="text-xs text-on-surface-variant">Pediatric monitoring</p>
                                </div>
                                <input class="ml-auto text-primary" name="registration_type" id="reg_child" value="Child" type="radio">
                            </div>
                        </div>
                    </div>

                    <!-- Maternal Specific Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md" id="maternalFields">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Last Menstrual Period (LMP)</label>
                            <input name="lmp" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" type="date">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Number of Previous Pregnancies (Gravida)</label>
                            <input name="gravida" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" min="0" placeholder="0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Number of Previous Births (Para)</label>
                            <input name="para" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" min="0" placeholder="0" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">PhilHealth Number</label>
                            <input name="philhealth_number" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="XX-XXXXXXXXX-X" type="text">
                        </div>
                    </div>

                    <!-- Child Specific Fields (Hidden by default) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md hidden" id="childFields">
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Birth Weight (kg)</label>
                            <input name="birth_weight_kg" step="0.01" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 3.2" type="number">
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-label-md text-on-surface-variant">Birth Height (cm)</label>
                            <input name="birth_height_cm" step="0.1" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="e.g. 50.0" type="number">
                        </div>
                    </div>
                </div>

                <div class="step-transition hidden" id="form-step-4">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Medical History</h2>
                        <p class="text-body-sm text-on-surface-variant">Check all that apply to the patient's history.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-md">
                        @foreach(['Hypertension', 'Diabetes', 'Asthma', 'Heart Disease', 'Anemia', 'Multiple Births'] as $i => $condition)
                        <div class="flex items-center p-sm rounded-lg border border-outline-variant hover:bg-surface-container transition-colors cursor-pointer">
                            <input name="medical_history[{{ $condition }}]" value="1" class="w-5 h-5 rounded text-primary focus:ring-primary mr-sm" id="hist_{{ $i + 1 }}" type="checkbox">
                            <label class="text-on-surface-variant text-sm cursor-pointer" for="hist_{{ $i + 1 }}">{{ $condition }}</label>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-lg">
                        <label class="font-label-md text-label-md text-on-surface-variant mb-xs block">Known Allergies</label>
                        <textarea name="allergies" class="w-full rounded-lg border-outline focus:ring-2 focus:ring-primary focus:border-primary px-sm py-base" placeholder="List any medicine or food allergies..." rows="3"></textarea>
                    </div>
                </div>

                <div class="step-transition hidden" id="form-step-5">
                    <div class="mb-lg">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-xs">Document/Verification Complete</h2>
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
                            <p class="text-xs text-on-secondary-container">By submitting, you confirm that the patient has consented to the storage of these documents for clinical use within the health network.</p>
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
                    <button class="hidden md:flex items-center gap-xs text-secondary font-label-md text-label-md hover:underline decoration-2 underline-offset-4 whitespace-nowrap">Cancel Registration</button>
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
                <h4 class="font-label-md text-label-md text-on-surface">Did you know?</h4>
                <p class="text-body-sm text-on-surface-variant">You can use your tablet's camera to scan and auto-fill PhilHealth numbers in Step 3. Look for the scanner icon next to the input field.</p>
            </div>
            <button class="md:ml-auto px-md py-xs rounded-full border border-secondary text-secondary text-xs font-bold hover:bg-secondary/5">LEARN MORE</button>
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

    function nextStep() {
        if (currentStep < totalSteps) {
            currentStep++;
            updateProgress();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            document.getElementById('registrationForm').submit();
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

    // Toggle registration type fields
    document.querySelectorAll('input[name="registration_type"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const type = e.target.value;
            document.getElementById('maternalFields').classList.toggle('hidden', type !== 'Maternal');
            document.getElementById('childFields').classList.toggle('hidden', type !== 'Child');
        });
    });

    updateProgress();
</script>
@endpush
