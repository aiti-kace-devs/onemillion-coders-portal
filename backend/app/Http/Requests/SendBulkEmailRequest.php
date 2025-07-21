<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendBulkEmailRequest extends FormRequest
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
            'subject' => 'required',
            'message' => 'sometimes',
            'template' => 'required_if:message,null',
            'student_ids' => 'required_if:list,null|nullable|array',
            'student_ids.*' => 'exists:users,id',
            'list' => 'required_if:student_ids,null|nullable|string',
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
            'student_ids.*' => 'student',
        ];
    }
}
