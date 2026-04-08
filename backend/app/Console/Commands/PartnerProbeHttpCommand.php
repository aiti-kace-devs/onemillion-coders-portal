<?php

namespace App\Console\Commands;

use App\Models\PartnerIntegration;
use App\Services\Partners\Startocode\PartnerProgressClient;
use App\Support\PartnerCodeNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Live HTTP probe against a partner (does not write to partner_integrations).
 * Use to verify HMAC + paths with optional ps_ secret without pasting secrets into the DB.
 */
class PartnerProbeHttpCommand extends Command
{
    protected $signature = 'partner:probe-http
        {partner_code : partner_code from partner_integrations}
        {--omcp= : OMCP user id (users.userId) for single-progress GET}
        {--ps-secret= : HMAC secret (ps_...); also read from PARTNER_PROBE_PS_SECRET if set}
        {--try-message-formats : On failure, try message_format + strip_ps_prefix variants (uses --ps-secret / env / DB hmac_secret or ps_ in headers)}';

    protected $description = 'Run one real single-progress GET using saved integration (optional in-memory ps_ override; not saved)';

    public function handle(PartnerProgressClient $client): int
    {
        if (! $this->probeAllowed()) {
            $this->error('Probe override is disabled. Use APP_ENV=local, or set PARTNER_PROGRESS_ALLOW_PROBE_OVERRIDE=true in .env');

            return self::FAILURE;
        }

        if (! Schema::hasTable('partner_integrations')) {
            $this->error('partner_integrations table missing.');

            return self::FAILURE;
        }

        $code = PartnerCodeNormalizer::normalize((string) $this->argument('partner_code'));
        $base = PartnerIntegration::query()->where('partner_code', $code)->where('is_enabled', true)->first();
        if (! $base) {
            $this->error("No enabled partner_integrations row for partner_code `{$code}`.");

            return self::FAILURE;
        }

        $omcp = trim((string) ($this->option('omcp') ?: ''));
        if ($omcp === '') {
            $this->error('Pass --omcp=<users.userId> (e.g. 001-26-00015).');

            return self::FAILURE;
        }

        $psSecret = trim((string) ($this->option('ps-secret') ?: ''));
        if ($psSecret === '') {
            $psSecret = trim((string) env('PARTNER_PROBE_PS_SECRET', ''));
        }

        $this->line('Using partner integration from DB (id='.$base->id.', base_url='.$base->base_url.')');
        $this->warn('No database rows are modified by this command.');

        $headersPreview = is_array($base->headers_json) ? $base->headers_json : [];
        foreach ($headersPreview as $hn => $hv) {
            if (is_string($hn) && preg_match('/signature/i', (string) $hn) && is_string($hv) && preg_match('/^[a-f0-9]{64}$/i', $hv)) {
                $this->warn('Headers JSON currently has a 64-char hex in `'.$hn.'` (static curl digest). Derivation/HMAC needs the ps_ secret. Use --ps-secret or PARTNER_PROBE_PS_SECRET, or fix the row in Admin.');

                break;
            }
        }

        $probe = $this->buildProbeModel($base, $psSecret);

        PartnerProgressClient::setProbeIntegrationOverride($probe);
        try {
            $result = $client->fetchStudentProgress($code, $omcp, null);
        } finally {
            PartnerProgressClient::setProbeIntegrationOverride(null);
        }

        $this->line('HTTP status: '.($result['status'] ?? 0));
        $this->line('OK: '.($result['ok'] ? 'yes' : 'no'));
        $this->line('Message: '.($result['message'] ?? ''));

        $psForVariants = $psSecret !== '' ? $psSecret : $this->extractHmacSecretFromIntegration($base);
        if (! ($result['ok'] ?? false) && $this->option('try-message-formats')) {
            if ($psForVariants === '') {
                $this->warn('Cannot try message formats: pass --ps-secret, set PARTNER_PROBE_PS_SECRET, or save hmac_secret / ps_ in Signature or Headers JSON.');
            } else {
                $this->info('Retrying with explicit signature_config_json (message_format × strip_ps_prefix variants; secret from DB or CLI, not printed)...');
                foreach (['timestamp', 'get_path_timestamp', 'get_path_query_timestamp'] as $fmt) {
                    foreach ([false, true] as $stripPs) {
                        if ($stripPs && ! str_starts_with($psForVariants, 'ps_')) {
                            continue;
                        }
                        $probe2 = $this->buildProbeModel($base, $psForVariants);
                        $probe2->signature_config_json = $this->explicitSigningConfig($probe2, $psForVariants, $fmt, $stripPs);
                        $hj = $probe2->headers_json;
                        $probe2->headers_json = $this->stripSigningHeaders(is_array($hj) ? $hj : null);

                        PartnerProgressClient::setProbeIntegrationOverride($probe2);
                        try {
                            $r2 = $client->fetchStudentProgress($code, $omcp, null);
                        } finally {
                            PartnerProgressClient::setProbeIntegrationOverride(null);
                        }
                        $stripLabel = $stripPs ? 'true' : 'false';
                        $this->line('--- message_format='.$fmt.' strip_ps_prefix='.$stripLabel.' status='.($r2['status'] ?? 0).' ok='.($r2['ok'] ? 'yes' : 'no').' msg='.($r2['message'] ?? ''));
                        if ($r2['ok'] ?? false) {
                            $this->info('Success with message_format='.$fmt.', strip_ps_prefix='.$stripLabel.'.');

                            return self::SUCCESS;
                        }
                    }
                }
            }
        }

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    private function probeAllowed(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return (bool) config('services.partner_progress.allow_probe_integration_override', false);
    }

    private function buildProbeModel(PartnerIntegration $base, string $psSecret): PartnerIntegration
    {
        $probe = $base->replicate();
        if ($psSecret !== '') {
            $probe->headers_json = [
                'X-Partner-Signature' => $psSecret,
            ];
            $probe->signature_config_json = null;
        }

        return $probe;
    }

    /**
     * @param  array<string, mixed>|null  $headersJson
     * @return array<string, mixed>|null
     */
    private function stripSigningHeaders(?array $headersJson): ?array
    {
        if (! is_array($headersJson)) {
            return null;
        }
        $out = [];
        foreach ($headersJson as $k => $v) {
            if (is_string($k) && (preg_match('/signature/i', $k) || preg_match('/timestamp/i', $k))) {
                continue;
            }
            $out[$k] = $v;
        }

        return $out === [] ? null : $out;
    }

    /**
     * Prefer CLI/env secret; else {@see PartnerIntegration::$signature_config_json} {@code hmac_secret};
     * else a non-hex {@code *Signature*} header value (e.g. {@code ps_…}).
     */
    private function extractHmacSecretFromIntegration(PartnerIntegration $integration): string
    {
        $cfg = is_array($integration->signature_config_json) ? $integration->signature_config_json : [];
        $fromCfg = trim((string) ($cfg['hmac_secret'] ?? ''));
        if ($fromCfg !== '') {
            return $fromCfg;
        }

        $headers = is_array($integration->headers_json) ? $integration->headers_json : [];
        foreach ($headers as $name => $value) {
            if (! is_string($name) || ! preg_match('/signature/i', $name)) {
                continue;
            }
            $sv = trim((string) $value);
            if ($sv !== '' && ! preg_match('/^[a-f0-9]{64}$/i', $sv)) {
                return $sv;
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function explicitSigningConfig(PartnerIntegration $probe, string $psSecret, string $messageFormat, bool $stripPsPrefix = false): array
    {
        $auth = is_array($probe->auth_config_json) ? $probe->auth_config_json : [];
        $pk = '';

        if (($probe->auth_type ?? '') === 'api_key_header') {
            $pk = trim((string) ($auth['value'] ?? ''));
        }

        $out = [
            'scheme' => 'hmac_timestamp_v1',
            'api_key' => $pk,
            'hmac_secret' => $psSecret,
            'message_format' => $messageFormat,
            'signature_encoding' => 'hex',
        ];
        if ($stripPsPrefix) {
            $out['strip_ps_prefix'] = true;
        }

        return $out;
    }
}
