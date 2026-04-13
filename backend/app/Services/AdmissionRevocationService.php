<?php

namespace App\Services;

use App\Events\AdmissionSlotFreed;
use App\Models\CourseBatch;
use App\Models\RevokedAdmission;
use App\Models\UserAdmission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdmissionRevocationService
{
    /**
     * Revoke a student's admission.
     *
     * - Copies record to revoked_admissions.
     * - Deletes from user_admission.
     * - Removes the student from external partners (Startocode, Coursera).
     * - Frees the slot on the programme_batch if conditions are met.
     */
    public function revoke(UserAdmission $admission): void
    {
        DB::transaction(function () use ($admission) {
            // 1. Keep the revoked record
            RevokedAdmission::create([
                'user_id'                 => $admission->user_id,
                'course_id'               => $admission->course_id,
                'batch_id'                => $admission->batch_id,
                'programme_batch_id'      => $admission->programme_batch_id,
                'session'                 => $admission->session,
                'location'                => $admission->location,
                'originally_confirmed_at' => $admission->confirmed,
                'revoked_at'              => now(),
            ]);

            // 2. Handle slot freeing based on time left to course completion
            $this->handleSlotOnRevocation($admission);

            // 3. Remove from user_admission
            $admission->delete();
        });

        // 4. Remove from external partners (outside transaction — side effects)
        $this->removeFromPartners($admission->user_id, $admission->course_id);
    }

    // ─── Slot freeing logic ───────────────────────────────────────────────────

    private function handleSlotOnRevocation(UserAdmission $admission): void
    {
        $programmeBatch = $admission->programme_batch_id
            ? CourseBatch::find($admission->programme_batch_id)
            : null;

        if (!$programmeBatch) {
            return;
        }

        $endDate     = Carbon::parse($programmeBatch->end_date);
        $today       = Carbon::today();
        $daysLeft    = $today->diffInDays($endDate, false); // negative = past

        if ($daysLeft <= 0) {
            // Course already completed — slot goes to waste
            return;
        }

        if ($daysLeft <= 7) {
            // Less than 1 week left — slot goes to waste (no time to allocate)
            return;
        }

        // 1 week or 2 weeks left — free the slot (increment available_slots)
        // The slot will be surfaced to short-course seekers via QuotaService
        CourseBatch::where('id', $programmeBatch->id)->increment('available_slots');

        event(new AdmissionSlotFreed($admission->course_id, $programmeBatch->id));
    }

    // ─── External partner removal ─────────────────────────────────────────────

    private function removeFromPartners(string $userId, ?int $courseId): void
    {
        $this->removeFromStartocode($userId, $courseId);
        $this->removeFromCoursera($userId, $courseId);
    }

    private function removeFromStartocode(string $userId, ?int $courseId): void
    {
        try {
            // TODO: Implement Startocode API call to deactivate the student's account/enrolment
            Log::info("Startocode removal triggered for user [{$userId}], course [{$courseId}]");
        } catch (\Throwable $e) {
            Log::error("Failed to remove user [{$userId}] from Startocode: " . $e->getMessage());
        }
    }

    private function removeFromCoursera(string $userId, ?int $courseId): void
    {
        try {
            // TODO: Implement Coursera API call to unenroll the student
            Log::info("Coursera removal triggered for user [{$userId}], course [{$courseId}]");
        } catch (\Throwable $e) {
            Log::error("Failed to remove user [{$userId}] from Coursera: " . $e->getMessage());
        }
    }
}
