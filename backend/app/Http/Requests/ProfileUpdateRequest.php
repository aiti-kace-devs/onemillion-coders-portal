<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'network_type' => ['required', 'string', Rule::in(['mtn', 'telecel', 'airteltigo'])],
            'mobile_no' => ['required', 'string', 'max:255'],
        ];
    }
}
