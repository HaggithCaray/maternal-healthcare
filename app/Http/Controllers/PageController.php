<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Models\MaternalRecord;
use App\Models\MaternalCheckup;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\GrowthMeasurement;
use App\Models\SmsMessage;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SmsService;
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
                        'password' => Hash::make(\Illuminate\Support\Str::random(16)),
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

    public function edit(Patient $patient)
    {
        $patient->load(['maternalRecord', 'childRecord']);
        return view('patient.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
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
        });

        return redirect()->route('records')->with('success', 'Patient information updated successfully!');
    }

    public function maternal(Request $request)
    {
        $patient = null;
        if (auth()->user()->role === 'user') {
            $patient = Patient::where('user_id', auth()->user()->id)
                              ->where('registration_type', 'Maternal')
                              ->first();
        } else {
            $id = $request->query('id');
            if ($id) {
                $patient = Patient::find($id);
            } else {
                $patient = Patient::where('registration_type', 'Maternal')->first();
            }
        }

        if (!$patient) {
            return redirect()->route('dashboard')->with('error', 'Maternal patient record not found.');
        }

        $record = $patient->maternalRecord;
        $checkups = $record ? $record->checkups : collect();

        // Simulated checkup submission
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            $request->validate([
                'weight_kg' => 'required|numeric',
                'bp' => 'required|string',
                'fetal_heart_rate' => 'required|integer',
                'notes' => 'nullable|string',
            ]);

            $visitNumber = $checkups->count() + 1;
            $weeks = 4 * $visitNumber; // simple calculation for demo

            MaternalCheckup::create([
                'maternal_record_id' => $record->id,
                'visit_number' => $visitNumber,
                'date' => Carbon::now()->format('Y-m-d'),
                'weight_kg' => $request->weight_kg,
                'bp' => $request->bp,
                'age_of_gestation' => "{$weeks}w 0d",
                'fetal_heart_rate' => $request->fetal_heart_rate,
                'attendant' => auth()->user()->name,
                'status' => 'Healthy',
                'notes' => $request->notes,
                'next_visit_date' => Carbon::now()->addWeeks(4)->format('Y-m-d'),
            ]);

            return back()->with('success', 'Checkup visit log added successfully!');
        }

        $view = auth()->user()->role === 'user' ? 'patient.maternal' : 'maternal';
        return view($view, compact('patient', 'record', 'checkups'));
    }

    public function growth(Request $request)
    {
        $patient = null;
        if (auth()->user()->role === 'user') {
            // Find children linked to the logged in mother
            $mother = Patient::where('user_id', auth()->user()->id)->first();
            if ($mother) {
                $childRecord = ChildRecord::where('mother_id', $mother->id)->first();
                $patient = $childRecord ? $childRecord->patient : null;
            }
        } else {
            $id = $request->query('id');
            if ($id) {
                $patient = Patient::find($id);
            } else {
                $patient = Patient::where('registration_type', 'Child')->first();
            }
        }

        if (!$patient) {
            return redirect()->route('dashboard')->with('error', 'Child record not found.');
        }

        $childRecord = $patient->childRecord;
        $growthMeasurements = $childRecord ? $childRecord->growthMeasurements : collect();

        // Handle growth entry submission
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            $request->validate([
                'weight_kg' => 'required|numeric',
                'height_cm' => 'required|numeric',
            ]);

            $ageMonths = Carbon::parse($patient->dob)->diffInMonths(Carbon::now());

            GrowthMeasurement::create([
                'child_record_id' => $childRecord->id,
                'date' => Carbon::now()->format('Y-m-d'),
                'age_months' => $ageMonths,
                'weight_kg' => $request->weight_kg,
                'height_cm' => $request->height_cm,
                'status' => 'Normal',
            ]);

            return back()->with('success', 'Growth measurement log added successfully!');
        }

        $view = auth()->user()->role === 'user' ? 'patient.growth' : 'growth';
        return view($view, compact('patient', 'childRecord', 'growthMeasurements'));
    }

    public function immunization(Request $request)
    {
        $patient = null;
        if (auth()->user()->role === 'user') {
            $mother = Patient::where('user_id', auth()->user()->id)->first();
            if ($mother) {
                $childRecord = ChildRecord::where('mother_id', $mother->id)->first();
                $patient = $childRecord ? $childRecord->patient : null;
            }
        } else {
            $id = $request->query('id');
            if ($id) {
                $patient = Patient::find($id);
            } else {
                $patient = Patient::where('registration_type', 'Child')->first();
            }
        }

        if (!$patient) {
            return redirect()->route('dashboard')->with('error', 'Child immunization record not found.');
        }

        $childRecord = $patient->childRecord;
        $immunizations = $childRecord ? $childRecord->immunizations : collect();

        // Handle updating vaccine dose given status
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            $immunization = Immunization::find($request->immunization_id);
            if ($immunization) {
                $immunization->update([
                    'status' => 'Given',
                    'given_date' => Carbon::now()->format('Y-m-d'),
                    'administered_by' => auth()->user()->name,
                    'remarks' => $request->remarks ?? 'Regular schedule dose',
                ]);
                return back()->with('success', 'Vaccine marked as administered!');
            }
        }

        $view = auth()->user()->role === 'user' ? 'patient.immunization' : 'immunization';
        return view($view, compact('patient', 'childRecord', 'immunizations'));
    }

    public function sms(Request $request, SmsService $smsService)
    {
        // Outbound SMS history log
        $smsMessages = SmsMessage::with('patient')->latest()->get();
        $patients = Patient::where('phone', '!=', null)->get();

        $gatewaySettings = $smsService->getSettings();
        $gatewayStatus = $smsService->checkStatus();

        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'message' => 'required|string',
            ]);

            $p = Patient::find($request->patient_id);

            $result = $smsService->sendSms($p->phone, $request->message);

            SmsMessage::create([
                'patient_id' => $p->id,
                'phone_number' => $p->phone,
                'message' => $request->message,
                'status' => $result['success'] ? 'Sent' : 'Failed',
                'sent_at' => $result['success'] ? Carbon::now() : null,
                'type' => 'Manual',
            ]);

            if ($result['success']) {
                return back()->with('success', 'SMS message sent successfully!');
            } else {
                return back()->with('error', 'Failed to send SMS: ' . $result['error']);
            }
        }

        return view('sms', compact('smsMessages', 'patients', 'gatewaySettings', 'gatewayStatus'));
    }

    public function updateSmsSettings(Request $request, SmsService $smsService)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'url' => 'required|url',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        $success = $smsService->saveSettings(
            $request->url,
            $request->username ?? '',
            $request->password ?? ''
        );

        if ($success) {
            return back()->with('success', 'SMS gateway settings updated successfully!');
        }

        return back()->with('error', 'Failed to update SMS gateway settings.');
    }

    public function testSmsGatewayConnection(SmsService $smsService)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $status = $smsService->checkStatus();
        
        return response()->json([
            'success' => true,
            'status' => $status ? 'online' : 'offline',
        ]);
    }

    public function messaging(Request $request)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            // Health Worker view: list patient users and active chats
            $patients = User::where('role', 'user')->get();
            $activePatientId = $request->query('chat_user_id') ?? ($patients->first()?->id ?? null);
            
            $activeChatUser = $activePatientId ? User::find($activePatientId) : null;
            
            $messages = collect();
            if ($activeChatUser) {
                $messages = ChatMessage::where(function ($q) use ($user, $activeChatUser) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $activeChatUser->id);
                })->orWhere(function ($q) use ($user, $activeChatUser) {
                    $q->where('sender_id', $activeChatUser->id)->where('receiver_id', $user->id);
                })->orderBy('created_at', 'asc')->get();

                // Mark unread messages as read
                $unreadCount = ChatMessage::where('sender_id', $activeChatUser->id)
                           ->where('receiver_id', $user->id)
                           ->where('is_read', false)
                           ->update(['is_read' => true]);

                // Notify the sender that their messages have been read
                if ($unreadCount > 0) {
                    $conversationId = min($user->id, $activeChatUser->id) . '-' . max($user->id, $activeChatUser->id);
                    event(new \App\Events\MessageRead($conversationId, $user->id));
                }
            }

            if ($request->isMethod('post')) {

                $request->validate([
                    'message' => 'required_without:file|nullable|string',
                    'receiver_id' => 'required|exists:users,id',
                    'file' => 'nullable|file|max:20480',
                ]);

                $attachmentPath = null;
                $attachmentName = null;
                $attachmentType = null;

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $attachmentPath = $file->store('attachments', 'public');
                    $attachmentName = $file->getClientOriginalName();
                    $attachmentType = $file->getMimeType();
                }

                $newMessage = ChatMessage::create([
                    'sender_id' => $user->id,
                    'receiver_id' => $request->receiver_id,
                    'message' => $request->message,
                    'is_read' => false,
                    'attachment_path' => $attachmentPath,
                    'attachment_name' => $attachmentName,
                    'attachment_type' => $attachmentType,
                ]);

                event(new \App\Events\MessageSent($newMessage, min($user->id, $request->receiver_id) . '-' . max($user->id, $request->receiver_id)));

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $newMessage,
                    ]);
                }

                return redirect()->route('messaging', ['chat_user_id' => $request->receiver_id]);
            }

            return view('messaging', compact('patients', 'activeChatUser', 'messages'));
        } else {
            // Patient View: communicates only with admin/midwife
            $midwife = User::where('role', 'admin')->first();
            
            $messages = collect();
            if ($midwife) {
                $messages = ChatMessage::where(function ($q) use ($user, $midwife) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $midwife->id);
                })->orWhere(function ($q) use ($user, $midwife) {
                    $q->where('sender_id', $midwife->id)->where('receiver_id', $user->id);
                })->orderBy('created_at', 'asc')->get();

                // Mark unread as read
                $unreadCount = ChatMessage::where('sender_id', $midwife->id)
                           ->where('receiver_id', $user->id)
                           ->where('is_read', false)
                           ->update(['is_read' => true]);

                // Notify the sender that their messages have been read
                if ($unreadCount > 0) {
                    $conversationId = min($user->id, $midwife->id) . '-' . max($user->id, $midwife->id);
                    event(new \App\Events\MessageRead($conversationId, $user->id));
                }
            }

            if ($request->isMethod('post')) {
                $request->validate([
                    'message' => 'required_without:file|nullable|string',
                    'file' => 'nullable|file|max:20480',
                ]);

                if ($midwife) {
                    $attachmentPath = null;
                    $attachmentName = null;
                    $attachmentType = null;

                    if ($request->hasFile('file')) {
                        $file = $request->file('file');
                        $attachmentPath = $file->store('attachments', 'public');
                        $attachmentName = $file->getClientOriginalName();
                        $attachmentType = $file->getMimeType();
                    }

                    $newMessage = ChatMessage::create([
                        'sender_id' => $user->id,
                        'receiver_id' => $midwife->id,
                        'message' => $request->message,
                        'is_read' => false,
                        'attachment_path' => $attachmentPath,
                        'attachment_name' => $attachmentName,
                        'attachment_type' => $attachmentType,
                    ]);
                    
                    event(new \App\Events\MessageSent($newMessage, min($user->id, $midwife->id) . '-' . max($user->id, $midwife->id)));

                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => true,
                            'message' => $newMessage,
                        ]);
                    }
                }

                return redirect()->route('messaging');
            }

            return view('patient.messaging', compact('midwife', 'messages'));
        }
    }

    public function reports()
    {
        $totalPatients = Patient::count();
        $maternalCases = Patient::where('registration_type', 'Maternal')->count();
        $childRecords = Patient::where('registration_type', 'Child')->count();

        // Get monthly registrations (mocking trends based on created_at dates)
        $registrations = Patient::selectRaw('strftime("%m", created_at) as month, count(*) as count')
                                ->groupBy('month')
                                ->orderBy('month')
                                ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = array_fill_keys($months, 0);

        foreach ($registrations as $reg) {
            $monthIndex = intval($reg->month) - 1;
            if ($monthIndex >= 0 && $monthIndex < 12) {
                $monthlyData[$months[$monthIndex]] = $reg->count;
            }
        }

        // Vaccine compliance stats
        $totalDoses = Immunization::count();
        $administeredDoses = Immunization::where('status', 'Given')->count();
        $complianceRate = $totalDoses > 0 ? round(($administeredDoses / $totalDoses) * 100) : 0;

        return view('reports', compact(
            'totalPatients',
            'maternalCases',
            'childRecords',
            'monthlyData',
            'complianceRate'
        ));
    }

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

            // Find next vaccine due
            $upcoming = Immunization::whereIn('child_record_id', $children->pluck('id'))
                                     ->where('status', 'Scheduled')
                                     ->orderBy('scheduled_date', 'asc')
                                     ->first();
            if ($upcoming) {
                $nextVaccineDate = Carbon::parse($upcoming->scheduled_date)->format('M d');
                $nextVaccineName = $upcoming->vaccine_name . ' · ' . ($upcoming->childRecord->patient->first_name ?? 'Child');
            }

            // Find overdue vaccines count
            $overdueVaccinesCount = Immunization::whereIn('child_record_id', $children->pluck('id'))
                                                 ->where('status', 'Scheduled')
                                                 ->where('scheduled_date', '<', Carbon::now())
                                                 ->count();
        }

        // Count unread messages from midwife
        $midwife = User::where('role', 'admin')->first();
        if ($midwife) {
            $unreadMessagesCount = ChatMessage::where('sender_id', $midwife->id)
                                             ->where('receiver_id', $user->id)
                                             ->where('is_read', false)
                                             ->count();
        }

        return view('patient.portal', compact(
            'childrenCount',
            'nextVaccineDate',
            'nextVaccineName',
            'overdueVaccinesCount',
            'unreadMessagesCount'
        ));
    }

    public function admin()
    {
        $users = User::all();
        return view('admin', compact('users'));
    }
}
