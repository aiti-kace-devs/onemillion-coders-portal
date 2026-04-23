<?php

namespace App\Services;

use App\Jobs\AdmitToPartnerPlatformJob;
use App\Models\AppConfig;
use App\Models\Booking;
use App\Models\PartnerStudentAdmission;
use App\Models\Programme;
use App\Models\User;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerAdmissionService
{
    /**
     * Handle enrollment into a partner platform.
     */
    public function handleEnrollment(User $user, Programme $programme, ?Booking $booking = null): void
    {
        if (!$programme->partner_id) {
            return;
        }

        $autoAdmit = (bool) AppConfig::getValue('AUTO_ADMIT', false);
        if (!$autoAdmit) {
            Log::info("AUTO_ADMIT is off. Skipping automatic partner enrollment for user {$user->userId}.");
            return;
        }

        $delay = $this->calculateEnrollmentDelay($programme, $booking);

        if ($delay > 0) {
            AdmitToPartnerPlatformJob::dispatch($user, $programme)->delay(now()->addSeconds($delay));
            Log::info("Scheduled partner enrollment for user {$user->userId} in {$delay} seconds.");
        } else {
            AdmitToPartnerPlatformJob::dispatch($user, $programme);
            Log::info("Dispatched immediate partner enrollment for user {$user->userId}.");
        }
    }

    /**
     * Calculate delay in seconds based on business rules.
     */
    protected function calculateEnrollmentDelay(Programme $programme, ?Booking $booking = null): int
    {
        $now = now();

        // Use batch start date if available
        $startDate = null;
        if ($booking && $booking->programmeBatch) {
            $startDate = $booking->programmeBatch->start_date;
        } elseif ($programme->start_date) {
            $startDate = Carbon::parse($programme->start_date);
        }

        if (!$startDate || $now->greaterThanOrEqualTo($startDate)) {
            return 0; // Immediate
        }

        $bookedAt = $booking ? $booking->booked_at : $now;
        $dayAfterBooking = $bookedAt->copy()->addDay();
        $threeDaysBeforeStart = $startDate->copy()->subDays(3);

        if ($threeDaysBeforeStart->isPast()) {
            return 0; // Within 3 days of start, immediate
        }

        // Target date is the earlier of the two
        $targetDate = $dayAfterBooking->min($threeDaysBeforeStart);

        return max(0, $now->diffInSeconds($targetDate));
    }

    /**
     * Get aggregate enrollment statistics for partner programmes.
     */
    public function getEnrollmentStats(): Collection
    {
        // cache response for cache ttl() minutes
        return Cache::remember('_enrollment_stats:all', cache_flexible_ttl(), function () {
            return Programme::whereNotNull('partner_id')
                ->with('partner')
                ->withCount(['partnerStudentAdmissions as enrolled_count'])
                ->withCount(['admissions as awaiting_count' => function ($query) {
                    $query->whereNotNull('confirmed')
                        // No manual join needed! hasManyThrough handles it.
                        ->whereDoesntHave('user.partnerAdmissions', function ($q) {
                            // Reference the 'courses' table that is already joined by the relationship
                            $q->whereColumn('partner_student_admissions.programme_id', 'courses.programme_id')
                                ->where('enrollment_status', 'enrolled');
                        });
                }])
                ->get();
        });
    }

    /**
     * Bulk enroll awaiting students into partner platforms.
     *
     * @param int|null $programmeId If provided, only enrolls for that specific programme.
     */
    public function enrolAwaitingStudents(?int $programmeId = null): int
    {
        $programmeIds = Programme::whereNotNull('partner_id')
            ->when($programmeId, fn($q) => $q->where('id', $programmeId))
            ->pluck('id');

        $totalDispatched = 0;

        foreach ($programmeIds as $id) {
            $programme = Programme::find($id);

            // 2. Use 'chunk' to process users without exhausting RAM
            // This query finds users confirmed for this programme but not yet in partner_admissions
            User::whereHas('admissions', function ($query) use ($id) {
                $query->whereNotNull('confirmed')
                    ->whereHas('programmeBatch', fn($q) => $q->where('programme_id', $id));
            })
                ->whereDoesntHave('partnerAdmissions', function ($query) use ($id) {
                    $query->where('programme_id', $id)
                        ->where('enrollment_status', 'enrolled');
                })
                ->chunk(200, function ($users) use ($programme, &$totalDispatched) {
                    foreach ($users as $user) {
                        AdmitToPartnerPlatformJob::dispatch($user, $programme);
                        $totalDispatched++;
                    }
                });

            if ($totalDispatched > 0) {
                Log::info("Dispatched admissions for programme: {$programme->title}");
            }
        }

        return $totalDispatched;
    }
}
