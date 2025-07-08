<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'user_id' => 'required|exists:users,userId',
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date|before_or_equal:' . now()->toDateString(),
        ];
    }

    /**
     * Validation for generating QR code data.
     */
    public function rulesForQRCode()
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date|before_or_equal:' . now()->toDateString(),
            'online' => 'sometimes',
            'validity' => 'sometimes|integer|min:1',
        ];
    }

    /**
     * Validation for recording attendance (scanned_data).
     */
    public function rulesForRecordAttendance()
    {
        return [
            'scanned_data' => 'required|string',
        ];
    }

    /**
     * Validation for confirming attendance.
     */
    public function rulesForConfirmAttendance()
    {
        return [
            'user_id' => 'required|exists:users,userId',
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date|before_or_equal:' . now()->toDateString(),
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
                //
            ];
    }
}
