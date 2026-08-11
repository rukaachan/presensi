<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:60'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:20'],
            'admission_year' => ['required', 'integer'],
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'min:8'],
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }
}
