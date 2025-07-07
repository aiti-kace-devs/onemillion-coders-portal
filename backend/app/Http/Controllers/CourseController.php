<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Centre;
use App\Models\Course;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $courses = Course::get();
        $branches = Branch::orderBy('title')->get();
        $programmes = Programme::orderBy('title')->get();

        return view('admin.manage_courses', compact('courses', 'branches', 'programmes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function fetchCentre(Request $request)
    {
        $centres = Centre::where('branch_id', $request->branch_id)
            ->orderBy('title')
            ->get(['id', 'title']);
        return response()->json([
            'centres' => $centres
        ]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        if ($request->ajax()) {

            $validation = Validator::make($request->all(), [
                'branch_id' => 'required',
                'centre_id' => 'required',
                'programme_id' => 'required',
                'duration' => 'required',
                'start_date' => 'sometimes',
                'end_date' => 'sometimes',
            ], [
                'branch_id.required' => 'The branch field is required.',
                'centre_id.required' => 'The centre field is required.',
                'programme_id.required' => 'The programme field is required.',
                'duration.required' => 'The duration field is required.',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }

            $input = $request->all();
            $branch_name = Branch::find($input['branch_id'])->title;
            $programme_name = Programme::find($input['programme_id'])->title;

            $input['course_name'] = "$programme_name - ($branch_name)";
            $input['location'] = $branch_name;
            $input['duration'] = $request->duration;
            $input['start_date'] = $request->start_date;;
            $input['end_date'] = $request->end_date;
            Course::create($input);

            return response()->json([
                'status' => true,
                'message' => 'Course created successfully!',
                'reload'  => route('admin.course.index')
            ], 200);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $course = Course::find($id);
        $branches = Branch::orderBy('title')->get();
        $centres = Centre::where('branch_id', $course->centre->branch_id)
            ->orderBy('title')
            ->get();
        $programmes = Programme::orderBy('title')->get();

        return view('admin.edit_course', compact('course', 'branches', 'centres', 'programmes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Course $course)
    {
        if ($request->ajax()) {

            $validation = Validator::make($request->all(), [
                'branch_id' => 'required',
                'centre_id' => 'required',
                'programme_id' => 'required',
                'duration' => 'required',
                'start_date' => 'sometimes',
                'end_date' => 'sometimes',
            ], [
                'branch_id.required' => 'The branch field is required.',
                'centre_id.required' => 'The centre field is required.',
                'programme_id.required' => 'The programme field is required.',
                'duration.required' => 'The duration field is required.',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }

            $input = $request->all();
            $course->fill($input)->save();

            return response()->json([
                'status' => true,
                'message' => 'Course updated successfully!',
                'reload'  => route('admin.course.index')
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::find($id);
        $course->delete();

        return redirect()->route('admin.course.index');
    }



    public function fetchProgrammeDetails(Request $request)
    {
        $programme = Programme::find($request->programme_id);

        if ($programme) {
            return response()->json([
                'duration' => $programme->duration,
                'start_date' => $programme->start_date,
                'end_date' => $programme->end_date,
            ]);
        } else {
            return response()->json(['error' => 'Programme not found'], 404);
        }
    }




}
