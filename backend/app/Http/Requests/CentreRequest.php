<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CentreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'branch_id'          => 'required|integer|exists:branches,id',
            'title'              => 'required|string|max:255',
            'is_ready'           => 'nullable|boolean',
            'seat_count'         => 'nullable|integer|min:0|max:65535',
            'short_slots_per_day' => 'nullable|integer|min:0|max:65535',
            'long_slots_per_day'  => 'nullable|integer|min:0|max:65535',
            'constituency_id'    => [
                'required',
                'integer',
                Rule::exists('constituencies', 'id')->where(function ($query) {
                    $query->where('branch_id', (int) $this->input('branch_id'));
                }),
            ],
            'district_id'        => [
                'nullable',
                'integer',
                Rule::exists('districts', 'id')->where(function ($query) {
                    $query->where('branch_id', (int) $this->input('branch_id'));
                }),
            ],
        ];
    }

    /** short_slots_per_day + long_slots_per_day must equal seat_count when all three are provided. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $seat  = $this->input('seat_count');
            $short = $this->input('short_slots_per_day');
            $long  = $this->input('long_slots_per_day');

            if ($seat !== null && $short !== null && $long !== null
                && (int) $short + (int) $long !== (int) $seat
            ) {
                $v->errors()->add(
                    'short_slots_per_day',
                    'Short-course slots + long-course slots must equal the total seat count (' . $seat . ').'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'branch_id'          => 'Branch name',
            'is_ready'           => 'Ready status',
            'title'              => 'Title',
            'constituency_id'    => 'Constituency',
            'district_id'        => 'District',
            'seat_count'         => 'Seat count',
            'short_slots_per_day' => 'Short-course slots',
            'long_slots_per_day'  => 'Long-course slots',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required'      => 'The branch field is required.',
            'branch_id.exists'        => 'The selected branch is invalid.',
            'is_ready.boolean'        => 'The ready status field must be true or false.',
            'title.required'          => 'The title field is required.',
            'constituency_id.required' => 'The constituency field is required.',
            'constituency_id.exists'  => 'The selected constituency is invalid for the selected region.',
            'district_id.exists'      => 'The selected district is invalid for the selected region.',
        ];
    }
}
