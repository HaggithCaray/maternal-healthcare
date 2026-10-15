<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\PostpartumVisit;
use App\Services\PostpartumAssessment;
use App\Services\PrenatalAssessment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MaternalRecordController extends Controller
{
    /**
     * Display maternal health records or record checkup visit.
     */
    public function maternal(Request $request, PrenatalAssessment $assessment)
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
            return redirect()->route($this->homeRoute())->with('error', 'Maternal patient record not found.');
        }

        $this->authorize('view', $patient);

        $record = $patient->maternalRecord;
        $checkups = $record ? $record->checkups : collect();
        $postpartumVisits = $record ? $record->postpartumVisits()->latest('date')->latest('id')->get() : collect();

        // Checkup submission
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            if ($request->input('visit_type') === 'postpartum') {
                return $this->storePostpartum($request, $patient);
            }

            return $this->storeCheckup($request, $patient);
        }

        $latestCheckup = $checkups->sortBy([['date', 'desc'], ['visit_number', 'desc']])->first();
        $gestationalDays = PrenatalAssessment::gestationalAgeDays($record?->lmp, Carbon::today());
        $riskFactors = $assessment->riskFactors($patient, $record, $latestCheckup);

        AuditLog::log('view_maternal_record', $patient);

        // The four routine postnatal visits, each marked Done / Due / Overdue / Upcoming.
        $postpartumSchedule = $this->postpartumSchedule($record, $postpartumVisits);

        // List of maternal patients for the admin-only "switch mother" dropdown.
        $maternalPatients = auth()->user()->role === 'admin'
            ? Patient::where('registration_type', 'Maternal')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
            : collect();

        $view = auth()->user()->role === 'user' ? 'patient.maternal' : 'maternal';
        return view($view, compact('patient', 'record', 'checkups', 'latestCheckup', 'gestationalDays', 'riskFactors', 'maternalPatients', 'postpartumVisits', 'postpartumSchedule'));
    }

    /**
     * Store new maternal checkup visit.
     */
    public function storeCheckup(Request $request, ?Patient $patient = null)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'weight_kg' => 'required|numeric|min:25|max:250',
            'bp' => ['required', 'string', 'regex:/^\s*\d{2,3}\s*\/\s*\d{2,3}\s*$/'],
            // Doppler usually picks up the fetal heart from about 12 weeks, so it may be blank early on.
            'fetal_heart_rate' => 'nullable|integer|min:50|max:250',
            'notes' => 'nullable|string',
        ], [
            'bp.regex' => 'Enter blood pressure as systolic/diastolic, e.g. 120/80.',
        ]);

        if (!$patient) {
            $patient = Patient::findOrFail($request->patient_id);
        }

        $record = $patient->maternalRecord;
        if (!$record) {
            return back()->with('error', 'No maternal record associated with this patient.');
        }

        $visitNumber = $record->checkups()->count() + 1;

        // Age of gestation, risk status and next visit date are derived by MaternalCheckup on save.
        $checkup = MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => $visitNumber,
            'date' => Carbon::now()->format('Y-m-d'),
            'weight_kg' => $request->weight_kg,
            'bp' => preg_replace('/\s+/', '', $request->bp),
            'fetal_heart_rate' => $request->fetal_heart_rate,
            'attendant' => auth()->user()->name,
            'notes' => $request->notes,
        ]);

        AuditLog::log('create_maternal_checkup', $checkup, [
            'patient_id' => $patient->id,
            'visit_number' => $visitNumber,
            'status' => $checkup->status,
        ]);

        if ($checkup->status === PrenatalAssessment::HIGH_RISK) {
            return back()->with('warning', 'Visit saved and flagged HIGH RISK: ' . implode(' ', array_column($checkup->risk_flags, 'message')));
        }

        return back()->with('success', 'Checkup visit log added successfully!');
    }

    /**
     * Store a new postpartum (postnatal) visit for a mother who has delivered.
     */
    public function storePostpartum(Request $request, Patient $patient)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'weight_kg' => 'required|numeric|min:25|max:250',
            'bp' => ['required', 'string', 'regex:/^\s*\d{2,3}\s*\/\s*\d{2,3}\s*$/'],
            'notes' => 'nullable|string|max:5000',
        ]);

        $record = $patient->maternalRecord;
        if (! $record) {
            return back()->with('error', 'No maternal record associated with this patient.');
        }

        if (! $record->delivery_date) {
            return back()->with('error', 'No delivery date on file. Record it on the patient edit form before logging postpartum visits.');
        }

        $visit = PostpartumVisit::create([
            'maternal_record_id' => $record->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'weight_kg' => $request->weight_kg,
            'bp' => preg_replace('/\s+/', '', $request->bp),
            'attendant' => auth()->user()->name,
            'notes' => $request->notes,
        ]);

        AuditLog::log('create_postpartum_visit', $visit, [
            'patient_id' => $patient->id,
            'days_postpartum' => $visit->days_postpartum,
            'status' => $visit->status,
        ]);

        if ($visit->status === PrenatalAssessment::HIGH_RISK) {
            return back()->with('warning', 'Postpartum visit saved and flagged HIGH RISK: ' . implode(' ', array_column($visit->risk_flags, 'message')));
        }

        return back()->with('success', 'Postpartum visit log added successfully!');
    }

    /**
     * The four routine postnatal visits (48 h, 1 w, 2 w, 6 w) with a Done / Due / Overdue /
     * Upcoming state each, so the mother's postpartum coverage is visible at a glance.
     *
     * @return array<int, array{day: int, label: string, dueDate: Carbon, state: string}>
     */
    private function postpartumSchedule(?MaternalRecord $record, $postpartumVisits): array
    {
        $deliveryDate = $record?->delivery_date;

        return collect(PostpartumAssessment::SCHEDULE_DAYS)->map(function (int $day) use ($deliveryDate, $postpartumVisits) {
            $dueDate = $deliveryDate ? Carbon::parse($deliveryDate)->addDays($day) : null;

            // A visit "covers" a scheduled check when it falls in the window up to the next
            // scheduled day (the last one is covered by any visit on or after day 42).
            $nextDay = PostpartumAssessment::nextScheduledDay($day);
            $covered = $postpartumVisits->contains(function ($visit) use ($day, $nextDay) {
                $d = $visit->days_postpartum;
                if ($d === null) {
                    return false;
                }

                return $nextDay === null ? $d >= $day : $d >= $day && $d < $nextDay;
            });

            $state = match (true) {
                $covered => 'Done',
                $dueDate === null => 'Upcoming',
                Carbon::today()->greaterThan($dueDate) => 'Overdue',
                Carbon::today()->greaterThanOrEqualTo($dueDate) => 'Due',
                default => 'Upcoming',
            };

            return [
                'day' => $day,
                'label' => PostpartumAssessment::SCHEDULE_LABELS[$day],
                'dueDate' => $dueDate,
                'state' => $state,
            ];
        })->all();
    }
}
