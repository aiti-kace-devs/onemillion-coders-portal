<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionnaireRequest extends FormRequest
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
                    Rule::unique('questionnaires', 'code')->ignore($this->route('questionnaire'), 'uuid')
                ],
                'message_when_inactive' => ['required', 'string'],
                'message_after_submission' => ['required', 'string'],
                'active' => ['nullable'],
                'schema' => ['required', 'array'],
                'schema.*.type' => ['required', 'string', 'in:course,instructors,others,facility'],
                'schema.*.title' => ['nullable', 'string'],
                'schema.*.description' => ['nullable', 'string'],
                'schema.*.questions.*.title' => ['required', 'string'],
                'schema.*.questions.*.description' => ['nullable', 'string'],
                'schema.*.questions.*.type' => ['required', 'string', 'in:radio,checkbox,text,textarea,instructor_feedback'],
                'schema.*.questions.*.rules' => ['nullable', 'string'],
                'schema.*.questions.*.options' => [
                    'nullable',
                    'required_if:schema.*.type,radio,checkbox',
                    'string',
                    'min:1',
                ],
                'schema.*.questions.*.validators.required' => ['nullable', 'boolean'],
                'schema.*.questions.*.validators.unique' => ['nullable', 'boolean'],

            ];
    }

    public function attributes()
    {
        return [
            'schema' => 'section',
            'code' => 'unique code',
            'schema.*.type' => 'type',
            'schema.*.title' => 'title',
            'schema.*.description' => 'description',
            'schema.*.questions.*.title' => 'question',
            'schema.*.questions.*.description' => 'description',
            'schema.*.questions.*.options' => 'options',
            'schema.*.questions.*.rules' => 'rules',
        ];
    }

    public function messages()
    {
        return [
            'schema.required'   => 'A section is required.',
            // 'schema.*.title.required'  => 'The question field is required.',
            'image.max' => 'The :attribute must not be greater than 2MB.',
            'schema.*.questions.*.options.required_if' => 'The :attribute field is required.',
        ];
    }
}
