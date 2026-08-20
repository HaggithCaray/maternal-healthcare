<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:2000|required_without:attachment',
            'attachment' => [
                'nullable',
                'file',
                'max:25600', // 25 MB max to prevent DoS
                'mimes:jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,mp4,mov',
            ],
        ];
    }
}
