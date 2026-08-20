<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'recipient_phone' => 'required|string',
            'message' => 'required|string|max:1000',
            'patient_id' => 'nullable|exists:patients,id',
        ];
    }
}
