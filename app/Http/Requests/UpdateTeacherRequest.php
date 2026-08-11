<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'photo' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'username' => ['required', 'string', 'max:60'],
        ];
    }
}
