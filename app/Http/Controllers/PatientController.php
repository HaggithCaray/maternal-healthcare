<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterPatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\AuditLog;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Models\ChatMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Display a listing of patient records with filtering and KPI summaries.
     */
    public function records(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status');

        $query = Patient::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'All Types') {
            $query->where('registration_type', $type);
        }

        if ($status && $status !== 'All Status') {
            $query->where('status', $status);
        }

        $patients = $query->latest()->get();

        // Calculate KPI stats
        $totalPatients = Patient::count();
        $maternalCases = Patient::where('registration_type', 'Maternal')->count();
        $childRecords = Patient::where('registration_type', 'Child')->count();
        $dueForVisit = Patient::where('status', 'Due for Visit')->count();

        AuditLog::log('view_patient_records', null, [
            'search' => $search,
            'type' => $type,
            'status' => $status,
            'count' => $patients->count(),
        ]);

        return view('records', compact(
            'patients',
            'totalPatients',
            'maternalCases',
            'childRecords',
            'dueForVisit',
            'search',
            'type',
            'status'
        ));
    }

    /**
     * Show registration form or handle patient creation.
     */
    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->store($request);
        }

        $this->authorize('create', Patient::class);

        $mothers = $this->maternalPatients();

        return view('register', compact('mothers'));
    }

    /**
     * Store newly created patient record with secure user provisioning.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Patient::class);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'phone' => 'required|string',
            'email' => ['nullable', 'email', $this->notStaffEmail()],
            'address' => 'required|string',
            'emergency_contact_name' => 'required|string',
            'emergency_contact_phone' => 'required|string',
            'registration_type' => 'required|in:Maternal,Child',
            'mother_id' => ['nullable', $this->maternalPatientRule()],
        ]);

        return DB::transaction(function () use ($request) {
            $userId = null;
            $temporaryPassword = null;
            if ($request->email) {
                $user = User::where('email', $request->email)->first();
                if (!$user) {
                    // Random initial password, shown once to the midwife so the patient can log in
                    $temporaryPassword = $this->generateTemporaryPassword();
                    $user = User::create([
                        'name' => $request->first_name . ' ' . $request->last_name,
                        'email' => $request->email,
                        'password' => Hash::make($temporaryPassword),
                        'role' => 'user',
                    ]);
                }
                $userId = $user->id;
            }

            $patient = Patient::create([
                'user_id' => $userId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'barangay' => 'Bicao',
                'occupation' => $request->occupation,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'registration_type' => $request->registration_type,
                'status' => 'Active',
            ]);

            $dob = Carbon::parse($request->dob);

            if ($request->registration_type === 'Maternal') {
                $lmp = $request->lmp ? Carbon::parse($request->lmp) : null;
                $edd = $lmp ? $lmp->copy()->addDays(280) : null;

                MaternalRecord::create([
                    'patient_id' => $patient->id,
                    'lmp' => $lmp,
                    'edd' => $edd,
                    'gravida' => $request->gravida ?? 1,
                    'para' => $request->para ?? 0,
                    'philhealth_number' => $request->philhealth_number,
                    'medical_history' => $request->medical_history ?? [],
                    'birth_plan' => [
                        'facility' => 'Barangay Bicao Health Station',
                        'attendant' => 'Midwife Elena',
                    ],
                ]);
            } else {
                $child = ChildRecord::create([
                    'patient_id' => $patient->id,
                    'mother_id' => $request->mother_id,
                    'birth_weight_kg' => $request->birth_weight_kg ?? 3.0,
                    'birth_height_cm' => $request->birth_height_cm ?? 50.0,
                    'birth_type' => 'Single',
                    'delivery_type' => 'Normal',
                ]);

                GrowthMeasurement::create([
                    'child_record_id' => $child->id,
                    'date' => $dob->format('Y-m-d'),
                    'age_months' => 0,
                    'weight_kg' => $request->birth_weight_kg ?? 3.0,
                    'height_cm' => $request->birth_height_cm ?? 50.0,
                ]);

                $schedule = [
                    ['BCG', 1, 0],
                    ['Hepatitis B', 1, 0],
                    ['Pentavalent (DPT-HepB-Hib)', 1, 6],
                    ['Pentavalent (DPT-HepB-Hib)', 2, 10],
                    ['Pentavalent (DPT-HepB-Hib)', 3, 14],
                    ['OPV', 1, 6],
                    ['OPV', 2, 10],
                    ['OPV', 3, 14],
                    ['IPV', 1, 14],
                    ['PCV', 1, 6],
                    ['PCV', 2, 10],
                    ['PCV', 3, 14],
                    ['MMR', 1, 39],
                    ['MMR', 2, 52],
                ];

                foreach ($schedule as $vaccine) {
                    Immunization::create([
                        'child_record_id' => $child->id,
                        'vaccine_name' => $vaccine[0],
                        'dose_number' => $vaccine[1],
                        'scheduled_date' => $dob->copy()->addWeeks($vaccine[2])->format('Y-m-d'),
                        'status' => $vaccine[2] === 0 ? 'Given' : 'Scheduled',
                        'given_date' => $vaccine[2] === 0 ? $dob->format('Y-m-d') : null,
                        'administered_by' => $vaccine[2] === 0 ? 'Midwife Elena' : null,
                    ]);
                }
            }

            AuditLog::log('create_patient', $patient, [
                'registration_type' => $patient->registration_type,
                'name' => $patient->first_name . ' ' . $patient->last_name,
            ]);

            $redirect = redirect()->route('records')->with('success', 'Patient registered successfully!');

            if ($temporaryPassword) {
                $redirect->with('portal_credentials', [
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'email' => $request->email,
                    'password' => $temporaryPassword,
                ]);
            }

            return $redirect;
        });
    }

    /**
     * Show the edit patient form.
     */
    public function edit(Patient $patient)
    {
        $this->authorize('update', $patient);

        $patient->load(['maternalRecord', 'childRecord', 'user']);
        $mothers = $this->maternalPatients();

        AuditLog::log('view_edit_patient', $patient);

        return view('patient.edit', compact('patient', 'mothers'));
    }

    /**
     * Update the specified patient in storage.
     */
    public function update(Request $request, Patient $patient)
    {
        $this->authorize('update', $patient);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'emergency_contact_name' => 'required|string',
            'emergency_contact_phone' => 'required|string',
            'status' => 'required|string|in:Active,Due for Visit,High Risk,Completed',
            'mother_id' => ['nullable', $this->maternalPatientRule()],
        ]);

        DB::transaction(function () use ($request, $patient) {
            $patient->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'occupation' => $request->occupation,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'status' => $request->status,
            ]);

            if ($patient->registration_type === 'Maternal' && $patient->maternalRecord) {
                $lmp = $request->lmp ? Carbon::parse($request->lmp) : null;
                $edd = $lmp ? $lmp->copy()->addDays(280) : null;

                $medicalHistory = [];
                foreach (['Hypertension', 'Diabetes', 'Asthma', 'Heart Disease', 'Anemia', 'Multiple Births'] as $condition) {
                    if ($request->boolean("medical_history.{$condition}")) {
                        $medicalHistory[] = $condition;
                    }
                }

                $patient->maternalRecord->update([
                    'lmp' => $lmp,
                    'edd' => $edd,
                    'gravida' => $request->gravida,
                    'para' => $request->para,
                    'philhealth_number' => $request->philhealth_number,
                    'allergies' => $request->allergies,
                    'medical_history' => $medicalHistory,
                ]);
            }

            if ($patient->registration_type === 'Child' && $patient->childRecord) {
                if ($request->has('mother_id')) {
                    $patient->childRecord->mother_id = $request->mother_id;
                }

                $patient->childRecord->update([
                    'birth_weight_kg' => $request->birth_weight_kg,
                    'birth_height_cm' => $request->birth_height_cm,
                    'head_circumference_cm' => $request->head_circumference_cm,
                    'birth_type' => $request->birth_type,
                    'delivery_type' => $request->delivery_type,
                    'has_newborn_screening' => $request->boolean('has_newborn_screening'),
                    'has_hearing_screening' => $request->boolean('has_hearing_screening'),
                    'has_eye_prophylaxis' => $request->boolean('has_eye_prophylaxis'),
                    'has_vitamin_k' => $request->boolean('has_vitamin_k'),
                    'has_bcg_at_birth' => $request->boolean('has_bcg_at_birth'),
                    'has_hepb_at_birth' => $request->boolean('has_hepb_at_birth'),
                ]);
            }

            AuditLog::log('update_patient', $patient);
        });

        return redirect()->route('records')->with('success', 'Patient information updated successfully!');
    }

    /**
     * Create (or reset) the patient's portal login and show the temporary password once.
     */
    public function resetPortalPassword(Patient $patient)
    {
        $this->authorize('update', $patient);

        $temporaryPassword = $this->generateTemporaryPassword();
        $user = $patient->user;

        if ($user && ! $user->isUser()) {
            return back()->with('error', 'This patient is linked to a staff account; its password cannot be reset here.');
        }

        if (! $user) {
            if (! $patient->email) {
                return back()->with('error', 'Add an email address for this patient first, then create the portal account.');
            }

            if (User::where('email', $patient->email)->exists()) {
                return back()->with('error', 'That email address is already used by another account.');
            }

            $user = User::create([
                'name' => $patient->first_name . ' ' . $patient->last_name,
                'email' => $patient->email,
                'password' => Hash::make($temporaryPassword),
                'role' => 'user',
            ]);
            $patient->update(['user_id' => $user->id]);
        } else {
            $user->update(['password' => Hash::make($temporaryPassword)]);
        }

        AuditLog::log('reset_patient_portal_password', $patient);

        return back()->with('portal_credentials', [
            'name' => $patient->first_name . ' ' . $patient->last_name,
            'email' => $user->email,
            'password' => $temporaryPassword,
        ]);
    }

    /**
     * Maternal patients a child record can be linked to.
     */
    protected function maternalPatients()
    {
        return Patient::where('registration_type', 'Maternal')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    /**
     * Validation rule: the given id must be a registered maternal patient.
     */
    protected function maternalPatientRule()
    {
        return Rule::exists('patients', 'id')->where('registration_type', 'Maternal');
    }

    /**
     * Validation rule: a patient's email must not belong to a staff account,
     * otherwise the patient record would be linked to (and log in as) that staff user.
     */
    protected function notStaffEmail(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (User::where('email', $value)->where('role', '!=', 'user')->exists()) {
                $fail('This email belongs to a staff account and cannot be used for a patient.');
            }
        };
    }

    /**
     * Readable temporary password (no symbols) the midwife can hand to the patient.
     */
    protected function generateTemporaryPassword(): string
    {
        return Str::password(10, symbols: false);
    }

    /**
     * Display patient portal dashboard.
     */
    public function patientPortal()
    {
        $user = auth()->user();
        $mother = Patient::where('user_id', $user->id)->first();
        
        $childrenCount = 0;
        $nextVaccineDate = 'N/A';
        $nextVaccineName = 'None';
        $overdueVaccinesCount = 0;
        $unreadMessagesCount = 0;

        if ($mother) {
            $children = ChildRecord::where('mother_id', $mother->id)->get();
            $childrenCount = $children->count();

            $upcoming = Immunization::whereIn('child_record_id', $children->pluck('id'))
                                     ->where('status', 'Scheduled')
                                     ->orderBy('scheduled_date', 'asc')
                                     ->first();
            if ($upcoming) {
                $nextVaccineDate = Carbon::parse($upcoming->scheduled_date)->format('M d');
                $nextVaccineName = $upcoming->vaccine_name . ' · ' . ($upcoming->childRecord->patient->first_name ?? 'Child');
            }

            $overdueVaccinesCount = Immunization::whereIn('child_record_id', $children->pluck('id'))
                                                 ->where('status', 'Scheduled')
                                                 ->where('scheduled_date', '<', Carbon::now())
                                                 ->count();
        }

        $midwife = User::where('role', 'admin')->first();
        if ($midwife) {
            $unreadMessagesCount = ChatMessage::where('sender_id', $midwife->id)
                                             ->where('receiver_id', $user->id)
                                             ->where('is_read', false)
                                             ->count();
        }

        AuditLog::log('view_patient_portal', $mother);

        return view('patient.portal', compact(
            'childrenCount',
            'nextVaccineDate',
            'nextVaccineName',
            'overdueVaccinesCount',
            'unreadMessagesCount'
        ));
    }
}
