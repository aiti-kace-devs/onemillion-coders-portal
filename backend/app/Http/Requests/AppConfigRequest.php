<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppConfigRequest extends FormRequest
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
            'key' => 'required|string|min:3|max:255',
            'value' => 'required|integer',
            'type' => 'required|string|in:string,integer,boolean,array,json', // optionally restrict to known types
            'is_cached' => 'nullable|boolean',
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
              'key' => 'configuration key',
            'type' => 'value type',
            'value' => 'configuration value',
            'is_cached' => 'cache setting',
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
             'key.required' => 'Configuration key is required.',
            'key.unique' => 'This configuration key already exists.',
            'key.max' => 'Configuration key cannot exceed 255 characters.',
            'type.required' => 'Value type is required.',
            'type.in' => 'Please select a valid value type.',
            'is_cached.required' => 'Cache setting is required.',
            'is_cached.boolean' => 'Cache setting must be enabled or disabled.',
        ];
    }
}
