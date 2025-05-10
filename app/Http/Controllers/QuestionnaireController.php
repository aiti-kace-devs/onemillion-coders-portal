<?php

namespace App\Http\Controllers;

use App\Exports\QuestionnaireResponseExport;
use App\Models\Branch;
use App\Models\Course;
use App\Http\Requests\QuestionnaireRequest;
use App\Models\Centre;
use App\Models\Questionnaire;
use Carbon\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class QuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Questionnaire/List');
    }

    /**
     * Show the form for creating a new resource.
     */

    public function fetch()
    {

        $data = Questionnaire::get(['uuid', 'title', 'active', 'updated_at']);
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                return  $row->active ? 'Active' : 'Inactive';
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
            ->rawColumns(['status', 'date', 'action'])
            ->make(true);
    }

    public function create()
    {
        $isCreateMethod = true;
        return Inertia::render('Questionnaire/Form', compact('isCreateMethod'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuestionnaireRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $destinationPath = 'questionnaire/banner/';
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

        Questionnaire::create($validated);

        return redirect()->route('admin.questionnaire.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        $form = Questionnaire::where('uuid', $uuid)->first();

        return Inertia::render('Questionnaire/Show', compact('form'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid)
    {
        $isCreateMethod = false;
        $questionnaire = Questionnaire::where('uuid', $uuid)->firstOrFail();
        $questionnaire->image = $questionnaire->image ? asset('storage/questionnaire/banner/' . $questionnaire->image) : null;

        return Inertia::render('Questionnaire/Form', compact('questionnaire', 'isCreateMethod'));
    }

    public function preview($uuid)
    {
        $questionnaire = Questionnaire::where('uuid', $uuid)->first();
        $questionnaire->image = $questionnaire->image ? asset('storage/questionnaire/banner/' . $questionnaire->image) : null;

        // $courses = [];
        // $branches = [];
        // $centres = [];
        $withLayout = true;

        // if (isset($admissionForm->schema)) {
        //     $courses = Course::orderBy('course_name')->get();
        //     $branches = Branch::orderBy('title')->get();
        //     $centres = Centre::orderBy('title')->get();
        // }

        return Inertia::render('Questionnaire/Preview', compact('questionnaire', 'withLayout'));
    }


    public function submitForm($code)
    {
        $user = \Auth::user();

        if(!$user->isAdmitted() && !$user->hasAttendance()) {
            return redirect(route('student.dashboard'))->with('error', 'You are not allowed to access this form.');
        }

        $questionnaire = Questionnaire::where('code', $code)->first();
        if (!$questionnaire) {
            return redirect('home');
        }

        $questionnaire->image = $questionnaire->image ? asset('storage/questionnaire/banner/' . $questionnaire->image) : null;
        $withLayout = false;

        $courses = Course::join('programmes', 'programmes.id', '=', 'courses.programme_id')
            ->where('courses.status', 1)
            ->where('programmes.status', 1)
            ->select('courses.*')
            ->orderBy('course_name')->get();

        $centres = Centre::where('status', 1)->orderBy('title')->get();

        $branches = Branch::where('status', 1)->orderBy('title')->get();

        return Inertia::render('Questionnaire/Preview', compact('questionnaire', 'courses', 'branches', 'centres', 'withLayout'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuestionnaireRequest $request, $uuid)
    {
        $validated = $request->validated();
        $form = Questionnaire::where('uuid', $uuid)->first();

        // Handle image upload if necessary
        if ($request->isDirty && $request->hasFile('image')) {
            $destinationPath = 'questionnaire/banner/';
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

        return redirect()->route('admin.questionnaire.index');
    }

    public function export($uuid)
    {
        $admissionForm = Questionnaire::where('uuid', $uuid)->with('responses')->first();
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
        $form = Questionnaire::where('uuid', $uuid)->first();

        $form->delete();
    }
}
