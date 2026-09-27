<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MaternalCheckup;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MaternalRecordController extends Controller
{
    /**
     * Display maternal health records or record checkup visit.
     */
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
            return redirect()->route($this->homeRoute())->with('error', 'Maternal patient record not found.');
        }

        $this->authorize('view', $patient);

        $record = $patient->maternalRecord;
        $checkups = $record ? $record->checkups : collect();

        // Checkup submission
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            return $this->storeCheckup($request, $patient);
        }

        AuditLog::log('view_maternal_record', $patient);

        $view = auth()->user()->role === 'user' ? 'patient.maternal' : 'maternal';
        return view($view, compact('patient', 'record', 'checkups'));
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
            'weight_kg' => 'required|numeric',
            'bp' => 'required|string',
            'fetal_heart_rate' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        if (!$patient) {
            $patient = Patient::findOrFail($request->patient_id);
        }

        $record = $patient->maternalRecord;
        if (!$record) {
            return back()->with('error', 'No maternal record associated with this patient.');
        }

        $checkups = $record->checkups;
        $visitNumber = $checkups->count() + 1;
        $weeks = 4 * $visitNumber;

        $checkup = MaternalCheckup::create([
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

        AuditLog::log('create_maternal_checkup', $checkup, [
            'patient_id' => $patient->id,
            'visit_number' => $visitNumber,
        ]);

        return back()->with('success', 'Checkup visit log added successfully!');
    }
}
