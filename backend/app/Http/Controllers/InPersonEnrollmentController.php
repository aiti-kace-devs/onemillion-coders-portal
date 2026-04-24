<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use App\Services\AvailabilityService;
use App\Services\GhanaCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InPersonEnrollmentController extends Controller
{
    /**
     * POST /api/in-person-enrollment
     */
    public function store(
        Request $request,
        BookingService $bookingService,
        AvailabilityService $availabilityService,
        GhanaCardService $ghanaCardService
    ): JsonResponse {
        return app(BookingController::class)->store(
            $request,
            $bookingService,
            $availabilityService,
            $ghanaCardService
        );
    }
}
