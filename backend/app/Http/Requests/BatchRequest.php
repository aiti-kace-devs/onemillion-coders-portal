<?php

namespace App\Http\Requests;

use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        // A completed batch should never be active.
        if ($this->boolean('completed')) {
            $this->merge(['status' => 0]);
        }
    }

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
            'description' => 'nullable|string',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'boolean',
            'completed' => 'boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $status = $this->boolean('status');
            $completed = $this->boolean('completed');

            // Only enforce for active, not completed batches.
            if (!$status || $completed) {
                return;
            }

            $currentBatchId = $this->route('id')
                ?? $this->route('batch')
                ?? $this->route('batchId');

            $currentBatchId = is_numeric($currentBatchId) ? (int) $currentBatchId : null;

            // Only one active batch is allowed at a time.
            $conflictingBatch = Batch::query()
                ->where('status', true)
                ->when($currentBatchId, fn ($q) => $q->where('id', '!=', $currentBatchId))
                ->orderBy('start_date')
                ->first();

            if ($conflictingBatch) {
                $conflictRange = trim(($conflictingBatch->start_date ?? '') . ' to ' . ($conflictingBatch->end_date ?? ''));
                $validator->errors()->add(
                    'status',
                    "There is already an active batch ({$conflictingBatch->title}) scheduled for {$conflictRange}. Deactivate or complete it before activating another batch."
                );
            }
        });
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'title' => 'Batch title',
            'description' => 'Description',
            'start_date' => 'Start date',
            'end_date' => 'End date',
            'status' => 'Status',
            'completed' => 'Completed status',
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
            'title.required' => 'Please provide a batch title.',
            'title.string' => 'The batch title must be a valid string.',
            'title.max' => 'The batch title cannot exceed 255 characters.',
            'description.string' => 'The description must be a valid string.',
            'start_date.required' => 'Please provide a start date.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.required' => 'Please provide an end date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'completed.required' => 'Please specify the completed status.',
            'completed.boolean' => 'The completed status must be either true or false.',
        ];
    }
}
