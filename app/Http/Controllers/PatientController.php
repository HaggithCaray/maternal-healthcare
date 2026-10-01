<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Models\ChatMessage;
use App\Services\PatientRegistration;
use App\Services\PrenatalAssessment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            return $this->store($request, app(PatientRegistration::class));
        }

        $this->authorize('create', Patient::class);

        $mothers = $this->maternalPatients();

        return view('register', compact('mothers'));
    }

    /**
     * Store newly created patient record with secure user provisioning.
     */
    public function store(Request $request, PatientRegistration $registration)
    {
        $this->authorize('create', Patient::class);

        $data = $request->validate(PatientRegistration::rules());
        ['patient' => $patient, 'temporaryPassword' => $temporaryPassword] = $registration->register($data);

        AuditLog::log('create_patient', $patient, [
            'registration_type' => $patient->registration_type,
            'name' => $patient->full_name,
        ]);

        $redirect = redirect()->route('records')->with('success', 'Patient registered successfully!');

        if ($temporaryPassword) {
            $redirect->with('portal_credentials', [
                'name' => $patient->full_name,
                'email' => $patient->email,
                'password' => $temporaryPassword,
            ]);
        }

        return $redirect;
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

        // Same rules as registration, so an edit can't store what the form would have refused.
        $request->validate(Arr::except(PatientRegistration::rules(), ['registration_type', 'barangay', 'email']) + [
            // The email is also the portal login, so it must not belong to another account.
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($patient->user_id)],
            'status' => 'required|string|in:Active,Due for Visit,High Risk,Completed',
            'head_circumference_cm' => 'nullable|numeric|min:20|max:60',
            'has_newborn_screening' => 'nullable|boolean',
            'has_hearing_screening' => 'nullable|boolean',
            'has_eye_prophylaxis' => 'nullable|boolean',
            'has_vitamin_k' => 'nullable|boolean',
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

                $patient->maternalRecord->update([
                    'lmp' => $lmp,
                    'edd' => $edd,
                    'gravida' => $request->gravida ?? $patient->maternalRecord->gravida,
                    'para' => $request->para ?? $patient->maternalRecord->para,
                    'philhealth_number' => $request->philhealth_number,
                    'allergies' => $request->allergies,
                    'medical_history' => MaternalRecord::normalizeConditions($request->input('medical_history', [])),
                    'birth_plan' => array_filter([
                        'facility' => $request->birth_plan_facility,
                        'attendant' => $request->birth_plan_attendant,
                    ]) ?: null,
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
                    'birth_type' => $request->birth_type ?? $patient->childRecord->birth_type,
                    'delivery_type' => $request->delivery_type ?? $patient->childRecord->delivery_type,
                    'has_newborn_screening' => $request->boolean('has_newborn_screening'),
                    'has_hearing_screening' => $request->boolean('has_hearing_screening'),
                    'has_eye_prophylaxis' => $request->boolean('has_eye_prophylaxis'),
                    'has_vitamin_k' => $request->boolean('has_vitamin_k'),
                    'has_bcg_at_birth' => $request->boolean('has_bcg_at_birth'),
                    'has_hepb_at_birth' => $request->boolean('has_hepb_at_birth'),
                ]);

                // Keep the BCG / Hepatitis B birth doses in line with the "at birth" checkboxes.
                $patient->childRecord->syncBirthDoses();
            }

            // Keep the portal login in step with the patient record — only when this patient owns
            // the login (a child registered with the mother's email shares her account).
            if ($patient->user && $patient->user->isUser() && $patient->user->patient?->is($patient)) {
                $patient->user->update(array_filter([
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                    'email' => $request->email,
                ]));
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

        $temporaryPassword = User::temporaryPassword();
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
            $user->signOutOtherSessions();
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
     * Display patient portal dashboard.
     */
    public function patientPortal()
    {
        $user = auth()->user();
        $mother = Patient::with('maternalRecord')->where('user_id', $user->id)->first();
        $today = Carbon::today();

        $children = collect();
        $nextVaccineDate = 'N/A';
        $nextVaccineName = 'None';
        $overdueVaccinesCount = 0;
        $unreadMessagesCount = 0;
        $activity = collect();

        if ($mother) {
            $children = ChildRecord::with(['patient', 'immunizations'])->where('mother_id', $mother->id)->get();
            $childRecordIds = $children->pluck('id');

            $scheduled = Immunization::with('childRecord.patient')
                ->whereIn('child_record_id', $childRecordIds)
                ->where('status', 'Scheduled');

            // Next dose still ahead; doses whose date has passed are counted as overdue instead.
            $upcoming = (clone $scheduled)->whereDate('scheduled_date', '>=', $today)->orderBy('scheduled_date')->first();
            if ($upcoming) {
                $nextVaccineDate = Carbon::parse($upcoming->scheduled_date)->format('M d');
                $nextVaccineName = $upcoming->vaccine_name . ' · ' . ($upcoming->childRecord->patient->first_name ?? 'Child');
            }

            $overdueVaccinesCount = (clone $scheduled)->whereDate('scheduled_date', '<', $today)->count();

            $activity = $this->familyActivity($mother, $childRecordIds);
        }
        $childrenCount = $children->count();

        $midwife = User::careTeamContact();
        $recentMessages = collect();
        if ($midwife) {
            $unreadMessagesCount = ChatMessage::where('sender_id', $midwife->id)
                                             ->where('receiver_id', $user->id)
                                             ->where('is_read', false)
                                             ->count();

            $recentMessages = ChatMessage::with('sender:id,name')
                ->where(fn ($q) => $q->where('sender_id', $user->id)->where('receiver_id', $midwife->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $midwife->id)->where('receiver_id', $user->id))
                ->latest('id')
                ->take(3)
                ->get();
        }

        AuditLog::log('view_patient_portal', $mother);

        return view('patient.portal', compact(
            'mother',
            'children',
            'childrenCount',
            'nextVaccineDate',
            'nextVaccineName',
            'overdueVaccinesCount',
            'unreadMessagesCount',
            'recentMessages',
            'midwife',
            'activity'
        ));
    }

    /**
     * Latest events on a family's records (vaccines, growth checks, prenatal visits), newest first.
     */
    protected function familyActivity(Patient $mother, $childRecordIds)
    {
        $vaccines = Immunization::with('childRecord.patient')
            ->whereIn('child_record_id', $childRecordIds)
            ->where('status', 'Given')
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn ($i) => [
                'at' => $i->updated_at,
                'text' => "{$i->vaccine_name} dose {$i->dose_number} given to",
                'name' => $i->childRecord?->patient?->first_name,
                'level' => 'good',
            ]);

        $growth = GrowthMeasurement::with('childRecord.patient')
            ->whereIn('child_record_id', $childRecordIds)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($g) => [
                'at' => $g->created_at,
                'text' => 'Weight and height recorded for',
                'name' => $g->childRecord?->patient?->first_name,
                'level' => 'info',
            ]);

        $visits = $mother->maternalRecord
            ? $mother->maternalRecord->checkups()->reorder()->latest()->take(5)->get()->map(fn ($v) => [
                'at' => $v->created_at,
                'text' => 'Prenatal visit logged',
                'name' => $v->age_of_gestation ? "({$v->age_of_gestation})" : '',
                'level' => $v->status === PrenatalAssessment::HIGH_RISK ? 'alert' : 'info',
            ])
            : collect();

        return $vaccines->concat($growth)->concat($visits)->sortByDesc('at')->take(5)->values();
    }
}
