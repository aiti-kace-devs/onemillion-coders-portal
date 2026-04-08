<?php

namespace Tests\Unit;

use App\Models\StudentPartnerProgress;
use App\Models\User;
use App\Services\PartnerProgressStalenessService;
use Carbon\Carbon;
use Tests\TestCase;

class PartnerProgressStalenessServiceTest extends TestCase
{
    public function test_it_marks_progress_as_stale_when_past_stale_after_date(): void
    {
        $service = new PartnerProgressStalenessService();

        $progress = new StudentPartnerProgress([
            'stale_after_at' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($service->isStale($progress));
    }

    public function test_it_respects_reminder_cooldown(): void
    {
        config()->set('services.partner_progress.reminder_cooldown_hours', 24);

        $service = new PartnerProgressStalenessService();
        $user = new User(['email' => 'student@example.com']);

        $progress = new StudentPartnerProgress([
            'stale_after_at' => Carbon::now()->subDays(2),
            'last_reminder_sent_at' => Carbon::now()->subHours(2),
        ]);
        $progress->setRelation('user', $user);

        $this->assertFalse($service->shouldSendReminder($progress));

        $progress->last_reminder_sent_at = Carbon::now()->subHours(30);
        $this->assertTrue($service->shouldSendReminder($progress));
    }
}
