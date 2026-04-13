<?php

namespace App\Http\Controllers;

use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function index(Request $request, AvailabilityService $availabilityService): JsonResponse
    {
        $request->validate([
            'centre_id' => 'required|integer|exists:centres,id',
            'course_id' => 'required|integer|exists:courses,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $centreId = (int) $request->query('centre_id');
        $courseId = (int) $request->query('course_id');
        $from = Carbon::parse($request->query('from'));
        $to = Carbon::parse($request->query('to'));

        $result = $availabilityService->getAvailableSlots($centreId, $courseId, $from, $to);

        return response()->json($result);
    }
}
