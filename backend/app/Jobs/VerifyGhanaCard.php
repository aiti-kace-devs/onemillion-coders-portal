<?php

namespace App\Jobs;

use App\Http\Controllers\NotificationController;
use App\Models\User;
use App\Services\GhanaCardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VerifyGhanaCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $tempImagePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GhanaCardService $service): void
    {
        if (!Storage::disk('private_cloud')->exists($this->tempImagePath)) {
            return;
        }

        $imageContents = Storage::disk('private_cloud')->get($this->tempImagePath);

        // Pass the raw contents to the service (Intervention Image can handle raw binary)
        $verification = $service->verify($this->user, $imageContents);

        if ($verification->code === '00' && $verification->verified) {
            NotificationController::notify(
                $this->user->id,
                'VERIFICATION',
                'Verification Successful',
                'Your identity has been verified successfully. Your Ghana Card details have been confirmed.'
            );
        } else {
            $userMessage = $service->buildUserSafeStatusMessage($verification->code, (string) $verification->status_message);
            NotificationController::notify(
                $this->user->id,
                'VERIFICATION',
                'Verification Unsuccessful',
                $userMessage
            );
        }

        // Cleanup
        Storage::disk('private_cloud')->delete($this->tempImagePath);
    }
}
