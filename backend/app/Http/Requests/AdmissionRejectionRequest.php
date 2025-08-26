<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdmissionRejectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
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
           'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'rejected_at' => 'required|date',

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
             'user_id' => 'Student',
            'course_id' => 'Course',
            'rejected_at' => 'Rejection date',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
               'user_id.required' => 'Please select a student.',
            'user_id.exists' => 'The selected student does not exist.',
            'course_id.required' => 'Please select a course.',
            'course_id.exists' => 'The selected course does not exist.',
            'rejected_at.required' => 'Please provide a rejection date.',
            'rejected_at.date' => 'The rejection date must be a valid date.',
        ];
    }
}
