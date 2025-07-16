<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseSessionRequest extends FormRequest
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
           'course_id' => ['required', 'exists:courses,id'],
            'limit' => ['required', 'numeric', 'min:0'],
            'course_time' => ['required', 'string', 'max:100'],
            'session' => ['required', 'string', 'max:100'],
            'link' => ['nullable', 'url'],
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
            'course_id' => 'Course name',
            'course_time' => ' Course duration',
            'limit' => 'session limit',
            'link' => 'Course link',
            'session' => 'Course session'

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
            'course_id' => 'Course name is required',
            'course_time' => ' Course duration is required',
            'limit' => 'Session limit is required',
            'session' => 'Session is required'
        ];
    }
}
