<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'emergency_contact_name' => 'required|string',
            'emergency_contact_phone' => 'required|string',
            'registration_type' => 'required|in:Maternal,Child',

            // Maternal specific
            'lmp' => 'nullable|date',
            'gravida' => 'nullable|integer',
            'para' => 'nullable|integer',
            'blood_type' => 'nullable|string',
            'risk_level' => 'nullable|in:Low,Medium,High',
            'medical_history' => 'nullable|array',
            'medical_history.*' => 'string',

            // Child specific
            'mother_name' => 'nullable|string',
            'father_name' => 'nullable|string',
            'birth_weight' => 'nullable|numeric',
            'birth_height' => 'nullable|numeric',
            'delivery_type' => 'nullable|string',
            'place_of_delivery' => 'nullable|string',
            'attendant_at_birth' => 'nullable|string',
        ];
    }
}
