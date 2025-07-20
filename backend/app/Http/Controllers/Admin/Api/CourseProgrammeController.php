<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;

class CourseProgrammeController extends Controller
{



    public function index()
    {
        $programmes = Programme::with(['category', 'courseCertification', 'courseModules'])
            ->withCount('courseModules')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $programmes
        ]);
    }





}
