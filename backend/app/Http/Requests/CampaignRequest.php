<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
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
            'message' => 'required|string',
            'priority' => 'required|in:low,normal,high',
            'target_branches' => 'nullable|array',
            'target_branches.*' => 'exists:branches,id',
            'target_districts' => 'nullable|array',
            'target_districts.*' => 'exists:districts,id',
            'target_centres' => 'nullable|array',
            'target_centres.*' => 'exists:centres,id',
            'target_programme_batches' => 'nullable|array',
            'target_programme_batches.*' => 'exists:programme_batches,id',
            'target_master_sessions' => 'nullable|array',
            'target_master_sessions.*' => 'exists:master_sessions,id',
            'target_course_sessions' => 'nullable|array',
            'target_course_sessions.*' => 'exists:course_sessions,id',
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
