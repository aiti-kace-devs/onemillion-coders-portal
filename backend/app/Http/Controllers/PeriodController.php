<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $periods = Period::get();

        return view('admin.manage_period', compact('periods'));
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
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validation->errors()->toArray(),
            ], 422);
        }

        $input = $request->all();
        Period::create($input);

        return response()->json([
            'status' => true,
            'message' => 'Period created successfully!',
            'reload'  => route('admin.period.index')
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
        $period = Period::find($id);

        return view('admin.edit_period', compact('period'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Period $period)
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
            $period->fill($input)->save();
    
            return response()->json([
                'status' => true,
                'message' => 'Period updated successfully!',
                'reload'  => route('admin.period.index')
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
        $period = Period::find($id);
        $period->delete();

        return redirect()->route('admin.period.index');
    }
}
