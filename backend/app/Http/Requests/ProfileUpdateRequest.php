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
   
    protected function prepareForValidation()
    {
        if ($this->input('card_type') === 'ghcard' && $this->ghcard && !str_starts_with($this->ghcard, 'GHA-')) {
            $this->merge([
                'ghcard' => 'GHA-' . $this->ghcard,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|regex:/^[\pL\s\-\' ]+$/u|min:5|max:255|min:4',
            'gender' => 'sometimes|in:male,female',
            'mobile_no' => 'sometimes|string|phone',
            'email' => 'sometimes','email', Rule::unique('users', 'email')->ignore($this->user()->id),
            'network_type' => 'sometimes|in:mtn,telecel,airteltigo',
            'card_type' => 'sometimes|in:ghcard,voters_id,drivers_license,passport',
            'ghcard' => [
                'sometimes',
                Rule::when(
                    $this->input('card_type') === 'ghcard',
                    [
                        'max:16',
                        'regex:/^GHA-[0-9]{9}-[0-9]{1}$/',
                        Rule::unique('users', 'ghcard')->ignore($this->user()->id),
                    ],
                    [
                        'max:20',
                        Rule::unique('users', 'ghcard')->ignore($this->user()->id),
                    ]
                ),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'ghcard' => 'Card ID',
        ];
    }
}
