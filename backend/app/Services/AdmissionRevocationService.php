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
     * Copies the record to revoked_admissions, deletes from user_admission,
     * frees the slot when conditions allow, and removes the student from external partners.
     */
    public function revoke(UserAdmission $admission): void
    {
        $slotFreed      = false;
        $courseId       = $admission->course_id;
        $programmeBatch = null;

        DB::transaction(function () use ($admission, &$slotFreed, &$programmeBatch) {
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

            $slotFreed      = $this->handleSlotOnRevocation($admission, $programmeBatch);
            $admission->delete();
        });

        // Fire event and call partner APIs outside the transaction
        if ($slotFreed && $programmeBatch) {
            event(new AdmissionSlotFreed($courseId, $programmeBatch->id));
        }

        $this->removeFromPartner('Startocode', $admission->user_id, $courseId);
        $this->removeFromPartner('Coursera', $admission->user_id, $courseId);
    }

    private function handleSlotOnRevocation(UserAdmission $admission, ?CourseBatch &$programmeBatch): bool
    {
        if (!$admission->programme_batch_id) {
            return false;
        }

        $programmeBatch = CourseBatch::find($admission->programme_batch_id);
        if (!$programmeBatch) {
            return false;
        }

        // Signed diff: negative = batch already ended
        $daysLeft = Carbon::today()->diffInDays(Carbon::parse($programmeBatch->end_date), false);

        if ($daysLeft <= 7) {
            // Less than 1 week (or already ended) — slot goes to waste
            return false;
        }

        CourseBatch::where('id', $programmeBatch->id)->increment('available_slots');

        return true;
    }

    private function removeFromPartner(string $partner, string $userId, ?int $courseId): void
    {
        try {
            // TODO: Implement {$partner} API call to deactivate the student's enrolment
            Log::info("{$partner} removal triggered", ['user_id' => $userId, 'course_id' => $courseId]);
        } catch (\Throwable $e) {
            Log::error("Failed to remove user from {$partner}", [
                'user_id'   => $userId,
                'course_id' => $courseId,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
