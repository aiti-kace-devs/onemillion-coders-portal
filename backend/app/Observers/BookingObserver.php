<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\UserAdmission;

class BookingObserver
{
    /**
     * Safety net for bookings created outside BookingService (Backpack CRUD, seeders).
     * The service path already sets user_admission_id and updates the admission mirror,
     * so this short-circuits in normal operation.
     */
    public function created(Booking $booking): void
    {
       
        $admission = UserAdmission::where('user_id', $booking->user_id)->first();
        if (!$admission) {
            return;
        }

        $admission->update([
            'programme_batch_id' => $booking->programme_batch_id,
            'course_id' => $booking->course_id,
        ]);

        $booking->user_admission_id = $admission->id;
        $booking->saveQuietly();
    }

    // public function deleted(Booking $booking): void
    // {
    //     if ($booking->user_admission_id) {
    //         UserAdmission::where('id', $booking->user_admission_id)
    //             ->update(['programme_batch_id' => null]);
    //         return;
    //     }

    //     UserAdmission::where('user_id', $booking->user_id)
    //         ->where('programme_batch_id', $booking->programme_batch_id)
    //         ->update(['programme_batch_id' => null]);
    // }



}
