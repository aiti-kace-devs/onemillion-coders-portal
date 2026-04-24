<?php

namespace App\Jobs;

use App\Helpers\MailerHelper;
use App\Mail\RenderedTemplateEmail;
use App\Models\EmailTemplate;
use App\Models\ProtocolList;
use App\Services\ProtocolListService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SendProtocolInvitationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $protocolId,
        public string $inviteToken
    ) {
    }

    public function backoff(): array
    {
        return [600, 1200];
    }

    public function handle(ProtocolListService $protocolListService): void
    {
        $protocol = ProtocolList::query()->find($this->protocolId);
        if (! $protocol || ! $this->matchesCurrentInvite($protocol)) {
            return;
        }

        $this->markAttemptStarted($protocol);

        $viewName = null;

        try {
            $content = $this->renderTemplateContent($protocolListService, $protocol);
            $parsedContent = MailerHelper::parseMarkdown($content);
            $viewName = MailerHelper::createView($parsedContent);
            if (! $viewName) {
                throw new RuntimeException('Unable to create the protocol invitation mail view.');
            }

            Mail::to($protocol->email)->send(
                new RenderedTemplateEmail($this->subjectLine(), "mail.temp.$viewName")
            );

            MailerHelper::removeView($viewName);

            $protocol->forceFill([
                'invitation_email_status' => 'sent',
                'activation_email_sent_at' => now(),
                'invitation_email_last_attempt_at' => now(),
                'invitation_email_failed_at' => null,
                'invitation_email_failure_message' => null,
                'invitation_email_attempts' => max((int) $protocol->invitation_email_attempts, $this->attempts()),
            ])->save();
        } catch (Throwable $exception) {
            if ($viewName !== null) {
                MailerHelper::removeView($viewName);
            }

            $this->markAttemptFailed($protocol, $exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        $protocol = ProtocolList::query()->find($this->protocolId);
        if (! $protocol || ! $this->matchesCurrentInvite($protocol)) {
            return;
        }

        $protocol->forceFill([
            'invitation_email_status' => 'failed',
            'invitation_email_failed_at' => now(),
            'invitation_email_last_attempt_at' => now(),
            'invitation_email_attempts' => max((int) $protocol->invitation_email_attempts, $this->attempts()),
            'invitation_email_failure_message' => Str::limit($exception->getMessage(), 1000, ''),
        ])->save();
    }

    private function markAttemptStarted(ProtocolList $protocol): void
    {
        $protocol->forceFill([
            'invitation_email_status' => $this->attempts() > 1 ? 'retrying' : 'sending',
            'invitation_email_last_attempt_at' => now(),
            'invitation_email_attempts' => max((int) $protocol->invitation_email_attempts, $this->attempts()),
        ])->save();
    }

    private function markAttemptFailed(ProtocolList $protocol, Throwable $exception): void
    {
        if (! $this->matchesCurrentInvite($protocol)) {
            return;
        }

        $isFinalAttempt = $this->attempts() >= $this->tries;

        $protocol->forceFill([
            'invitation_email_status' => $isFinalAttempt ? 'failed' : 'retrying',
            'invitation_email_last_attempt_at' => now(),
            'invitation_email_failed_at' => $isFinalAttempt ? now() : null,
            'invitation_email_attempts' => max((int) $protocol->invitation_email_attempts, $this->attempts()),
            'invitation_email_failure_message' => Str::limit($exception->getMessage(), 1000, ''),
        ])->save();
    }

    private function renderTemplateContent(ProtocolListService $protocolListService, ProtocolList $protocol): string
    {
        $template = EmailTemplate::query()
            ->where('name', PROTOCOL_ACTIVATION_INVITATION_EMAIL)
            ->value('content');

        if (! is_string($template) || trim($template) === '') {
            throw new RuntimeException('Protocol activation invitation email template is missing.');
        }

        $displayName = trim((string) ($protocol->full_name ?: $protocol->first_name ?: 'Participant'));
        $replacements = [
            'displayName' => $displayName,
            'firstName' => (string) $protocol->first_name,
            'fullName' => $displayName,
            'email' => (string) $protocol->email,
            'ghcard' => (string) $protocol->ghcard,
            'activationUrl' => $protocolListService->activationUrlFor($this->inviteToken),
            'activationInviteCode' => strtoupper(substr((string) explode('.', $this->inviteToken)[0], 0, 8)),
            'appName' => (string) config('app.name', 'One Million Coders'),
        ];

        foreach ($replacements as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    private function matchesCurrentInvite(ProtocolList $protocol): bool
    {
        [$publicId] = array_pad(explode('.', trim($this->inviteToken), 2), 2, null);
        if (! is_string($publicId) || $publicId === '') {
            return false;
        }

        return hash_equals((string) $protocol->invite_token_hash, hash('sha256', $publicId));
    }

    private function subjectLine(): string
    {
        return 'Welcome to One Million Coders - Activate Your Account';
    }
}
