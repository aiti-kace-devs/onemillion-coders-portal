<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistrictRequest extends FormRequest
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
            'description' => 'nullable|string|max:2000',
            'branch_id' => 'required|integer|exists:branches,id',
            'status' => 'nullable|boolean',
            'centres' => 'nullable|array',
            'centres.*' => 'integer|exists:centres,id',
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
            'title' => 'District title',
            'branch_id' => 'Branch',
            'centres' => 'Centres',
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
            'title.required' => 'The district title field is required.',
            'branch_id.required' => 'The branch field is required.',
            'branch_id.exists' => 'The selected branch is invalid.',
        ];
    }
}
