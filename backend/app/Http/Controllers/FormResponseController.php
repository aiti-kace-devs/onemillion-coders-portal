<?php

namespace App\Http\Controllers;

use App\Events\FormSubmittedEvent;
use App\Models\Form;
use App\Models\User;
use App\Models\FormResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

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
        $form = Form::where('uuid', $request->form_uuid)->firstOrFail();
        $schema = $form->schema;

        // $validationRules = $form->getValidationRules();
        $validationRules = [
            'response_data' => 'required|array',
        ];

        $customMessages = [
            'response_data.required' => 'The form responses are required.',
        ];


        $formattedData = [];
        $attributes = [];

        foreach ($request->input('response_data', []) as $key => $value) {
            foreach ($schema as $field) {
                if (strcasecmp($key, $field['title']) == 0) {
                    $formattedData[$field['field_name']] = trim($value);
                    break;
                }
            }
        }

        foreach ($schema as $field) {
            $fieldKey = $field['type'] == 'select_course' ? 'response_data.course_id' : "response_data.{$field['field_name']}";

            $fieldTitle = ucwords(str_replace('_', ' ', $field['title']));

            $rules = [];

            $attributes[$fieldKey] = Str::remove('_id', Str::remove('response_data.', $fieldKey, true));

            if (!empty($field['validators']['required'])) {
                $rules[] = 'required';
                $customMessages["{$fieldKey}.required"] = "{$fieldTitle} is required.";
            }

            if (!empty($field['validators']['unique'])) {
                $valueToCheck = $formattedData[$field['field_name']] ?? null;
            
                if (!empty($valueToCheck)) {
                    $userFieldMap = [
                        'email' => 'email',
                        'phone' => 'mobile_no',
                    ];
            
                    $dbColumn = $userFieldMap[$field['field_name']] ?? null;
            
                    if ($dbColumn) {
                        $exists = User::where($dbColumn, $valueToCheck)->exists();
            
                        if ($exists) {
                            return redirect()->back()->withInput()->withErrors([
                                $fieldKey => ["{$fieldTitle} has already been taken."]
                            ]);
                        }
                    }
                }
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
                    $customMessages["{$fieldKey}.phone"] = "This must be a valid phonenumber.";
                    $fieldName = $field['field_name'];
                    break;

                default:
                    $rules[] = 'nullable';
                    break;
            }

            $validationRules[$fieldKey] = implode('|', $rules);
            $additionRules = Str::length($field['rules'] ?? '') > 0 ? '|' . $field['rules'] ?? '' : '';
            $validationRules[$fieldKey] =  $validationRules[$fieldKey] . $additionRules;
        }

        // dd($validationRules, $attributes);
        $validated = $request->validate($validationRules, $customMessages, $attributes);

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
            }
        }

        $response = new FormResponse($validated);

        $form->responses()->save($response);

        // Log::info($validated['response_data']);
        // Log::info($fieldName);

        FormSubmittedEvent::dispatch($validated['response_data'], $response->id, $fieldName);
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
        dd($validationRules);
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
