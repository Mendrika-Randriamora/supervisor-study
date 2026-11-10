<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSupervisor() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
            'audience' => ['required', 'in:all,selected'],
            'student_ids' => ['required_if:audience,selected', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
