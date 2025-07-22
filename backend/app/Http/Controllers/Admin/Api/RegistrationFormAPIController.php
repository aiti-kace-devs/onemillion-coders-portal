<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\Form;
use App\Models\Course;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RegistrationFormAPIController extends Controller
{




    public function index(Request $request)
    {
        $form = Form::all();

        return response()->json([
            'success' => true,
            'data' => $form
        ]);

    }





}
