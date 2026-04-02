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
            'schema.*.validators' => 'nullable|array',
            // 'schema.*.validators.required' => 'required|boolean',
            // 'schema.*.validators.unique' => 'required|boolean',
            'schema.*.field_name' => 'nullable|string|max:100|regex:/^[a-z0-9\-_]+$/',
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
            if (isset($field['type']) && in_array($field['type'], ['select']) && empty($field['options'])) {
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
            if (!isset($field['rules']) || trim((string) $field['rules']) === '') {
                continue;
            }

            $rules = $this->splitRuleString((string) $field['rules']);

            foreach ($rules as $rule) {
                if ($this->isRegexRule($rule)) {
                    $pattern = $this->extractRegexPattern($rule);
                    if (! $this->isValidRegexPattern($pattern)) {
                        $validator->errors()->add(
                            "schema.{$index}.rules",
                            "Invalid regex rule. Ensure it has valid delimiters (e.g. regex:/^...$/)."
                        );
                        break;
                    }
                }
            }
        }
    }

    private function isRegexRule(string $rule): bool
    {
        return str_starts_with($rule, 'regex:') || str_starts_with($rule, 'not_regex:');
    }

    private function extractRegexPattern(string $rule): string
    {
        $position = strpos($rule, ':');
        if ($position === false) {
            return '';
        }

        return substr($rule, $position + 1);
    }

    private function isValidRegexPattern(string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        $isValid = true;
        set_error_handler(function () use (&$isValid) {
            $isValid = false;
            return true;
        }, E_WARNING);

        try {
            $result = preg_match($pattern, '');
            if ($result === false) {
                $isValid = false;
            }
        } catch (\Throwable $exception) {
            $isValid = false;
        } finally {
            restore_error_handler();
        }

        return $isValid;
    }

    private function splitRuleString(string $rules): array
    {
        $rules = trim($rules);
        if ($rules === '') {
            return [];
        }

        $tokens = [];
        $length = strlen($rules);
        $index = 0;

        while ($index < $length) {
            while ($index < $length && ctype_space($rules[$index])) {
                $index++;
            }

            if ($index >= $length) {
                break;
            }

            if ($this->startsWithRegexRule($rules, $index)) {
                [$token, $nextIndex] = $this->consumeRegexRule($rules, $index);
                $token = trim($token);
                if ($token !== '') {
                    $tokens[] = $token;
                }
                $index = $nextIndex;
                if ($index < $length && $rules[$index] === '|') {
                    $index++;
                }
                continue;
            }

            $start = $index;
            while ($index < $length && $rules[$index] !== '|') {
                $index++;
            }

            $token = trim(substr($rules, $start, $index - $start));
            if ($token !== '') {
                $tokens[] = $token;
            }

            if ($index < $length && $rules[$index] === '|') {
                $index++;
            }
        }

        return $tokens;
    }

    private function startsWithRegexRule(string $rules, int $index): bool
    {
        return str_starts_with(substr($rules, $index), 'regex:')
            || str_starts_with(substr($rules, $index), 'not_regex:');
    }

    private function consumeRegexRule(string $rules, int $index): array
    {
        $prefix = str_starts_with(substr($rules, $index), 'regex:') ? 'regex:' : 'not_regex:';
        $length = strlen($rules);
        $current = $index + strlen($prefix);

        if ($current >= $length) {
            return [substr($rules, $index), $length];
        }

        $delimiter = $rules[$current];
        $current++;
        $token = $prefix . $delimiter;
        $escaped = false;

        while ($current < $length) {
            $char = $rules[$current];
            $token .= $char;

            if ($escaped) {
                $escaped = false;
            } elseif ($char === '\\') {
                $escaped = true;
            } elseif ($char === $delimiter) {
                $current++;
                break;
            }

            $current++;
        }

        while ($current < $length && ctype_alpha($rules[$current])) {
            $token .= $rules[$current];
            $current++;
        }

        return [$token, $current];
    }
}
