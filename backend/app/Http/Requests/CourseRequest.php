<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
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
            'branch_id' => 'required',
            'centre_id' => 'required',
            'programme_id' => 'required',
            'duration' => 'required',
            'start_date' => 'sometimes',
            'end_date' => 'sometimes',
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
                //
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
            'branch_id.required' => 'The branch field is required.',
            'centre_id.required' => 'The centre field is required.',
            'programme_id.required' => 'The programme field is required.',
            'duration.required' => 'The duration field is required.',
        ];
    }
}
