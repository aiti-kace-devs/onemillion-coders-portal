<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'master_name' => 'required|string|max:255',
            'session_type' => 'required|string|in:Morning,Afternoon,Evening,Fullday,Online',
            'time'         => 'required|string|max:255',
            'course_type'  => 'required|string|max:255',
            'status'       => 'nullable|boolean',
        ];
    }
}
