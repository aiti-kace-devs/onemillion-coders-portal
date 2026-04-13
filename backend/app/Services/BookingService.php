<?php

namespace App\Services;

use App\Models\UserAdmission;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\Course;
use App\Events\AdmissionSlotFreed;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingService
{
    /**
     * Book a user into a programme batch.
     *
     * @param User $user
     * @param Course $course
     * @param ProgrammeBatch $batch
     * @return UserAdmission
     * @throws Exception
     */
    public function book(User $user, Course $course, ProgrammeBatch $batch): UserAdmission
    {
        return DB::transaction(function () use ($user, $course, $batch) {
            // Lock the batch row for update
            $lockedBatch = ProgrammeBatch::where('id', $batch->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedBatch || $lockedBatch->available_slots <= 0) {
                throw new Exception('No available slots for this programme batch.');
            }

            // Check if user is already booked into this specific batch
            $existingAdmission = UserAdmission::where('user_id', $user->userId)
                ->where('programme_batch_id', $batch->id)
                ->first();

            if ($existingAdmission) {
                // User already booked into this batch — just return the record
                return $existingAdmission;
            }

            // If user has an existing admission to a DIFFERENT batch, cancel it first (restore slot)
            $previousAdmission = UserAdmission::where('user_id', $user->userId)
                ->whereNotNull('programme_batch_id')
                ->where('programme_batch_id', '!=', $batch->id)
                ->first();

            if ($previousAdmission) {
                $this->cancelWithoutDelete($previousAdmission);
            }

            // Decrement available_slots
            $lockedBatch->decrement('available_slots');

            // Invalidate availability cache for this centre/course/batch window
            if ($lockedBatch->programme && $lockedBatch->centre) {
                $course = Course::where('programme_id', $lockedBatch->programme_id)
                    ->where('centre_id', $lockedBatch->centre_id)
                    ->first();
                if ($course) {
                    AvailabilityService::clearCache(
                        $lockedBatch->centre_id,
                        $course->id,
                        $lockedBatch->start_date,
                        $lockedBatch->end_date
                    );
                }
            }

            // Create or update the admission record
            $admission = UserAdmission::updateOrCreate(
                ['user_id' => $user->userId],
                [
                    'course_id' => $course->id,
                    'batch_id' => $batch->admission_batch_id,
                    'programme_batch_id' => $batch->id,
                    'email_sent' => now(),
                ]
            );

            return $admission;
        });
    }

    /**
     * Cancel an admission without deleting the record (used internally when switching batches).
     * Restores the slot if eligible. Does NOT delete the admission record.
     */
    private function cancelWithoutDelete(UserAdmission $admission): void
    {
        if (!$admission->programme_batch_id) {
            return;
        }

        $batch = $admission->programmeBatch;
        if (!$batch) {
            return;
        }

        $batchEndDate = Carbon::parse($batch->end_date);
        $daysUntilEnd = now()->diffInDays($batchEndDate, false);

        // Only restore if > 7 days before batch end
        if ($daysUntilEnd > 7) {
            DB::transaction(function () use ($batch) {
                $lockedBatch = ProgrammeBatch::where('id', $batch->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedBatch) {
                    $lockedBatch->increment('available_slots');
                }
            });

            event(new AdmissionSlotFreed($batch, $admission));
        }

        // Clear the programme_batch_id to release the slot reference
        $admission->update(['programme_batch_id' => null]);
    }

    /**
     * Cancel an admission and restore the slot if eligible.
     *
     * @param UserAdmission $admission
     * @return bool — true if slot was restored, false if not eligible
     */
    public function cancel(UserAdmission $admission): bool
    {
        if (!$admission->programme_batch_id) {
            // Legacy admission — no slot to restore
            $admission->delete();
            return false;
        }

        $batch = $admission->programmeBatch;
        if (!$batch) {
            $admission->delete();
            return false;
        }

        $batchEndDate = Carbon::parse($batch->end_date);
        $daysUntilEnd = now()->diffInDays($batchEndDate, false);

        $slotRestored = false;

        // Only restore if > 7 days before batch end
        if ($daysUntilEnd > 7) {
            DB::transaction(function () use ($batch) {
                $lockedBatch = ProgrammeBatch::where('id', $batch->id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedBatch) {
                    $lockedBatch->increment('available_slots');
                }
            });

            // Invalidate availability cache
            if ($batch->programme && $batch->centre) {
                $course = Course::where('programme_id', $batch->programme_id)
                    ->where('centre_id', $batch->centre_id)
                    ->first();
                if ($course) {
                    AvailabilityService::clearCache(
                        $batch->centre_id,
                        $course->id,
                        $batch->start_date,
                        $batch->end_date
                    );
                }
            }

            $slotRestored = true;
            event(new AdmissionSlotFreed($batch, $admission));
        }

        $admission->delete();

        return $slotRestored;
    }
}
