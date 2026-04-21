<?php

namespace App\Services;

use App\Models\AdmissionRejection;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\DB;

class AdmissionRevocationService
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    /**
     * Revoke a user's admission: cancel any linked booking (restoring the slot if eligible),
     * record the rejection, and clear the shortlist flag. The UserAdmission row is deleted.
     *
     * @return array{slot_restored: bool, rejection: AdmissionRejection}
     */
    public function revoke(UserAdmission $admission): array
    {
        return DB::transaction(function () use ($admission) {
            $userId = $admission->user_id;
            $courseId = $admission->course_id;

            $slotRestored = false;

            $bookings = Booking::confirmed()
                ->with('programmeBatch')
                ->where('user_id', $userId)
                ->get();
            foreach ($bookings as $booking) {
                if ($this->bookingService->cancel($booking)) {
                    $slotRestored = true;
                }
            }

            $admission->delete();

            $rejection = AdmissionRejection::create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'revoked_by' => 'admin',
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
