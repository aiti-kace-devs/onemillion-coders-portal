<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Course;
use App\Services\CourseRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SlotRecommendationApiController extends Controller
{
    public function __construct(private CourseRecommendationService $recommendationService) {}

    /**
     * POST /api/slots/recommend
     *
     * Returns a priority-ordered recommendation when a course slot is unavailable.
     */
    public function recommend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'   => 'required|string|exists:users,userId',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = Course::with(['centre.branch', 'programme'])->findOrFail((int) $data['course_id']);

        $recommendation = $this->recommendationService->findAlternative($course, $data['user_id']);

        return response()->json([
            'status'         => 'success',
            'recommendation' => $recommendation,
        ]);
    }
}
