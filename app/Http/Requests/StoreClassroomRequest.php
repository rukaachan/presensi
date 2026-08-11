<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grade_level' => ['required', 'string', 'max:10'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated'])],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ];
    }
}
