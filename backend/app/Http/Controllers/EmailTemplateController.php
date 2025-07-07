<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EmailTemplate::all();
        return view('admin.manage_email_template', compact('templates'));
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
        if ($request->ajax()) {

            $validation = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:email_templates,name',
                'content' => 'required|string',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }

            $input = $request->all();
            $input = [
                'name' => strtoupper($input['name']),
                'content' => $input['content'],
            ];

            EmailTemplate::create($input);

            return response()->json([
                'status' => true,
                'message' => 'Template created successfully!',
                'reload'  => route('admin.email.template.index')
            ], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $template = EmailTemplate::find($id);

        return view('admin.edit_email_template', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $template)
    {
        if ($request->ajax()) {

            $validation = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'content' => 'required|string',
            ]);

            $name = $request->input('name');
            $input = $request->all();



            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validation->errors()->toArray(),
                ], 422);
            }
            $nameMaintained = false;
            if (in_array($template->name, NOT_TO_REMOVE)) {

                $nameMaintained = true;

                $template->fill([
                    'content' => $input['content'],
                    'name' => $template->name,
                ])->save();
            } else {
                $template->fill([
                    'content' => $input['content'],
                    'name' => strtoupper($input['name']),
                ])->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Template updated successfully! ' . $nameMaintained ? 'Name not changed.' : '',
                'reload'  => route('admin.email.template.index')
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $template = EmailTemplate::find($id);
        if (in_array($template->name, NOT_TO_REMOVE)) {
            return redirect()->route('admin.email.template.index')->with([
                'flash' => 'Unable to delete a required template',
                'key' => 'error'
            ]);
        }
        $template->delete();

        return redirect()->route('admin.email.template.index');
    }
}
