<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminRequest extends FormRequest
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
        $adminId = $this->route('id') ?? $this->route('admin'); // 'id' or 'admin' depending on your route
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        if ($isUpdate && $adminId) {
            $rules = [
                'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.,_\'"-]+$/'],
                'email' => 'required|string|email|max:255|unique:admins,email,' . $adminId,
                'password' => 'required|string|min:8',
            ];
            if ($this->filled('password')) {
                $rules['password'] = 'string|min:8';
            }
            return $rules;
        }

        // Default: create rules
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.,_\'"-]+$/'],
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
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
