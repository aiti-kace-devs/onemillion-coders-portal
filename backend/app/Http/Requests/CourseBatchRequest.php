<?php

namespace App\Http\Requests;

use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CourseBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'course_id'       => 'required|integer|exists:courses,id',
            'batch_id'        => 'required|integer|exists:admission_batches,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'duration'        => 'nullable|integer|min:1',
            'available_slots' => 'nullable|integer|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $batchId   = $this->input('batch_id');
            $startDate = $this->input('start_date');
            $endDate   = $this->input('end_date');

            if (!$batchId || !$startDate || !$endDate) {
                return;
            }

            $batch = Batch::find($batchId);
            if (!$batch) {
                return;
            }

            if ($startDate < $batch->start_date) {
                $v->errors()->add('start_date', 'Start date must be within the admission batch period (' . $batch->start_date . ' – ' . $batch->end_date . ').');
            }

            if ($endDate > $batch->end_date) {
                $v->errors()->add('end_date', 'End date must be within the admission batch period (' . $batch->start_date . ' – ' . $batch->end_date . ').');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'course_id'       => 'Course',
            'batch_id'        => 'Admission batch',
            'start_date'      => 'Start date',
            'end_date'        => 'End date',
            'duration'        => 'Duration',
            'available_slots' => 'Available slots',
        ];
    }
}
