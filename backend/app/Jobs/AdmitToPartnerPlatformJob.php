<?php

namespace App\Jobs;

use App\Integrations\Partners\PartnerManager;
use App\Models\PartnerStudentAdmission;
use App\Models\Programme;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AdmitToPartnerPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected Programme $programme;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Programme $programme)
    {
        $this->user = $user;
        $this->programme = $programme;
    }

    /**
     * Execute the job.
     */
    public function handle(PartnerManager $partnerManager): void
    {
        try {
            if (!$this->programme->partner) {
                return;
            }

            // Create or update admission record
            $admission = PartnerStudentAdmission::updateOrCreate([
                'user_id' => $this->user->userId,
                'partner_id' => $this->programme->partner_id,
                'programme_id' => $this->programme->id,
            ]);

            if ($admission->enrollment_status === 'admitted') {
                return;
            }

            $integration = $partnerManager->resolve($this->programme);

            $data = $integration->admitStudent($this->user, $this->programme);

            if ($data['success']) {
                $admission->update([
                    'enrollment_status' => 'admitted',
                    'external_user_id' => $data['external_user_id'],
                ]);

                Log::info("Successfully admitted student {$this->user->userId} to {$this->programme->partner->name}");
            } else {
                $admission->update(['enrollment_status' => 'failed']);
                Log::error("Failed to admit student {$this->user->userId} to {$this->programme->partner->name}");
            }
        } catch (Exception $e) {
            Log::error("Error in AdmitToPartnerPlatformJob: " . $e->getMessage());
            throw $e;
        }
    }
}
