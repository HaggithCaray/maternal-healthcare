<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaternalCheckupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'maternal_record_id' => 'required|exists:maternal_records,id',
            'visit_date' => 'required|date',
            'gestational_age_weeks' => 'required|integer|min:1|max:45',
            'weight_kg' => 'required|numeric|min:20|max:200',
            'blood_pressure' => ['required', 'string', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'fundal_height_cm' => 'nullable|numeric|min:0|max:50',
            'fetal_heart_rate_bpm' => 'nullable|integer|min:60|max:220',
            'notes' => 'nullable|string|max:1000',
            'next_visit_date' => 'nullable|date|after:visit_date',
        ];
    }
}
