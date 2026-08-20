<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'sms_gateway_url' => 'required|url',
            'sms_gateway_user' => 'nullable|string|max:255',
            'sms_gateway_password' => 'nullable|string|max:255',
        ];
    }
}
