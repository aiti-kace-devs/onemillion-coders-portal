<?php

namespace App\Services;

use App\Models\AdmissionRejection;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserAdmission;
use App\Http\Controllers\NotificationController;
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
                'source' => 'ADMIN',
                'rejected_at' => now(),
            ]);

            User::where('userId', $userId)->update(['shortlist' => 0]);

            $user = User::where('userId', $userId)->first();
            $course = \App\Models\Course::find($courseId);
            if ($user && $course) {
                $cooldownHours = (int) \App\Models\AppConfig::getValue('ADMISSION_REVOCATION_COOLDOWN_HOURS', 24);
                $cooldownEndTime = now()->addHours($cooldownHours);

                NotificationController::notify(
                    $user->id,
                    'ADMISSION_REVOKED',
                    'Admission Revoked',
                    "You have revoked your admission for {$course->course_name}. "
                    . "You must wait {$cooldownHours} hours before selecting a new course. "
                    . "You can select a new course after " . $cooldownEndTime->format('l jS F, Y g:i A') . "."
                );
            }

            return [
                'slot_restored' => $slotRestored,
                'rejection' => $rejection,
            ];
        });
    }

}
