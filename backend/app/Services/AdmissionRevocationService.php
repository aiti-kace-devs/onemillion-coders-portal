<?php

namespace App\Services;

use App\Models\AdmissionRejection;
use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\DB;

class AdmissionRevocationService
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    /**
     * Revoke a user's admission: restore the programme_batch slot (if eligible),
     * fire AdmissionSlotFreed, record the rejection, and clear the shortlist flag.
     *
     * @return array{slot_restored: bool, rejection: AdmissionRejection}
     */
    public function revoke(UserAdmission $admission): array
    {
        return DB::transaction(function () use ($admission) {
            $userId = $admission->user_id;
            $courseId = $admission->course_id;

            $slotRestored = $this->bookingService->cancel($admission);

            $rejection = AdmissionRejection::create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'rejected_at' => now(),
            ]);

            User::where('userId', $userId)->update(['shortlist' => 0]);

            return [
                'slot_restored' => $slotRestored,
                'rejection' => $rejection,
            ];
        });
    }
}
