<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendBulkEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'subject' => 'required',
            // require at least one of message or template
            'message' => 'nullable|required_without:template',
            'template' => 'nullable|required_without:message',
            'student_ids' => 'required_without:select_all_in_query|nullable|array',
            'student_ids.*' => 'exists:users,id',
            'select_all_in_query' => 'sometimes|boolean',
            'custom_view' => 'sometimes|string',
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
            'student_ids.*' => 'student',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Ensure that when using the GenericEmail mailable as template,
     * a message body is always provided, since that template has no default content.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $template = $this->input('template');
            $message = $this->input('message');

            if ($template === \App\Mail\GenericEmail::class && (is_null($message) || $message === '')) {
                $validator->errors()->add(
                    'message',
                    'A message is required when using the generic email template.'
                );
            }
        });
    }
}
