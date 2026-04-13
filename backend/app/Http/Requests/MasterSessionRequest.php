<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterSessionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'master_name' => 'required|string|max:255',
            'session_type' => 'required|in:Morning,Afternoon,Evening,Fullday,Online',
            'time' => 'required|string|max:255',
            'course_type' => 'required|string|max:255',
            'status' => 'nullable|boolean',
        ];
    }
}
