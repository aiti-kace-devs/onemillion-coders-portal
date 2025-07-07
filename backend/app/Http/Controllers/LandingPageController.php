<?php

namespace App\Http\Controllers;

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
        return view('landing-page.home');
    }

    public function application()
    {
        return view('landing-page.application');
    }

    public function courseView($course)
    {
        $validCourses = [
            'cybersecurity-course' => 'landing-page.cybersecurity',
            'ai-course' => 'landing-page.ai',
            'data-protection-course' => 'landing-page.data-protection',
            'protection-expert-course' => 'landing-page.protection-expert',
            'protection-sup-course' => 'landing-page.protection-sup',
            'certified-dpf-course' => 'landing-page.certified-dpf',
            'cnst-course' => 'landing-page.cnst',
        ];

        if (!array_key_exists($course, $validCourses)) {
            abort(404);
        }

        return view($validCourses[$course]);
    }
}
