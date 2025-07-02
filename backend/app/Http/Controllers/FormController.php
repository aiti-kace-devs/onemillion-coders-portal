<?php

namespace App\Http\Controllers;

use App\Exports\FormResponseExport;
use App\Models\Branch;
use App\Models\Course;
use App\Http\Requests\DynamicFormRequest;
use App\Models\Centre;
use App\Models\Form;
use Carbon\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Form/List');
    }

    /**
     * Show the form for creating a new resource.
     */

    public function fetch()
    {

        $data = Form::get(['uuid', 'title', 'active', 'updated_at']);
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('date', function ($row) {
                return '<span class="hidden">' . strtotime($row->updated_at) . '</span>' . Carbon::parse($row->updated_at)->toDayDateTimeString();
            })
            ->editColumn('active', function ($row) {
                return '<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">' . $row->active ? 'Active' : 'Inactive' . '</span>';
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

                        <div id="dropdown-menu-' . $row->uuid . '" class="hidden dropdown-menu fixed right-0 z-50 mt-1 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                            <button type="button" data-id="' . $row->uuid . '" class="edit ' . $linkClass . '">
                                Edit
                            </button>
                            <button type="button" data-id="' . $row->uuid . '" class="preview ' . $linkClass . '">
                                Preview
                            </button>
                            <button type="button" data-id="' . $row->uuid . '" class="responses ' . $linkClass . '">
                                Responses
                            </button>
                            <button type="button" data-id="' . $row->uuid . '" class="delete ' . $linkClass . '">
                                 Delete
                            </button>
                        </div>
                      </div>
                      ';

                return $action;
            })
            ->rawColumns(['date', 'action'])
            ->make(true);
    }

    public function create()
    {
        return Inertia::render('Form/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DynamicFormRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $destinationPath = 'form/banner/';
            $image = $request->file('image');
            $fileName = time() . '.' . $image->getClientOriginalExtension();

            // Delete old image if it exists
            if (\Storage::disk('public')->exists($destinationPath . $fileName)) {
                \Storage::disk('public')->delete($destinationPath . $fileName);
            }

            // Save new image
            \Storage::disk('public')->putFileAs($destinationPath, $image, $fileName);
            $validated['image'] = $fileName;
        }

        Form::create($validated);

        return redirect()->route('admin.form.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        $form = Form::where('uuid', $uuid)->first();

        return Inertia::render('Form/Show', compact('form'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid)
    {
        $admissionForm = Form::where('uuid', $uuid)->firstOrFail();
        $admissionForm->image = $admissionForm->image ? asset('storage/form/banner/' . $admissionForm->image) : null;

        return Inertia::render('Form/Edit', compact('admissionForm'));
    }

    public function preview($uuid)
    {
        $admissionForm = Form::where('uuid', $uuid)->first();
        $admissionForm->image = $admissionForm->image ? asset('storage/form/banner/' . $admissionForm->image) : null;

        $courses = [];
        $branches = [];
        $centres = [];
        $withLayout = true;

        if (isset($admissionForm->schema)) {
            $courses = Course::orderBy('course_name')->get();
            $branches = Branch::orderBy('title')->get();
            $centres = Centre::orderBy('title')->get();
        }
        return Inertia::render('Form/Preview', compact('admissionForm', 'courses', 'branches', 'centres', 'withLayout'));
    }


    public function submitForm($formCode)
    {
        // 679c89bf-91ec-488e-9878-0d010468ca3e
        $admissionForm = Form::where('code', $formCode)->first();
        if (!$admissionForm) {
            return redirect('home');
        }

        $admissionForm->image = $admissionForm->image ? asset('storage/form/banner/' . $admissionForm->image) : null;
        $withLayout = false;

        $courses = Course::join('programmes', 'programmes.id', '=', 'courses.programme_id')
            ->where('courses.status', 1)
            ->where('programmes.status', 1)
            ->select('courses.*')
            ->orderBy('course_name')->get();

        $centres = Centre::where('status', 1)->orderBy('title')->get();

        $branches = Branch::where('status', 1)->orderBy('title')->get();

        return Inertia::render('Form/Preview', compact('admissionForm', 'courses', 'branches', 'centres', 'withLayout'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DynamicFormRequest $request, $uuid)
    {
        $validated = $request->validated();
        $form = Form::where('uuid', $uuid)->first();

        // Handle image upload if necessary
        if ($request->isDirty && $request->hasFile('image')) {
            $destinationPath = 'form/banner/';
            $image = $request->file('image');
            $fileName = time() . '.' . $image->getClientOriginalExtension();

            // Delete old image if it exists
            if ($form->image && \Storage::disk('public')->exists($destinationPath . $form->image)) {
                \Storage::disk('public')->delete($destinationPath . $form->image);
            }

            // Save new image
            \Storage::disk('public')->putFileAs($destinationPath, $image, $fileName);
            $validated['image'] = $fileName;
        } else {
            // Retain existing image
            $validated['image'] = $form->image;
        }

        $form->update($validated);

        return redirect()->route('admin.form.index');
    }

    public function export($uuid)
    {
        $admissionForm = Form::where('uuid', $uuid)->with('responses')->first();
        $schema = collect($admissionForm->schema);

        $headers = $schema
            ->pluck('title')
            ->toArray();

        $replacements = [
            'course' => 'course_id'
        ];

        $fieldNames = $schema
            ->pluck('field_name')
            ->map(function ($field) use ($replacements) {
                return $replacements[$field] ?? $field;
            })
            ->toArray();

        $responses = $admissionForm->responses->map(function ($response) use ($fieldNames) {
            return collect($fieldNames)
                ->mapWithKeys(function ($field) use ($response) {
                    $value = $response->response_data[$field]
                        ?? $response->{$field}
                        ?? null;

                    if ($field === 'course_id' && $value) {
                        $value = Course::find($value)->course_name ?? $value;
                    }

                    return [$field => $value];
                })
                ->toArray();
        });

        return Excel::download(new FormResponseExport($headers, $responses), $admissionForm->title . '_data.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        $form = Form::where('uuid', $uuid)->first();

        $form->delete();
    }
}
