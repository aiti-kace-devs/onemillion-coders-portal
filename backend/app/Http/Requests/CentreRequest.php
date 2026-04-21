<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CentreRequest extends FormRequest
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
        $isUpdating = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'branch_id' => 'required|integer|exists:branches,id',
            'title' => 'required|string|max:255',
            'is_ready' => 'nullable|boolean',
            'constituency_id' => [
                'required',
                'integer',
                Rule::exists('constituencies', 'id')->where(function ($query) {
                    $query->where('branch_id', (int) $this->input('branch_id'));
                }),
            ],
            'district_id' => [
                'nullable',
                'integer',
                Rule::exists('districts', 'id')->where(function ($query) {
                    $query->where('branch_id', (int) $this->input('branch_id'));
                }),
            ],
            'seat_count' => 'nullable|integer|min:1|max:255',
            'short_slots_per_day' => 'nullable|integer|min:0|max:255',
            'long_slots_per_day' => 'nullable|integer|min:0|max:255',
            'protocol_reserved_short_slots' => 'nullable|integer|min:0|max:255',
            'protocol_reserved_long_slots' => 'nullable|integer|min:0|max:255',
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
            'branch_id' => 'Branch name',
            'is_ready' => 'Ready status',
            'title' => 'Title',
            'constituency_id' => 'Constituency',
            'district_id' => 'District',
            'protocol_reserved_short_slots' => 'Protocol Reserved Short Slots',
            'protocol_reserved_long_slots' => 'Protocol Reserved Long Slots',
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
            'branch_id.required' => 'The branch field is required.',
            'branch_id.exists' => 'The selected branch is invalid.',
            'is_ready.boolean' => 'The ready status field must be true or false.',
            'title.required' => 'The title field is required.',
            'constituency_id.required' => 'The constituency field is required.',
            'constituency_id.exists' => 'The selected constituency is invalid for the selected region.',
            'district_id.exists' => 'The selected district is invalid for the selected region.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $seatCount = $this->input('seat_count');
            $shortSlots = $this->input('short_slots_per_day');
            $longSlots = $this->input('long_slots_per_day');
            $protocolShortReserved = $this->input('protocol_reserved_short_slots');
            $protocolLongReserved = $this->input('protocol_reserved_long_slots');

            // Validate that short + long = seat_count when all three are provided
            if ($seatCount && $shortSlots !== null && $longSlots !== null) {
                if (($shortSlots + $longSlots) !== (int) $seatCount) {
                    $validator->errors()->add(
                        'short_slots_per_day',
                        'Short slots + Long slots must equal Seat Count.'
                    );
                    $validator->errors()->add(
                        'long_slots_per_day',
                        'Short slots + Long slots must equal Seat Count.'
                    );
                }
            }

            // Validate protocol reserved slots don't exceed capacity
            if ($protocolShortReserved !== null && $shortSlots !== null && $protocolShortReserved > $shortSlots) {
                $validator->errors()->add(
                    'protocol_reserved_short_slots',
                    'Protocol reserved short slots cannot exceed short slots per day.'
                );
            }

            if ($protocolLongReserved !== null && $longSlots !== null && $protocolLongReserved > $longSlots) {
                $validator->errors()->add(
                    'protocol_reserved_long_slots',
                    'Protocol reserved long slots cannot exceed long slots per day.'
                );
            }
        });
    }
}
