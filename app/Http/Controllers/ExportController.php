<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\Patient;
use App\Models\PostpartumVisit;
use App\Services\PrenatalAssessment;
use App\Support\CsvExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * CSV exports for reporting to the Municipal Health Office and for offline review.
 * Each export streams straight from the database and is written to the audit log.
 */
class ExportController extends Controller
{
    /**
     * Patient masterlist, honouring the same filters as the Records page so the file
     * matches exactly what the exporter saw on screen.
     */
    public function patients(Request $request, CsvExporter $csv)
    {
        $this->authorize('viewAny', Patient::class);

        [$query, $filters] = $this->filteredPatients($request);

        AuditLog::log('export_patients', null, $filters + ['count' => (clone $query)->count()]);

        $columns = [
            ['id', 'Patient ID'],
            ['name', 'Full Name'],
            ['registration_type', 'Type'],
            ['status', 'Status'],
            ['dob', 'Date of Birth'],
            ['age', 'Age'],
            ['gender', 'Gender'],
            ['phone', 'Phone'],
            ['address', 'Address'],
            ['barangay', 'Barangay'],
            ['emergency_contact_name', 'Emergency Contact'],
            ['emergency_contact_phone', 'Emergency Contact Phone'],
            ['registered', 'Registered On'],
        ];

        return $csv->download('patients-' . Carbon::today()->toDateString() . '.csv', $columns, function () use ($query) {
            // lazy() chunks by offset so the name ordering is kept, and stays memory-safe.
            $rows = $query->with('childRecord.mother')
                ->orderBy('last_name')->orderBy('first_name')
                ->lazy(500);

            foreach ($rows as $patient) {
                yield [
                    'id' => 'BC-' . $patient->created_at->format('Y') . '-' . sprintf('%03d', $patient->id),
                    'name' => $patient->full_name,
                    'registration_type' => $patient->registration_type,
                    'status' => $patient->status,
                    'dob' => $patient->dob ? Carbon::parse($patient->dob)->format('Y-m-d') : '',
                    'age' => $patient->dob ? Carbon::parse($patient->dob)->age : '',
                    'gender' => $patient->gender,
                    'phone' => $patient->phone,
                    'address' => $patient->address,
                    'barangay' => $patient->barangay,
                    'emergency_contact_name' => $patient->emergency_contact_name,
                    'emergency_contact_phone' => $patient->emergency_contact_phone,
                    'registered' => $patient->created_at->format('Y-m-d'),
                ];
            }
        });
    }

    /**
     * Full prenatal checkup log: one row per visit, oldest first.
     */
    public function prenatalVisits(CsvExporter $csv)
    {
        $this->authorize('viewAny', Patient::class);

        AuditLog::log('export_prenatal_visits', null, ['count' => MaternalCheckup::count()]);

        $columns = [
            ['mother', 'Mother'],
            ['date', 'Visit Date'],
            ['visit_number', 'Visit #'],
            ['age_of_gestation', 'Age of Gestation'],
            ['weight_kg', 'Weight (kg)'],
            ['bp', 'Blood Pressure'],
            ['fetal_heart_rate', 'Fetal Heart Rate (bpm)'],
            ['status', 'Status'],
            ['risk_flags', 'Risk Flags'],
            ['next_visit_date', 'Next Visit'],
            ['attendant', 'Assessed By'],
            ['notes', 'Notes'],
        ];

        return $csv->download('prenatal-visits-' . Carbon::today()->toDateString() . '.csv', $columns, function () {
            $rows = MaternalCheckup::with('maternalRecord.patient')
                ->orderBy('date')->orderBy('id')
                ->lazy(500);

            foreach ($rows as $checkup) {
                yield [
                    'mother' => $checkup->maternalRecord?->patient?->full_name ?? '',
                    'date' => $checkup->date?->format('Y-m-d'),
                    'visit_number' => $checkup->visit_number,
                    'age_of_gestation' => $checkup->age_of_gestation,
                    'weight_kg' => $checkup->weight_kg,
                    'bp' => $checkup->bp,
                    'fetal_heart_rate' => $checkup->fetal_heart_rate,
                    'status' => $checkup->status,
                    'risk_flags' => collect($checkup->risk_flags ?? [])->pluck('message')->implode('; '),
                    'next_visit_date' => $checkup->next_visit_date?->format('Y-m-d'),
                    'attendant' => $checkup->attendant,
                    'notes' => $checkup->notes,
                ];
            }
        });
    }

    /**
     * Postpartum (postnatal) visit log: one row per visit, oldest first.
     */
    public function postpartumVisits(CsvExporter $csv)
    {
        $this->authorize('viewAny', Patient::class);

        AuditLog::log('export_postpartum_visits', null, ['count' => PostpartumVisit::count()]);

        $columns = [
            ['mother', 'Mother'],
            ['date', 'Visit Date'],
            ['days_postpartum', 'Day Postpartum'],
            ['weight_kg', 'Weight (kg)'],
            ['bp', 'Blood Pressure'],
            ['status', 'Status'],
            ['risk_flags', 'Risk Flags'],
            ['next_visit_date', 'Next Visit'],
            ['attendant', 'Assessed By'],
            ['notes', 'Notes'],
        ];

        return $csv->download('postpartum-visits-' . Carbon::today()->toDateString() . '.csv', $columns, function () {
            $rows = PostpartumVisit::with('maternalRecord.patient')
                ->orderBy('date')->orderBy('id')
                ->lazy(500);

            foreach ($rows as $visit) {
                yield [
                    'mother' => $visit->maternalRecord?->patient?->full_name ?? '',
                    'date' => $visit->date?->format('Y-m-d'),
                    'days_postpartum' => $visit->days_postpartum,
                    'weight_kg' => $visit->weight_kg,
                    'bp' => $visit->bp,
                    'status' => $visit->status,
                    'risk_flags' => collect($visit->risk_flags ?? [])->pluck('message')->implode('; '),
                    'next_visit_date' => $visit->next_visit_date?->format('Y-m-d'),
                    'attendant' => $visit->attendant,
                    'notes' => $visit->notes,
                ];
            }
        });
    }

    /**
     * Immunization dose log: one row per scheduled dose.
     */
    public function immunizations(CsvExporter $csv)
    {
        $this->authorize('viewAny', Patient::class);

        AuditLog::log('export_immunizations', null, ['count' => Immunization::count()]);

        $columns = [
            ['child', 'Child'],
            ['mother', 'Mother'],
            ['vaccine_name', 'Vaccine'],
            ['dose_number', 'Dose #'],
            ['scheduled_date', 'Scheduled Date'],
            ['given_date', 'Given Date'],
            ['status', 'Status'],
            ['administered_by', 'Administered By'],
            ['remarks', 'Remarks'],
        ];

        return $csv->download('immunizations-' . Carbon::today()->toDateString() . '.csv', $columns, function () {
            $rows = Immunization::with('childRecord.patient', 'childRecord.mother')
                ->orderBy('scheduled_date')->orderBy('id')
                ->lazy(500);

            foreach ($rows as $dose) {
                yield [
                    'child' => $dose->childRecord?->patient?->full_name ?? '',
                    'mother' => $dose->childRecord?->mother?->full_name ?? '',
                    'vaccine_name' => $dose->vaccine_name,
                    'dose_number' => $dose->dose_number,
                    'scheduled_date' => $dose->scheduled_date?->format('Y-m-d'),
                    'given_date' => $dose->given_date?->format('Y-m-d'),
                    'status' => $dose->status,
                    'administered_by' => $dose->administered_by,
                    'remarks' => $dose->remarks,
                ];
            }
        });
    }

    /**
     * The annual report as tidy CSV rows (metric, value), matching the figures on the
     * Reports dashboard for the chosen year.
     */
    public function reportSummary(Request $request, CsvExporter $csv)
    {
        $this->authorize('viewAny', Patient::class);

        $today = Carbon::today();
        $firstRegistration = Patient::min('created_at');
        $firstYear = $firstRegistration ? min(Carbon::parse($firstRegistration)->year, $today->year) : $today->year;
        $years = range($today->year, $firstYear);

        $year = (int) $request->query('year', $today->year);
        if (! in_array($year, $years, true)) {
            $year = $today->year;
        }

        $totalPatients = Patient::count();
        $maternalCases = Patient::where('registration_type', 'Maternal')->count();
        $childRecords = Patient::where('registration_type', 'Child')->count();
        $highRiskMothers = Patient::where('registration_type', 'Maternal')->where('status', 'High Risk')->count();

        $monthly = [];
        foreach (range(1, 12) as $month) {
            $monthly[$month] = ['Maternal' => 0, 'Child' => 0];
        }
        Patient::whereYear('created_at', $year)
            ->get(['created_at', 'registration_type'])
            ->each(function (Patient $patient) use (&$monthly) {
                $monthly[$patient->created_at->month][$patient->registration_type]++;
            });

        $dueDoses = Immunization::whereYear('scheduled_date', $year)->whereDate('scheduled_date', '<=', $today);
        $dosesDue = (clone $dueDoses)->count();
        $dosesGiven = (clone $dueDoses)->where('status', 'Given')->count();
        $complianceRate = $dosesDue > 0 ? (int) round(($dosesGiven / $dosesDue) * 100) : null;
        $overdueDoses = Immunization::where('status', 'Scheduled')->whereDate('scheduled_date', '<', $today)->count();

        $prenatalVisits = MaternalCheckup::whereYear('date', $year)->count();
        $highRiskVisits = MaternalCheckup::whereYear('date', $year)->where('status', PrenatalAssessment::HIGH_RISK)->count();
        $postpartumVisits = PostpartumVisit::whereYear('date', $year)->count();
        $highRiskPostpartum = PostpartumVisit::whereYear('date', $year)->where('status', PrenatalAssessment::HIGH_RISK)->count();

        $latestStatuses = GrowthMeasurement::orderByDesc('date')->orderByDesc('id')
            ->get(['child_record_id', 'status'])
            ->unique('child_record_id')
            ->countBy('status');
        $childrenMeasured = $latestStatuses->sum();

        AuditLog::log('export_report_summary', null, ['year' => $year]);

        $rows = [
            ['metric' => 'Report year', 'value' => $year],
            ['metric' => 'Generated on', 'value' => $today->toDateString()],
            ['metric' => 'Registered patients (total)', 'value' => $totalPatients],
            ['metric' => 'Registered mothers', 'value' => $maternalCases],
            ['metric' => 'Registered children', 'value' => $childRecords],
            ['metric' => 'High-risk mothers', 'value' => $highRiskMothers],
        ];

        foreach (range(1, 12) as $month) {
            $rows[] = [
                'metric' => 'Registrations in ' . Carbon::create()->month($month)->format('F'),
                'value' => $monthly[$month]['Maternal'] + $monthly[$month]['Child'],
            ];
        }

        $rows = array_merge($rows, [
            ['metric' => 'Doses due so far in year', 'value' => $dosesDue],
            ['metric' => 'Doses given of those due', 'value' => $dosesGiven],
            ['metric' => 'Immunization coverage (%)', 'value' => $complianceRate ?? '—'],
            ['metric' => 'Overdue doses (all years)', 'value' => $overdueDoses],
            ['metric' => 'Prenatal visits in year', 'value' => $prenatalVisits],
            ['metric' => 'Prenatal visits flagged High Risk', 'value' => $highRiskVisits],
            ['metric' => 'Postpartum visits in year', 'value' => $postpartumVisits],
            ['metric' => 'Postpartum visits flagged High Risk', 'value' => $highRiskPostpartum],
            ['metric' => 'Children measured (growth)', 'value' => $childrenMeasured],
        ]);

        foreach ($latestStatuses as $status => $count) {
            $rows[] = ['metric' => 'Children last measured: ' . $status, 'value' => $count];
        }

        $columns = [
            ['metric', 'Metric'],
            ['value', 'Value'],
        ];

        return $csv->download("report-summary-{$year}-" . $today->toDateString() . '.csv', $columns, $rows);
    }

    /**
     * Apply the Records-page filters (search / type / status) to a patient query.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: array<string, string>}
     */
    private function filteredPatients(Request $request): array
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status');

        $query = Patient::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('childRecord.mother', fn ($mother) => $mother->where('phone', 'like', "%{$search}%"));
            });
        }

        if ($type && $type !== 'All Types') {
            $query->where('registration_type', $type);
        }

        if ($status && $status !== 'All Status') {
            $query->where('status', $status);
        }

        return [$query, array_filter([
            'search' => (string) $search,
            'type' => (string) $type,
            'status' => (string) $status,
        ], fn ($v) => $v !== '')];
    }
}
