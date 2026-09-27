<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChildHealthController extends Controller
{
    /**
     * Display child growth records or record new growth measurement.
     */
    public function growth(Request $request)
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
            return redirect()->route($this->homeRoute())->with('error', 'Child record not found.');
        }

        $this->authorize('view', $patient);

        $childRecord = $patient->childRecord;
        $growthMeasurements = $childRecord ? $childRecord->growthMeasurements : collect();

        // Handle growth entry submission
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            return $this->storeGrowth($request, $childRecord, $patient);
        }

        AuditLog::log('view_growth_record', $patient);

        $view = auth()->user()->role === 'user' ? 'patient.growth' : 'growth';
        return view($view, compact('patient', 'childRecord', 'growthMeasurements'));
    }

    /**
     * Store new growth measurement entry.
     */
    public function storeGrowth(Request $request, ?ChildRecord $childRecord = null, ?Patient $patient = null)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'weight_kg' => 'required|numeric|min:0.5|max:100',
            'height_cm' => 'required|numeric|min:20|max:200',
        ]);

        if (!$childRecord) {
            $patient = Patient::findOrFail($request->patient_id);
            $childRecord = $patient->childRecord;
        }

        $ageMonths = Carbon::parse($patient->dob)->diffInMonths(Carbon::now());

        $measurement = GrowthMeasurement::create([
            'child_record_id' => $childRecord->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'age_months' => $ageMonths,
            'weight_kg' => $request->weight_kg,
            'height_cm' => $request->height_cm,
            'status' => 'Normal',
        ]);

        AuditLog::log('create_growth_measurement', $measurement, [
            'child_record_id' => $childRecord->id,
            'weight_kg' => $request->weight_kg,
            'height_cm' => $request->height_cm,
        ]);

        return back()->with('success', 'Growth measurement log added successfully!');
    }

    /**
     * Display child immunization records or record vaccine administration.
     */
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
            return redirect()->route($this->homeRoute())->with('error', 'Child immunization record not found.');
        }

        $this->authorize('view', $patient);

        $childRecord = $patient->childRecord;
        $immunizations = $childRecord ? $childRecord->immunizations : collect();

        // Handle updating vaccine dose given status
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            return $this->updateImmunizationStatus($request);
        }

        AuditLog::log('view_immunization_record', $patient);

        $view = auth()->user()->role === 'user' ? 'patient.immunization' : 'immunization';
        return view($view, compact('patient', 'childRecord', 'immunizations'));
    }

    /**
     * Mark vaccine dose as administered.
     */
    public function updateImmunizationStatus(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $immunization = Immunization::find($request->immunization_id);
        if ($immunization) {
            $immunization->update([
                'status' => 'Given',
                'given_date' => Carbon::now()->format('Y-m-d'),
                'administered_by' => auth()->user()->name,
                'remarks' => $request->remarks ?? 'Regular schedule dose',
            ]);

            AuditLog::log('administer_vaccine', $immunization, [
                'vaccine' => $immunization->vaccine_name,
                'dose' => $immunization->dose_number,
            ]);

            return back()->with('success', 'Vaccine marked as administered!');
        }

        return back()->with('error', 'Vaccine record not found.');
    }
}
