<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\AdmissionWaitlist;
use App\Services\CourseRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WaitlistApiController extends Controller
{
    public function __construct(private CourseRecommendationService $recommendationService) {}

    /**
     * POST /api/waitlist/join
     */
    public function join(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'   => 'required|string|exists:users,userId',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $created = $this->recommendationService->addToWaitlist($data['user_id'], (int) $data['course_id']);

        return response()->json([
            'status'  => 'success',
            'created' => $created,
            'message' => $created
                ? 'You have been added to the waitlist.'
                : 'You are already on the waitlist for this course.',
        ]);
    }

    /**
     * DELETE /api/waitlist/leave
     */
    public function leave(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'   => 'required|string|exists:users,userId',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $this->recommendationService->removeFromWaitlist($data['user_id'], (int) $data['course_id']);

        return response()->json([
            'status'  => 'success',
            'message' => 'You have been removed from the waitlist.',
        ]);
    }

    /**
     * GET /api/waitlist/status?user_id=&course_id=
     */
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'   => 'required|string',
            'course_id' => 'required|integer',
        ]);

        $onWaitlist = AdmissionWaitlist::where('user_id', $data['user_id'])
            ->where('course_id', (int) $data['course_id'])
            ->exists();

        return response()->json([
            'status'      => 'success',
            'on_waitlist' => $onWaitlist,
        ]);
    }
}
