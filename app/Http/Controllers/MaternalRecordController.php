<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MaternalCheckup;
use App\Models\Patient;
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

        // Checkup submission
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            return $this->storeCheckup($request, $patient);
        }

        $latestCheckup = $checkups->sortBy([['date', 'desc'], ['visit_number', 'desc']])->first();
        $gestationalDays = PrenatalAssessment::gestationalAgeDays($record?->lmp, Carbon::today());
        $riskFactors = $assessment->riskFactors($patient, $record, $latestCheckup);

        AuditLog::log('view_maternal_record', $patient);

        $view = auth()->user()->role === 'user' ? 'patient.maternal' : 'maternal';
        return view($view, compact('patient', 'record', 'checkups', 'latestCheckup', 'gestationalDays', 'riskFactors'));
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
}
