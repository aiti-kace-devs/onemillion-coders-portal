<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CentreRequest extends FormRequest
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
            'branch_id' => 'required|integer|exists:branches,id',
            'title' => 'required|string|max:255',
            'is_ready' => 'nullable|boolean',
            'constituency_id' => [
                'required',
                'integer',
                Rule::exists('constituencies', 'id')->where(function ($query) {
                    $query->where('branch_id', (int) $this->input('branch_id'));
                }),
            ],
            'district_id' => [
                'nullable',
                'integer',
                Rule::exists('districts', 'id')->where(function ($query) {
                    $query->where('branch_id', (int) $this->input('branch_id'));
                }),
            ],
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
            'branch_id' => 'Branch name',
            'is_ready' => 'Ready status',
            'title' => 'Title',
            'constituency_id' => 'Constituency',
            'district_id' => 'District',
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
            'branch_id.exists' => 'The selected branch is invalid.',
            'is_ready.boolean' => 'The ready status field must be true or false.',
            'title.required' => 'The title field is required.',
            'constituency_id.required' => 'The constituency field is required.',
            'constituency_id.exists' => 'The selected constituency is invalid for the selected region.',
            'district_id.exists' => 'The selected district is invalid for the selected region.',
        ];
    }
}
