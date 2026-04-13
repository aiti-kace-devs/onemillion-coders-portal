<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgrammeBatchRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'admission_batch_id' => 'required|exists:admission_batches,id',
            'programme_id' => 'required|exists:programmes,id',
            'centre_id' => 'required|exists:centres,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_enrolments' => 'required|integer|min:0',
            'available_slots' => 'required|integer|min:0',
            'status' => 'nullable|boolean',
        ];
    }
}
