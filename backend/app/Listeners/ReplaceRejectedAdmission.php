<?php

namespace App\Listeners;

use App\Events\AdmissionRejected;
use App\Services\AdmissionService;
use App\Services\AdmissionStatisticsService;
use App\Models\Admin;
use Illuminate\Support\Facades\Log;

class ReplaceRejectedAdmission
{
    /**
     * Handle the event.
     */
    public function handle(AdmissionRejected $event): void
    {
        if (!$event->shouldReplace) {
            return;
        }

        $admission = $event->admission;
        $course = $admission->course;
        $batch = $admission->batch;

        // Check if course has auto-replacement enabled
        if (!$course->auto_admit_enabled) {
            Log::info("Auto-replacement skipped: Not enabled for course", [
                'course_id' => $course->id
            ]);
            return;
        }

        try {
            $admissionService = app(AdmissionService::class);
            
            // Find next eligible student (limit = 1)
            $preview = $admissionService->previewAdmission($course, 1, $batch->id);
            
            if (empty($preview['students']) || $preview['students']->isEmpty()) {
                Log::warning("No replacement student found", [
                    'course_id' => $course->id,
                    'batch_id' => $batch->id,
                    'rejected_user' => $admission->user_id
                ]);
                return;
            }

            // Admit the next student
            $admin = Admin::first(); // System admin
            
            $admissionRun = $admissionService->executeAdmission(
                $course,
                1, // Just one student
                $batch->id,
                $admission->session, // Same session as rejected student
                $admin
            );

            Log::info("Student auto-replaced", [
                'rejected_user' => $admission->user_id,
                'replacement_user' => $preview['students']->first()->userId,
                'course_id' => $course->id,
                'batch_id' => $batch->id,
                'admission_run_id' => $admissionRun->id
            ]);

        } catch (\Exception $e) {
            Log::error("Auto-replacement failed", [
                'course_id' => $course->id,
                'rejected_user' => $admission->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
