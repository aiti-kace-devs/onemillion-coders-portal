<?php

namespace App\Services\Scheduling;

use App\Jobs\AdmitStudentJob;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmStudentSessionService
{
    public function __construct(
        private ProgrammeQuotaService $quotaService,
    ) {}

    /**
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: array{code: string, message: string}}
     */
    public function attempt(User $user, int $sessionId, ?string $idempotencyKey = null): array
    {
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $cacheKey = $this->idempotencyCacheKey($user, $idempotencyKey);
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && ($cached['ok'] ?? false) === true) {
                return $cached;
            }
        }

        $admission = UserAdmission::where('user_id', $user->userId)->first();
        if (! $admission) {
            return $this->fail('no_admission', 'No course admission found for this account.');
        }

        $course = Course::query()->with('programme')->find($admission->course_id);
        if (! $course) {
            return $this->fail('no_course', 'Your admission has no valid course.');
        }

        $programme = $course->programme;
        if (! $programme) {
            return $this->fail('no_programme', 'Your course has no programme.');
        }

        $wasConfirmed = (bool) $admission->confirmed;
        $hadSessionAlready = (bool) $admission->session;
        $changingSession = $wasConfirmed && $hadSessionAlready;

        if ($changingSession && ! config(ALLOW_SESSION_CHANGE, false)) {
            return $this->fail('session_change_disabled', 'Unable to change session at this time. Contact administrator.');
        }

        if (! CentreBlockBookingGuard::passes($user, $course)) {
            return $this->fail('block_required', 'You must complete a centre time-slot booking before confirming this session.');
        }

        try {
            $payload = DB::transaction(function () use ($user, $sessionId, $admission, $course, $programme, $wasConfirmed, $hadSessionAlready) {
                /** @var CourseSession|null $session */
                $session = CourseSession::query()->lockForUpdate()->find($sessionId);

                if (! $session || (int) $session->course_id !== (int) $course->id) {
                    return $this->fail('invalid_session', 'That session is not available for your course.');
                }

                if ($session->slotLeft() < 1) {
                    return $this->fail('session_full', 'No slots available for this session.');
                }

                if (! $wasConfirmed) {
                    if (! $this->quotaService->hasCapacityForNewConfirmation($programme, $course, $user)) {
                        return $this->fail('programme_quota_full', 'This programme has reached its enrolment limit for your selection.');
                    }
                }

                $admission->confirmed = now();
                $admission->session = $session->id;
                $admission->save();

                $changing = $wasConfirmed && $hadSessionAlready;

                return [
                    'ok' => true,
                    'data' => [
                        'admission_id' => $admission->id,
                        'course_session_id' => $session->id,
                        'session_name' => $session->name,
                        'confirmed_at' => $admission->confirmed?->toIso8601String(),
                        'changed_session' => $changing,
                    ],
                    'session' => $session,
                    'course' => $course,
                    'changing_session' => $changing,
                    'was_confirmed_before' => $wasConfirmed,
                ];
            });
        } catch (\Throwable $e) {
            Log::error($e);

            return $this->fail('server_error', 'Unable to confirm session. Try again later.');
        }

        if (($payload['ok'] ?? false) !== true) {
            return $payload;
        }

        $this->afterSuccess($user, $admission, $payload);

        $response = [
            'ok' => true,
            'data' => $payload['data'],
        ];

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            Cache::put($this->idempotencyCacheKey($user, $idempotencyKey), $response, now()->addDay());
        }

        return $response;
    }

    private function afterSuccess(User $user, UserAdmission $admission, array $payload): void
    {
        $session = $payload['session'];
        $course = $payload['course'];
        $changingSession = $payload['changing_session'];

        if (! $changingSession) {
            AdmitStudentJob::dispatch($admission);
            activity('user_admission')
                ->causedBy($user)
                ->performedOn($admission)
                ->withProperties([
                    'session' => $session->name,
                    'course' => $course->course_name,
                ])
                ->event('Session Confirmed')
                ->log("{$user->name} confirmed their session: {$session->name}");
        } else {
            activity('user_admission')
                ->causedBy($user)
                ->performedOn($admission)
                ->withProperties([
                    'session' => $session->name,
                    'course' => $course->course_name,
                ])
                ->event('Session Changed')
                ->log("{$user->name} changed their session to: {$session->name}");
        }
    }

    private function idempotencyCacheKey(User $user, string $key): string
    {
        return 'session_confirm:'.hash('sha256', $user->userId.'|'.$key);
    }

    /**
     * @return array{ok: false, error: array{code: string, message: string}}
     */
    private function fail(string $code, string $message): array
    {
        return [
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
