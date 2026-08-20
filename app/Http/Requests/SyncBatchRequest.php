<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'batch_id' => 'nullable|string|uuid',
            'client_timestamp' => 'nullable|date',
            'patients' => 'nullable|array',
            'patients.*.first_name' => 'required_with:patients|string|max:255',
            'patients.*.last_name' => 'required_with:patients|string|max:255',
            'patients.*.dob' => 'required_with:patients|date',
            'patients.*.gender' => 'required_with:patients|string',
            'patients.*.phone' => 'required_with:patients|string',
            'patients.*.email' => 'nullable|email',
            'patients.*.address' => 'required_with:patients|string',
            'patients.*.emergency_contact_name' => 'required_with:patients|string',
            'patients.*.emergency_contact_phone' => 'required_with:patients|string',
            'patients.*.registration_type' => 'required_with:patients|in:Maternal,Child',

            'checkups' => 'nullable|array',
            'checkups.*.maternal_record_id' => 'required_with:checkups|exists:maternal_records,id',
            'checkups.*.visit_date' => 'required_with:checkups|date',
            'checkups.*.gestational_age_weeks' => 'required_with:checkups|integer',
            'checkups.*.weight_kg' => 'required_with:checkups|numeric',
            'checkups.*.blood_pressure' => 'required_with:checkups|string',

            'immunizations' => 'nullable|array',
            'immunizations.*.child_record_id' => 'required_with:immunizations|exists:child_records,id',
            'immunizations.*.vaccine_name' => 'required_with:immunizations|string',
            'immunizations.*.dose_number' => 'required_with:immunizations|integer',
            'immunizations.*.scheduled_date' => 'required_with:immunizations|date',
            'immunizations.*.status' => 'required_with:immunizations|in:Scheduled,Administered,Missed',

            'growth' => 'nullable|array',
            'growth.*.child_record_id' => 'required_with:growth|exists:child_records,id',
            'growth.*.measurement_date' => 'required_with:growth|date',
            'growth.*.age_months' => 'required_with:growth|integer',
            'growth.*.weight_kg' => 'required_with:growth|numeric',
            'growth.*.height_cm' => 'required_with:growth|numeric',
        ];
    }
}
