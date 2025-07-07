<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgrammeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $programmes = Programme::get();

        return view('admin.manage_programme', compact('programmes'));
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
        
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            'duration' => 'required',
            'start_date' => 'sometimes',
            'end_date' => 'sometimes',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validation->errors()->toArray(),
            ], 422);
        }

        $input = new Programme();
        $input->title = $request->title;
        $input->duration = $request->duration;
        $input->start_date = $request->start_date;
        $input->end_date = $request->end_date;
        $input->status = 1;
        $input->save();
        // $input = $request->all();
        // $input->status = 1;
        // Programme::create($input);

        return response()->json([
            'status' => true,
            'message' => 'Programme created successfully!',
            'reload'  => route('admin.programme.index')
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
        $programme = Programme::find($id);

        return view('admin.edit_programme', compact('programme'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Programme $programme)
    {
        if ($request->ajax()) {
        
            $validation = Validator::make($request->all(), [
                'title' => 'required',
            ]);
    
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }
    
            $input = $request->all();
            $programme->fill($input)->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Programme updated successfully!',
                'reload'  => route('admin.programme.index')
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
        $programme = Programme::find($id);
        $programme->delete();

        return redirect()->route('admin.programme.index');
    }
}
