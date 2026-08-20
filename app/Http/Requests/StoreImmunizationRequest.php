<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImmunizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'child_record_id' => 'required|exists:child_records,id',
            'vaccine_name' => 'required|string',
            'dose_number' => 'required|integer|min:1',
            'scheduled_date' => 'required|date',
            'status' => 'required|in:Scheduled,Administered,Missed',
            'administered_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
