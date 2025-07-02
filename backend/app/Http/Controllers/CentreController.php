<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Centre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CentreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $centres = Centre::get();
        $branches = Branch::orderBy('title')->get();

        return view('admin.manage_centre', compact('centres', 'branches'));
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        if ($request->ajax()) {

            $validation = Validator::make(
                $request->all(),
                [
                    'branch_id' => 'required',
                    'title' => 'required',
                ],
                [
                    'branch_id.required' => 'The branch field is required.',
                ]
            );

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }

            $input = $request->all();
            Centre::create($input);

            return response()->json([
                'status' => true,
                'message' => 'Centre created successfully!',
                'reload'  => route('admin.centre.index')
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
        $centre = Centre::find($id);
        $branches = Branch::orderBy('title')->get();

        return view('admin.edit_centre', compact('centre', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Centre $centre)
    {
        if ($request->ajax()) {

            $validation = Validator::make(
                $request->all(),
                [
                    'branch_id' => 'required',
                    'title' => 'required',
                ],
                [
                    'branch_id.required' => 'The branch field is required.',
                ]
            );

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }

            $input = $request->all();
            $centre->fill($input)->save();

            return response()->json([
                'status' => true,
                'message' => 'Centre updated successfully!',
                'reload'  => route('admin.centre.index')
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
        $centre = Centre::find($id);
        $centre->delete();

        return redirect()->route('admin.centre.index');
    }
}
