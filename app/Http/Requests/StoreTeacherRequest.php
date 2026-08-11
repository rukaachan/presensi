<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'min:8'],
            'role_code' => ['required', Rule::in(['homeroom_teacher', 'duty_teacher', 'counseling_teacher'])],
        ];
    }
}
