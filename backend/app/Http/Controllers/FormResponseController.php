<?php

namespace App\Http\Controllers;

use App\Events\FormSubmittedEvent;
use App\Models\Form;
use App\Models\User;
use App\Models\FormResponse;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;


class FormResponseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function fetch(Request $request)
    {
        $uuid = $request->uuid;

        $form = Form::where('uuid', $uuid)->with('responses')->first();
        $data = $form->responses;

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('date', function ($row) {
                return '<span class="hidden">' . strtotime($row->updated_at) . '</span>' . Carbon::parse($row->updated_at)->toDayDateTimeString();
            })
            ->editColumn('title', function ($row) {
                return '#RESPONSE_' . strtotime($row->created_at);
            })
            ->addColumn('action', function ($row) {
                $linkClass = 'inline-flex items-center w-full px-4 py-2 text-sm text-gray-700 disabled:cursor-not-allowed disabled:opacity-25 hover:text-gray-50 hover:bg-gray-100';

                $action =
                    '<div class="relative inline-block text-left">
                        <div class="flex justify-end">
                          <button type="button" class="dropdown-toggle py-2 rounded-md">
                          <span class="material-symbols-outlined dropdown-span" dropdown-log="' . $row->uuid . '">
                            more_vert
                          </span>
                          </button>
                        </div>

                        <div id="dropdown-menu-' . $row->uuid . '" class="hidden dropdown-menu absolute right-0 z-50 mt-2 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                            <button type="button" data-id="' . $row->uuid . '" class="edit ' . $linkClass . '">
                                Edit
                            </button>
                            <button type="button" data-id="' . $row->uuid . '" class="view ' . $linkClass . '">
                                View
                            </button>
                            <button type="button" data-id="' . $row->uuid . '" class="delete ' . $linkClass . '">
                                 Delete
                            </button>
                        </div>
                      </div>
                      ';

                return $action;
            })
            ->rawColumns(['title', 'date', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $formUuid = $request->input('form_uuid');
        if (!$formUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Form UUID is required'
            ], 400);
        }

        $form = Form::where('uuid', $formUuid)->first();
        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => 'Form not found'
            ], 404);
        }

        $schema = $form->schema;

        $validationRules = [];
        $customMessages = [];
        $attributes = [];
        $phoneFieldName = null;

        foreach ($schema as $field) {
            $fieldName = $field['field_name'];
            $inputField = $fieldName;
            $fieldTitle = ucwords(str_replace(['-', '_'], ' ', $field['title']));
            $rules = [];

            $attributes[$inputField] = $fieldTitle;

            if (!empty($field['validators']['required'])) {
                $rules[] = 'required';
                $customMessages["{$inputField}.required"] = "{$fieldTitle} is required.";
            } else {
                $rules[] = 'nullable';
            }

            if (!empty($field['validators']['unique'])) {
                $value = $request->input($inputField);
                if ($value) {
                    $userFieldMap = [
                        'email' => 'email',
                        'phone' => 'mobile_no',
                    ];
                    $dbColumn = $userFieldMap[$fieldName] ?? null;
                    if ($dbColumn && User::where($dbColumn, $value)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            $inputField => ["{$fieldTitle} has already been taken."]
                        ]);
                    }
                }
            }

            switch ($field['type']) {
                case 'text':
                case 'textarea': $rules[] = 'string'; break;
                case 'radio':
                case 'select': $rules[] = 'string'; break;
                case 'number': $rules[] = 'numeric'; break;
                case 'email': $rules[] = 'email'; break;
                case 'checkbox': $rules[] = 'array'; break;
                case 'file':
                    $rules[] = 'file';
                    $rules[] = 'max:2048';
                    if (!empty($field['options'])) {
                        $allowedMimes = array_map('trim', explode(',', strtolower($field['options'])));
                        $rules[] = 'mimes:' . implode(',', $allowedMimes);
                    }
                    break;
                case 'select_course':
                    $rules[] = 'exists:courses,id';
                    break;
                case 'phonenumber':
                    $rules[] = 'phone';
                    $phoneFieldName = $fieldName;
                    break;
            }

            if (!empty($field['rules'])) {
                $rules[] = $field['rules'];
            }

            $validationRules[$inputField] = implode('|', $rules);
        }

        $validated = $request->validate($validationRules, $customMessages, $attributes);

        // ═══════════════════════════════════════════════════════════════════════
        // MANDATORY SECURITY CHECKS — OTP Verification & Email Uniqueness
        // ═══════════════════════════════════════════════════════════════════════
        // These checks run unconditionally for any form with an email field,
        // regardless of the form schema's validators.unique configuration.
        //
        // They serve as the backend's primary defence against:
        //  1. External tools (Postman, curl, etc.) bypassing the frontend OTP flow
        //  2. Duplicate registrations with already-taken email addresses
        //  3. Fabricated otp_verified_emails rows (no otp_code_hash = illegitimate)
        //
        // HOW THIS BLOCKS EXTERNAL TOOL ABUSE:
        //  - The otp_verified_emails table now tracks the FULL lifecycle from
        //    OTP-send time. The otp_code_hash column is populated ONLY by the
        //    legitimate OtpService::store() method when an OTP is actually sent.
        //  - If a row has no otp_code_hash, it was fabricated (e.g. direct DB insert
        //    by an attacker) — we reject it.
        //  - If no row exists at all, the sender never went through the OTP flow.
        //  - Even if an attacker somehow verifies an email, the uniqueness check
        //    below prevents registration if that email is already in the users table.
        // ═══════════════════════════════════════════════════════════════════════

        $emailField = collect($schema)->first(function ($field) {
            return strtolower($field['type']) === 'email';
        });

        $emailValue = null;
        if ($emailField) {
            $emailValue = $request->input($emailField['field_name']);
            if ($emailValue) {
                $emailNormalized = strtolower(trim($emailValue));
                $otpService = app(OtpService::class);

                // CHECK 1: Email Uniqueness — prevent registration with an email that
                // already exists in the users table. Checked FIRST because it's the
                // cheapest query and gives the most helpful error message.
                if (User::where('email', $emailNormalized)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email address is already registered.',
                        'errors'  => [
                            $emailField['field_name'] => ['This email address is already registered. Please use a different email or log in to your existing account.'],
                        ],
                    ], 409);
                }

                // CHECK 2: OTP Verification + Legitimacy proof (single DB query).
                //
                // isVerified() now performs ALL of these checks internally:
                //  a) Row exists in otp_verified_emails
                //  b) otp_code_hash is present (proves the row was created by OtpService::store(),
                //     not fabricated via manual DB insert, SQL injection, or external tools)
                //  c) verified_at is set (OTP was actually verified via code entry or email link)
                //  d) used_at is null (verification hasn't been consumed by a prior registration)
                //  e) Verification is within the configured VERIFIED_TTL
                //
                // If ANY of these fail, the registration is rejected. This single check
                // eliminates the need for a separate otp_code_hash query.
                if (!$otpService->isVerified($emailNormalized)) {
                    // Log extra detail for fabrication detection
                    $record = \App\Models\OtpVerifiedEmail::where('email', $emailNormalized)->first();
                    if ($record && empty($record->otp_code_hash)) {
                        Log::warning('Registration attempt with missing otp_code_hash — possible fabrication', [
                            'email' => $emailNormalized,
                        ]);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Email verification is required before registration.',
                        'errors'  => [
                            'otp' => ['Please verify your email address with the OTP code before submitting.'],
                        ],
                    ], 422);
                }
            }
        }

        foreach ($schema as $field) {
            if ($field['type'] === 'file' && $request->hasFile($field['field_name'])) {
                $file = $request->file($field['field_name']);
                $destinationPath = 'form/uploads/';
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                Storage::disk('public')->putFileAs($destinationPath, $file, $fileName);

                $validated[$field['field_name']] = [
                    'name' => $fileName,
                    'url' => Storage::url($destinationPath . $fileName),
                ];
            }
        }

        $responseData = [];
        foreach ($schema as $field) {
            $fieldName = $field['field_name'];
            $responseData[$fieldName] = $validated[$fieldName] ?? $request->input($fieldName);
        }

        // Wrap form creation + OTP consumption in a transaction so they
        // either both succeed or both roll back. This prevents a state where
        // the form response is saved but OTP remains unconsumed (or vice versa).
        $response = DB::transaction(function () use ($form, $responseData, $emailField, $emailValue) {
            $response = new FormResponse([
                'form_id' => $form->id,
                'response_data' => $responseData,
            ]);
            $form->responses()->save($response);

            // Consume OTP verification — prevents reuse from Postman/curl etc.
            // If consumption fails (no row found), throw to roll back the entire transaction.
            if ($emailField && $emailValue) {
                $consumed = app(OtpService::class)->consumeVerification($emailValue);
                if (!$consumed) {
                    throw new \RuntimeException('Failed to consume OTP verification for: ' . $emailValue);
                }
            }

            return $response;
        });

        FormSubmittedEvent::dispatch($responseData, $response->id, $phoneFieldName);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $response,
        ], 201);
    }







    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        $formResponse = FormResponse::where('uuid', $uuid)->with('form')->first();
        $admissionForm = $formResponse->form;
        $admissionForm->image = $admissionForm->image ? asset('storage/form/banner/' . $admissionForm->image) : null;

        return Inertia::render('FormResponse/Show', compact('formResponse', 'admissionForm'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid)
    {
        $formResponse = FormResponse::where('uuid', $uuid)->with('form')->first();
        $admissionForm = $formResponse->form;
        $admissionForm->image = $admissionForm->image ? asset('storage/form/banner/' . $admissionForm->image) : null;

        return Inertia::render('FormResponse/Edit', compact('formResponse', 'admissionForm'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $uuid)
    {
        $formReponse = FormResponse::where('uuid', $uuid)->with('form')->first();
        $form = $formReponse->form;
        $schema = $form->schema;

        $validationRules = [
            'response_data' => 'required|array',
        ];

        $customMessages = [
            'response_data.required' => 'The form responses are required.',
        ];

        foreach ($schema as $field) {
            $fieldKey = $field['type'] == 'select_course' ? 'response_data.course_id' : "response_data.{$field['field_name']}";

            $rules = [];

            $field['title'] = strtolower($field['title']);

            if (isset($field['validators']['required']) && $field['validators']['required']) {
                $rules[] = 'required';
                $customMessages["{$fieldKey}.required"] = "This field is required.";
            } elseif (isset($field['validators']['unique']) && $field['validators']['unique']) {
                $rules[] = "unique:form_responses,{$field['title']}";
                $customMessages["{$fieldKey}.unique"] = "This field has already been taken.";
            } else {
                $rules[] = 'nullable';
            }

            switch ($field['type']) {
                case 'text':
                case 'textarea':
                    $rules[] = 'string';
                    $customMessages["{$fieldKey}.string"] = "This field must be a string.";
                    break;

                case 'radio':
                case 'select':
                    $rules[] = 'string';
                    $customMessages["{$fieldKey}.string"] = "This field must be a valid option.";
                    break;

                case 'number':
                    $rules[] = 'numeric';
                    $customMessages["{$fieldKey}.numeric"] = "This field must be a number.";
                    break;

                case 'email':
                    $rules[] = 'email';
                    $customMessages["{$fieldKey}.email"] = "This field must be a valid email address.";
                    break;

                case 'checkbox':
                    $rules[] = 'array';
                    $customMessages["{$fieldKey}.array"] = "This field must be an array.";
                    break;

                case 'file':
                    $rules[] = 'file';
                    $rules[] = 'max:2048';

                    if (!empty($field['options'])) {
                        $allowedMimes = array_map('trim', explode(',', strtolower($field['options'])));
                        $rules[] = 'mimes:' . implode(',', $allowedMimes);
                        $customMessages["{$fieldKey}.mimes"] = "Must be a file of type: " . implode(', ', $allowedMimes) . ".";
                    }

                    $customMessages["{$fieldKey}.file"] = "This field must be a file.";
                    $customMessages["{$fieldKey}.max"] = "The file must not be greater than 2MB.";

                    break;

                case 'select_course':
                    $rules[] = 'exists:courses,id';
                    $customMessages["{$fieldKey}.exists"] = "The selected course is invalid";
                    break;

                case 'phonenumber':
                    $rules[] = 'phone';
                    $customMessages["{$fieldKey}.file"] = "The {$field['title']} must be a valid phonenumber.";
                    break;

                default:
                    $rules[] = 'nullable';
                    break;
            }

            $validationRules[$fieldKey] = implode('|', $rules);
        }
        // dd($validationRules);
        $validated = $request->validate($validationRules, $customMessages);

        // Handle file uploads
        foreach ($schema as $field) {
            if ($field['type'] === 'file' && $request->hasFile("response_data.{$field['field_name']}")) {
                $destinationPath = 'form/uploads/';
                $file = $request->file("response_data.{$field['field_name']}");

                $fileName = time() . '.' . $file->getClientOriginalExtension();

                // Delete old image if it exists
                if (\Storage::disk('public')->exists($destinationPath . $fileName)) {
                    \Storage::disk('public')->delete($destinationPath . $fileName);
                }

                // Save new image
                \Storage::disk('public')->putFileAs($destinationPath, $file, $fileName);

                $validated['response_data'][$field['field_name']] = $fileName;
            } else {
                $validated['response_data'][$field['field_name']] = $formReponse['response_data'][$field['field_name']];
            }
        }

        $formReponse->fill($validated)->save();

        return redirect()->route('admin.form.show', $form->uuid);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        $data = FormResponse::where('uuid', $uuid)->first();

        $data->delete();
    }
}
