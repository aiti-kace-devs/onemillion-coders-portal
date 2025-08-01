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
        // If this is a QR code generation request
        if ($this->routeIs('attendance.generate_qrcode')) {
            return [
                'course_id' => 'required|exists:courses,id',
                'date' => 'required|date|before_or_equal:' . now()->toDateString(),
                'online' => 'sometimes',
                'validity' => 'sometimes|integer|min:1',
            ];
        }
        // If this is a record attendance request
        if ($this->routeIs('attendance.record_attendance')) {
            return [
                'scanned_data' => 'required|string',
            ];
        }
        // If this is a confirm attendance request
        if ($this->routeIs('attendance.confirm_attendance')) {
            return [
                'user_id' => 'required|exists:users,userId',
                'course_id' => 'required|exists:courses,id',
                'date' => 'required|date|before_or_equal:' . now()->toDateString(),
            ];
        }
        // Default: CRUD validation
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
            'user_id' => 'student',
            'course_id' => 'course',
            'date' => 'attendance date',
            'scanned_data' => 'scanned QR code',
            'online' => 'online status',
            'validity' => 'validity period',
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
            'user_id.required' => 'Please select a student.',
            'user_id.exists' => 'The selected student does not exist.',
            'course_id.required' => 'Please select a course.',
            'course_id.exists' => 'The selected course does not exist.',
            'date.required' => 'Please provide an attendance date.',
            'date.date' => 'The attendance date must be a valid date.',
            'date.before_or_equal' => 'The attendance date cannot be in the future.',
            'scanned_data.required' => 'Please provide the scanned QR code data.',
            'scanned_data.string' => 'The scanned QR code data must be a valid string.',
            'validity.integer' => 'The validity period must be a number.',
            'validity.min' => 'The validity period must be at least 1.',
            ];
    }
}
