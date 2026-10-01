<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\Patient;
use App\Services\WhoGrowthStandards;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChildHealthController extends Controller
{
    /**
     * The child whose record is shown. Staff pick any patient with ?id=; a mother sees her own
     * children only (?id= picks one of them, defaulting to the first).
     *
     * @return array{0: ?Patient, 1: \Illuminate\Support\Collection<int, Patient>} [child, the mother's children for the switcher]
     */
    protected function selectChild(Request $request): array
    {
        $id = $request->query('id');

        if (auth()->user()->isAdmin()) {
            $patient = $id ? Patient::find($id) : Patient::where('registration_type', 'Child')->first();

            return [$patient, collect()];
        }

        $mother = Patient::where('user_id', auth()->id())->first();
        $children = $mother
            ? Patient::whereHas('childRecord', fn ($q) => $q->where('mother_id', $mother->id))->orderBy('dob')->get()
            : collect();

        if ($id !== null) {
            $patient = $children->firstWhere('id', (int) $id);
            abort_unless($patient, 404);

            return [$patient, $children];
        }

        return [$children->first(), $children];
    }

    /**
     * Display child growth records or record new growth measurement.
     */
    public function growth(Request $request)
    {
        [$patient, $siblings] = $this->selectChild($request);

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

        $latestGrowth = $growthMeasurements->sortByDesc('date')->first();

        AuditLog::log('view_growth_record', $patient);

        $view = auth()->user()->role === 'user' ? 'patient.growth' : 'growth';
        return view($view, compact('patient', 'childRecord', 'growthMeasurements', 'latestGrowth', 'siblings'));
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

        // Age in months, WHO z-scores and nutritional status are derived by GrowthMeasurement on save.
        $measurement = GrowthMeasurement::create([
            'child_record_id' => $childRecord->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'weight_kg' => $request->weight_kg,
            'height_cm' => $request->height_cm,
        ]);

        AuditLog::log('create_growth_measurement', $measurement, [
            'child_record_id' => $childRecord->id,
            'weight_kg' => $request->weight_kg,
            'height_cm' => $request->height_cm,
            'status' => $measurement->status,
        ]);

        if (! in_array($measurement->status, [WhoGrowthStandards::NORMAL, WhoGrowthStandards::NOT_ASSESSED], true)) {
            return back()->with('warning', "Measurement saved. WHO assessment: {$measurement->status}.");
        }

        return back()->with('success', 'Growth measurement log added successfully!');
    }

    /**
     * Display child immunization records or record vaccine administration.
     */
    public function immunization(Request $request)
    {
        [$patient, $siblings] = $this->selectChild($request);

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
        return view($view, compact('patient', 'childRecord', 'immunizations', 'siblings'));
    }

    /**
     * Mark vaccine dose as administered.
     */
    public function updateImmunizationStatus(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'immunization_id' => 'required|integer',
            'remarks' => 'nullable|string|max:255',
        ]);

        $immunization = Immunization::find($request->immunization_id);
        if ($immunization) {
            // Recorded already (e.g. by another midwife, or this page was out of date): keep that record.
            if ($immunization->status === 'Given') {
                return back()->with('warning', "{$immunization->vaccine_name} dose {$immunization->dose_number} was already recorded as given; nothing was changed.");
            }

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
