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
        $adminId = $this->route('id') ?? $this->route('admin');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.,_\'"-]+$/'],
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8|confirmed',
            'centre_id' => 'nullable|integer|exists:centres,id',
            'centres' => 'nullable|array',
            'centres.*' => 'integer|exists:centres,id',
        ];

        if ($isUpdate && $adminId) {
            $rules = [
                'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.,_\'"-]+$/'],
                'email' => 'required|string|email|max:255|unique:admins,email,' . $adminId,
                'centre_id' => 'nullable|integer|exists:centres,id',
                'centres' => 'nullable|array',
                'centres.*' => 'integer|exists:centres,id',
            ];
            
            // Only require password if it's being updated
            if ($this->filled('password')) {
                $rules['password'] = 'string|min:8|confirmed';
            }
            
            return $rules;
        }

        return $rules;
    }

        // Default: create rules
        // return [
        //     'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s.,_\'"-]+$/'],
        //     'email' => 'required|string|email|max:255|unique:admins',
        //     'password' => 'required|string|min:8',
        // ];
    

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
                'name' => ' Admin name',
                'email' => 'Admin Email',
                'password' => 'Admin password',
                'is_super' => 'Super admin status',

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
                'name.required' => 'The full name field is required.',
            'name.string' => 'The full name must be a valid text.',
            'name.max' => 'The full name must not exceed 100 characters.',
            'name.regex' => 'Only letters, numbers, spaces, and common punctuation are allowed.',

            'email.required' => 'The email address field is required.',
            'email.string' => 'The email address must be valid text.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'The email address must not exceed 255 characters.',
            'email.unique' => 'This email address is already registered in the system.',

            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be valid text.',
            'password.min' => 'The password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            ];
    }
}
