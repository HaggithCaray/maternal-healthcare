<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Models\MaternalRecord;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\GrowthMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PageController extends Controller
{
    public function records(Request $request)
    {
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

    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
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
                'registration_type' => 'required|in:Maternal,Child',
            ]);

            // 1. Create Patient User if email is provided
            $userId = null;
            if ($request->email) {
                $user = User::where('email', $request->email)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $request->first_name . ' ' . $request->last_name,
                        'email' => $request->email,
                        'password' => Hash::make('password'),
                        'role' => 'user',
                    ]);
                }
                $userId = $user->id;
            }

            // 2. Save Patient Profile
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

            // 3. Create Specific Records
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
                    'birth_weight_kg' => $request->birth_weight_kg ?? 3.0,
                    'birth_height_cm' => $request->birth_height_cm ?? 50.0,
                    'birth_type' => 'Single',
                    'delivery_type' => 'Normal',
                ]);

                // Create initial growth point at birth
                GrowthMeasurement::create([
                    'child_record_id' => $child->id,
                    'date' => $dob->format('Y-m-d'),
                    'age_months' => 0,
                    'weight_kg' => $request->birth_weight_kg ?? 3.0,
                    'height_cm' => $request->birth_height_cm ?? 50.0,
                    'status' => 'Normal',
                ]);

                // Generate standard Booklet Immunization Schedule based on birth date
                $schedule = [
                    ['BCG', 1, 0], // At birth
                    ['Hepatitis B', 1, 0], // At birth
                    ['Pentavalent (DPT-HepB-Hib)', 1, 6], // 1.5 months (6 weeks)
                    ['Pentavalent (DPT-HepB-Hib)', 2, 10], // 2.5 months (10 weeks)
                    ['Pentavalent (DPT-HepB-Hib)', 3, 14], // 3.5 months (14 weeks)
                    ['OPV', 1, 6],
                    ['OPV', 2, 10],
                    ['OPV', 3, 14],
                    ['IPV', 1, 14],
                    ['PCV', 1, 6],
                    ['PCV', 2, 10],
                    ['PCV', 3, 14],
                    ['MMR', 1, 39], // 9 months (39 weeks)
                    ['MMR', 2, 52], // 1 year (52 weeks)
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

            return redirect()->route('records')->with('success', 'Patient registered successfully!');
        }

        return view('register');
    }
}
