<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationFormRequest extends FormRequest
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
            // 'description' => 'sometimes|string',
            // 'image' => 'sometimes|string',
            'schema' => 'sometimes|array',
            'schema.*' => 'required|array',
            'schema.*.title' => 'required|string|max:255',
            'schema.*.type' => 'required|string|in:text,email,select,phonenumber,select_course,textarea,number,date,file,checkbox,radio',
            'schema.*.description' => 'nullable|string|max:500',
            'schema.*.rules' => 'nullable|string|max:1000',
            'schema.*.options' => 'nullable|string|max:1000',
            'schema.*.validators' => 'required|array',
            // 'schema.*.validators.required' => 'required|boolean',
            // 'schema.*.validators.unique' => 'required|boolean',
            'schema.*.field_name' => 'required|string|max:100|regex:/^[a-z0-9\-_]+$/',
            'code' => 'required|string',
            // 'message_after_registration' => 'sometimes|string',
            // 'message_when_inactive' => 'sometimes|string',
            'active' => 'sometimes|boolean',
            'status' => 'sometimes|boolean',
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
            'schema.*.title.required' => 'Each form field must have a title.',
            'schema.*.title.max' => 'Field title cannot exceed 255 characters.',
            'schema.*.type.required' => 'Each form field must have a type.',
            'schema.*.type.in' => 'Field type must be one of: text, email, select, phonenumber, select_course, textarea, number, date, file, checkbox, radio.',
            'schema.*.description.max' => 'Field description cannot exceed 500 characters.',
            'schema.*.rules.max' => 'Field rules cannot exceed 1000 characters.',
            'schema.*.options.max' => 'Field options cannot exceed 1000 characters.',
            'schema.*.validators.required' => 'Each form field must have validators.',
            'schema.*.validators.required.required' => 'Required validator must be specified.',
            'schema.*.validators.unique.required' => 'Unique validator must be specified.',
            'schema.*.field_name.required' => 'Each form field must have a field name.',
            'schema.*.field_name.regex' => 'Field name can only contain lowercase letters, numbers, hyphens, and underscores.',
            'schema.*.field_name.max' => 'Field name cannot exceed 100 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateSchemaStructure($validator);
            $this->validateFieldNamesUniqueness($validator);
            $this->validateSelectOptions($validator);
            $this->validateRulesFormat($validator);
        });
    }

    /**
     * Validate schema structure and field types
     */
    private function validateSchemaStructure($validator)
    {
        if (!$this->has('schema') || !is_array($this->input('schema'))) {
            return;
        }

        $schema = $this->input('schema');
        
        foreach ($schema as $index => $field) {
            // Validate select fields have options
            if (isset($field['type']) && in_array($field['type'], ['select', 'select_course']) && empty($field['options'])) {
                $validator->errors()->add("schema.{$index}.options", "Select fields must have options defined.");
            }

            // Validate field name format
            if (isset($field['field_name'])) {
                if (preg_match('/^[a-z0-9\-_]+$/', $field['field_name']) === 0) {
                    $validator->errors()->add("schema.{$index}.field_name", "Field name contains invalid characters.");
                }
            }
        }
    }

    /**
     * Validate field names are unique within the schema
     */
    private function validateFieldNamesUniqueness($validator)
    {
        if (!$this->has('schema') || !is_array($this->input('schema'))) {
            return;
        }

        $schema = $this->input('schema');
        $fieldNames = [];
        
        foreach ($schema as $index => $field) {
            if (isset($field['field_name'])) {
                if (in_array($field['field_name'], $fieldNames)) {
                    $validator->errors()->add("schema.{$index}.field_name", "Field name '{$field['field_name']}' is already used.");
                }
                $fieldNames[] = $field['field_name'];
            }
        }
    }

    /**
     * Validate select options format
     */
    private function validateSelectOptions($validator)
    {
        if (!$this->has('schema') || !is_array($this->input('schema'))) {
            return;
        }

        $schema = $this->input('schema');
        
        foreach ($schema as $index => $field) {
            if (isset($field['type']) && $field['type'] === 'select' && isset($field['options'])) {
                $options = explode(',', $field['options']);
                if (count($options) < 2) {
                    $validator->errors()->add("schema.{$index}.options", "Select field must have at least 2 options.");
                }
            }
        }
    }

    /**
     * Validate rules format
     */
    private function validateRulesFormat($validator)
    {
        if (!$this->has('schema') || !is_array($this->input('schema'))) {
            return;
        }

        $schema = $this->input('schema');
        
        foreach ($schema as $index => $field) {
            if (isset($field['rules']) && !empty($field['rules'])) {
                $rules = explode('|', $field['rules']);
                foreach ($rules as $rule) {
                    $rule = trim($rule);
                    if (!empty($rule) && !preg_match('/^[a-zA-Z0-9:,\s\-_\/\^\\\pL]+$/', $rule)) {
                        $validator->errors()->add("schema.{$index}.rules", "Invalid rule format: {$rule}");
                    }
                }
            }
        }
    }
}
