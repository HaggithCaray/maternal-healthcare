<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrowthMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'child_record_id' => 'required|exists:child_records,id',
            'measurement_date' => 'required|date',
            'age_months' => 'required|integer|min:0|max:120',
            'weight_kg' => 'required|numeric|min:0.5|max:100',
            'height_cm' => 'required|numeric|min:20|max:200',
            'head_circumference_cm' => 'nullable|numeric|min:10|max:100',
            'muac_cm' => 'nullable|numeric|min:5|max:50',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
