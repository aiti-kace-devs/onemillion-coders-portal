<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\Models\UserAdmission;
use App\Services\Scheduling\CentreBlockBookingGuard;
use App\Services\Scheduling\ConfirmStudentSessionService;
use App\Services\Scheduling\ProgrammeQuotaService;
use App\Services\Scheduling\SessionAlternativesService;
use App\Services\Scheduling\StudentSessionFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentSessionController extends Controller
{
    public function sessionOptions(
        Request $request,
        ProgrammeQuotaService $quotaService,
        SessionAlternativesService $alternativesService,
    ): JsonResponse {
        $validated = $request->validate([
            'preferred_centre_id' => ['nullable', 'integer', 'exists:centres,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'course_session_id' => ['nullable', 'integer', 'exists:course_sessions,id'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'batch_id' => ['nullable', 'integer', 'exists:admission_batches,id'],
        ]);

        $user = $request->user();
        $admission = UserAdmission::query()->where('user_id', $user->userId)->first();
        if (! $admission) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'no_admission',
                    'message' => 'No course admission found for this account.',
                ],
            ], 404);
        }

        $course = Course::query()
            ->with(['programme', 'centre'])
            ->find($admission->course_id);

        if (! $course) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'no_course',
                    'message' => 'Your admission has no valid course.',
                ],
            ], 404);
        }

        if (! empty($validated['course_id']) && (int) $validated['course_id'] !== (int) $course->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'course_mismatch',
                    'message' => 'Course does not match your admission.',
                ],
            ], 409);
        }

        if (! empty($validated['programme_id']) && (int) $validated['programme_id'] !== (int) $course->programme_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'programme_mismatch',
                    'message' => 'Programme does not match your admitted course.',
                ],
            ], 409);
        }

        if (! empty($validated['batch_id']) && (int) $validated['batch_id'] !== (int) $course->batch_id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'batch_mismatch',
                    'message' => 'Batch does not match your admitted course.',
                ],
            ], 409);
        }

        $programme = $course->programme;
        if (! $programme) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'no_programme',
                    'message' => 'Your course has no programme.',
                ],
            ], 404);
        }

        $preferredCentreId = isset($validated['preferred_centre_id'])
            ? (int) $validated['preferred_centre_id']
            : ($course->centre_id ? (int) $course->centre_id : null);

        $quotaInfo = $quotaService->remainingForCourse($programme, $course, $user);
        $blockApplies = CentreBlockBookingGuard::appliesTo($user, $course);
        $blockIds = CentreBlockBookingGuard::applicableBlockIds($course);
        $blockRequired = $blockApplies && $blockIds !== [] && ! CentreBlockBookingGuard::hasConfirmedBooking($user, $course);

        $courseSessions = $this->listCourseSessionsForCourse($course, $programme, $quotaService, $user);

        $evaluationSessionId = $validated['course_session_id'] ?? null;
        $requested = $this->buildRequestedPayload(
            $user,
            $admission,
            $course,
            $programme,
            $quotaInfo,
            $preferredCentreId,
            $evaluationSessionId,
            $blockRequired,
            $quotaService,
        );

        $alternatives = $alternativesService->build($user, $course, $preferredCentreId);

        return response()->json([
            'success' => true,
            'data' => [
                'flow' => StudentSessionFlow::flowLabel($user, $course),
                'attendance_mode' => StudentSessionFlow::isFullyRemoteOnline($user, $course) ? 'fully_remote' : 'centre_based',
                'course_sessions' => $courseSessions,
                'requested' => $requested,
                'alternatives' => $alternatives,
                'meta' => [
                    'generated_at' => now()->toIso8601String(),
                    'cache_ttl_seconds' => (int) config('scheduling.session_options_cache_ttl_seconds', 15),
                    'admission_id' => $admission->id,
                ],
            ],
        ]);
    }

    public function sessionConfirm(Request $request, ConfirmStudentSessionService $confirmService): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:course_sessions,id'],
        ]);

        $user = $request->user();
        $idempotencyKey = $request->header('Idempotency-Key');

        $result = $confirmService->attempt($user, (int) $validated['session_id'], $idempotencyKey);

        if (($result['ok'] ?? false) !== true) {
            $code = $result['error']['code'] ?? 'unknown';
            $status = match ($code) {
                'session_change_disabled', 'session_full', 'programme_quota_full', 'invalid_session', 'block_required' => 409,
                'no_admission', 'no_course', 'no_programme' => 404,
                'server_error' => 500,
                default => 409,
            };

            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], $status);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listCourseSessionsForCourse(
        Course $course,
        $programme,
        ProgrammeQuotaService $quotaService,
        $user,
    ): array {
        $sessions = CourseSession::query()
            ->courseType()
            ->where('course_id', $course->id)
            ->where('status', true)
            ->orderBy('session')
            ->get();

        $wasConfirmed = (bool) UserAdmission::query()
            ->where('user_id', $user->userId)
            ->value('confirmed');

        $quotaOkForNew = $wasConfirmed || $quotaService->hasCapacityForNewConfirmation($programme, $course, $user);

        $out = [];
        foreach ($sessions as $session) {
            $slotsLeft = $session->slotLeft();
            $out[] = [
                'course_session_id' => $session->id,
                'session_label' => $session->session,
                'slots_left' => $slotsLeft,
                'course_time' => $session->course_time,
                'status' => (bool) $session->status,
                'quota_allows_confirm' => $quotaOkForNew,
            ];
        }

        return $out;
    }

    private function buildRequestedPayload(
        User $user,
        UserAdmission $admission,
        Course $course,
        $programme,
        array $quotaInfo,
        ?int $preferredCentreId,
        ?int $evaluationSessionId,
        bool $blockRequired,
        ProgrammeQuotaService $quotaService,
    ): array {
        $preferredTitle = $course->centre?->title;
        if ($preferredCentreId !== null && (int) $preferredCentreId !== (int) $course->centre_id) {
            $preferredTitle = Centre::query()->whereKey($preferredCentreId)->value('title') ?? $preferredTitle;
        }

        $limitsBase = [
            'programme_quota' => [
                'applies' => $quotaInfo['applies'],
                'max' => $quotaInfo['max'],
                'used' => $quotaInfo['used'],
                'remaining' => $quotaInfo['remaining'],
            ],
            'block_required' => $blockRequired,
        ];

        $base = [
            'course_id' => $course->id,
            'course_name' => $course->course_name,
            'programme_id' => $programme->id,
            'programme_title' => $programme->title,
            'batch_id' => $course->batch_id,
            'preferred_centre_id' => $preferredCentreId,
            'preferred_centre_title' => $preferredTitle,
            'limits' => $limitsBase,
        ];

        if ($evaluationSessionId === null) {
            return array_merge($base, [
                'available' => null,
                'reason' => null,
                'course_session_id' => null,
                'session_label' => null,
                'slots_left' => null,
                'session_cap' => null,
            ]);
        }

        $session = CourseSession::query()
            ->where('id', $evaluationSessionId)
            ->where('course_id', $course->id)
            ->courseType()
            ->first();

        if (! $session) {
            return array_merge($base, [
                'available' => false,
                'reason' => 'invalid_session',
                'course_session_id' => $evaluationSessionId,
                'session_label' => null,
                'slots_left' => null,
                'session_cap' => null,
            ]);
        }

        $slotsLeft = $session->slotLeft();
        $wasConfirmed = (bool) $admission->confirmed;
        $quotaAllows = $wasConfirmed || $quotaService->hasCapacityForNewConfirmation($programme, $course, $user);

        $reason = null;
        $available = true;

        if ($blockRequired) {
            $available = false;
            $reason = 'block_required';
        } elseif ($slotsLeft < 1) {
            $available = false;
            $reason = 'session_full';
        } elseif (! $quotaAllows) {
            $available = false;
            $reason = 'programme_quota_full';
        }

        $limits = array_merge($limitsBase, ['session_cap' => $session->limit]);

        return array_merge($base, [
            'available' => $available,
            'reason' => $reason,
            'course_session_id' => $session->id,
            'session_label' => $session->session,
            'slots_left' => $slotsLeft,
            'session_cap' => $session->limit,
            'limits' => $limits,
        ]);
    }
}
