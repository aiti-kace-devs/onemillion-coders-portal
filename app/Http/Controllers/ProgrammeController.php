<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgrammeRequest;
use App\Models\CourseCategory;
use App\Models\Programme;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use League\CommonMark\CommonMarkConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Yajra\DataTables\Facades\DataTables;


class ProgrammeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Programme/List');
    }

    public function fetch()
    {
        $data = Programme::get();;
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('category', function ($row) {
                return  $row->course_category?->title;
            })
            ->editColumn('start_date', function ($row) {
                return '<span class="hidden">' . strtotime($row->start_date) . '</span>' . Carbon::parse($row->start_date)->toFormattedDayDateString();
            })
            ->editColumn('end_date', function ($row) {
                return '<span class="hidden">' . strtotime($row->end_date) . '</span>' . Carbon::parse($row->end_date)->toFormattedDayDateString();
            })
            ->editColumn('status', function($row){
                return '<input
                type="checkbox"
                value="' . $row->status . '"
                v-model="proxyChecked"
                class="rounded-sm w-5 h-5 border-gray-700 text-gray-700 shadow-sm focus:ring-gray-500"
            />';
            })
            ->addColumn('action', function ($row) {
                $linkClass = 'inline-flex items-center w-full px-4 py-2 text-sm text-gray-700 disabled:cursor-not-allowed disabled:opacity-25 hover:text-gray-50 hover:bg-gray-100';

                $action =
                    '<div class="relative inline-block text-left">
                        <div class="flex justify-end">
                          <button type="button" class="dropdown-toggle py-2 rounded-md">
                          <span class="material-symbols-outlined dropdown-span" dropdown-log="' . $row->id . '">
                            more_vert
                          </span>
                          </button>
                        </div>

                        <div id="dropdown-menu-' . $row->id . '" class="hidden dropdown-menu absolute right-0 z-50 mt-2 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                            <button type="button" data-id="' . $row->id . '" class="edit ' . $linkClass . '">
                                Edit
                            </button>
                            
                            <button type="button" data-id="' . $row->id . '" class="delete ' . $linkClass . '">
                                 Delete
                            </button>
                        </div>
                      </div>
                      ';

                return $action;
            })
            ->rawColumns(['start_date', 'end_date', 'status', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $isCreateMethod = true;
        $categories = CourseCategory::orderBy('title')->get();

        return Inertia::render('Programme/Form', compact('isCreateMethod', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(ProgrammeRequest $request)
    {
        $validated = $request->validated();

        if (!empty($validated->content)) {
            $converter = new CommonMarkConverter();
            $validated->content = $converter->convert($validated->content)->getContent();
        }

        if ($request->hasFile('image')) {
            $destinationPath = 'programme/';
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

        $validated['slug'] = \Str::slug($validated['title']);

        Programme::create($validated);

        return redirect()->route('admin.programme.index');
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
        $isCreateMethod = false;
        $programme = Programme::find($id);
        $categories = CourseCategory::orderBy('title')->get();

        return Inertia::render('Programme/Form', compact('programme', 'categories', 'isCreateMethod'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProgrammeRequest $request, Programme $programme)
    {
        $validated = $request->validated();

        if (!empty($validated['content'])) {
            $converter = new CommonMarkConverter();
            $validated['content'] = $converter->convert($validated['content'])->getContent();
        }

        // Handle image upload if necessary
        if ($request->isDirty && $request->hasFile('image')) {
            $destinationPath = 'programme/';
            $image = $request->file('image');
            $fileName = time() . '.' . $image->getClientOriginalExtension();

            // Delete old image if it exists
            if ($programme->image && \Storage::disk('public')->exists($destinationPath . $programme->image)) {
                \Storage::disk('public')->delete($destinationPath . $programme->image);
            }

            // Save new image
            \Storage::disk('public')->putFileAs($destinationPath, $image, $fileName);
            $validated['image'] = $fileName;
        } else {
            // Retain existing image
            $validated['image'] = $programme->image;
        }

        $validated['slug'] = \Str::slug($validated['title']);

        $programme->fill($validated)->save();

        return redirect()->route('admin.programme.index');
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

        $destinationPath = 'programme/';

        if ($programme->image && \Storage::disk('public')->exists($destinationPath . $programme->image)) {
            \Storage::disk('public')->delete($destinationPath . $programme->image);
        }

        $programme->delete();
    }
}
