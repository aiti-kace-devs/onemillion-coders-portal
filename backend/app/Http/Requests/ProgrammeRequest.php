<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgrammeRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'sub_title' => 'sometimes|string|max:255',
            'duration' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'description' => 'sometimes|string',
            'overview' => 'sometimes|array',
            'prerequisites' => 'sometimes|string',
            'image' => 'sometimes|string',
            'level' => 'sometimes|string',
            'job_responsible' => 'sometimes|string',
            'cover_image_id' => 'sometimes|string',
            'course_category_id' => 'sometimes|string',
            'status' => 'sometimes|boolean',
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
            //
        ];
    }
}
