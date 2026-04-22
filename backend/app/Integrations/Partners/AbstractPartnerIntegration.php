<?php

namespace App\Integrations\Partners;

use App\Models\Partner;
use Illuminate\Support\Facades\Log;

abstract class AbstractPartnerIntegration implements PartnerIntegrationInterface
{
    protected Partner $partner;
    protected array $credentials;

    public function __construct(Partner $partner)
    {
        $this->partner = $partner;
        $this->credentials = $partner->getCredentialsArray();
    }

    protected function log(string $message, string $level = 'info', array $context = []): void
    {
        Log::$level("[Partner: {$this->partner->name}] $message", $context);
    }
}
