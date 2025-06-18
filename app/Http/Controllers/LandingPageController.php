<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Programme;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    //

    public function index()
    {
        return view('landing-page.index');
    }

    public function availableCourses()
    {
        $categories = CourseCategory::orderBy('title')->with('programmes')->get();
        $programmes = Programme::where('status', true)->get();
        return view('landing-page.home', compact('categories','programmes'));
    }

    public function application()
    {
        return view('landing-page.application');
    }

    public function show($slug)
    {
        $programme = Programme::where('slug', $slug)->firstOrFail();

        return view('landing-page.show-course', compact('programme'));
    }
}
