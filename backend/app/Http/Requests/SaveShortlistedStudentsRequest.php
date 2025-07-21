<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveShortlistedStudentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'emails' => 'sometimes|array',
            'emails.*' => 'email',
            'student_ids' => 'sometimes|array',
            'student_ids.*' => 'numeric',
            'phone_numbers' => 'sometimes|array',
            // 'phone_numbers.*' => 'phone'
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'emails.*' => 'email address',
            'student_ids.*' => 'student',
        ];
    }
}
