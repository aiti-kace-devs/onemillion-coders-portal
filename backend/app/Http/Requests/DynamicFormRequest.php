<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DynamicFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return
            [
                'title' => ['required', 'string', 'max:100'],
                'description' => ['nullable'],
                'image' => [
                    'nullable',
                    $this->boolean('isDirty') ? ['required', 'image'] : [],
                    'max:2048',
                ],
                'code' => [
                    'required',
                    Rule::unique('forms', 'code')->ignore($this->route('form'), 'uuid')
                ],
                'message_when_inactive' => ['required'],
                'message_after_registration' => ['required'],
                'active' => ['required'],
                'schema' => ['required', 'array'],
                'schema.*.title' => ['required'],
                'schema.*.description' => ['nullable'],
                'schema.*.type' => ['required', 'string', 'in:select,radio,checkbox,text,number,file,select_course,email,phonenumber,textarea'],
                'schema.*.rules' => ['nullable', 'string'],
                'schema.*.options' => [
                    'nullable',
                    'required_if:schema.*.type,select,radio,checkbox,file',
                    'string',
                    'min:1',
                    function ($attribute, $value, $fail) {
                        $index = explode('.', $attribute)[1]; // Extract index from "schema.X.options"
                        $type = request("schema.$index.type"); // Get the type for the same index

                        if ($type === 'file') {
                            $allowedFileTypes = ['jpg', 'jpeg', 'png', 'gif', 'docx', 'txt', 'pdf', 'csv', 'xlsx', 'zip'];

                            // Convert input to an array, normalize case, and trim spaces
                            $values = array_map('trim', explode(',', strtolower($value)));

                            $invalidValues = array_diff($values, $allowedFileTypes);

                            if (!empty($invalidValues)) {
                                $fail('The file type' . (count($invalidValues) > 1 ? 's' : '') . ' "' . implode(', ', $invalidValues) . '" ' . (count($invalidValues) > 1 ? 'are' : 'is') . ' invalid.');
                            }
                        }
                    }
                ],
                'schema.*.validators.required' => ['nullable', 'boolean'],
                'schema.*.validators.unique' => ['nullable', 'boolean'],

            ];
    }

    public function attributes()
    {
        return [
            'schema' => 'question',
            'schema.*.title' => 'question',
            'schema.*.description' => 'description',
            'schema.*.options' => 'options',
        ];
    }

    public function messages()
    {
        return [
            // 'schema.required'   => 'A question is required.',
            // 'schema.*.title.required'  => 'The question field is required.',
            'image.max' => 'The :attribute must not be greater than 2MB.',
            'schema.*.options.required_if' => 'The :attribute field is required.',
        ];
    }
}
