<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $type = $this->input('type');
        $type = is_string($type) && $type !== '' ? $type : 'string';

        $valueRules = match ($type) {
            'integer' => ['required', 'integer'],
            'boolean' => ['required', Rule::in(['0', '1', 0, 1, true, false, 'true', 'false'])],
            'json' => ['required', 'json'],
            'array' => ['required', 'string'],
            // String configs (e.g. APPLICATION_REVIEW_IFRAME_URL) may be left empty until ready.
            default => ['nullable', 'string', 'max:65535'],
        };

        return [
            'key' => 'required|string|min:3|max:255',
            'type' => 'required|string|in:string,integer,boolean,array,json',
            'value' => $valueRules,
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
