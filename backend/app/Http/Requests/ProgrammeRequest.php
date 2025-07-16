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
           'title' => 'required',
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
            'title' => 'Programme title',
            'duration' => 'Programme duration',
            'start_date' => 'Start date',
            'end_date' => 'End date'
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
            'title.required' => 'The programme title is required.',
            'title.duration' => 'The programme duration is required.',
        ];
    }
}
