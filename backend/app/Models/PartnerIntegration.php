<?php

namespace App\Models;

use App\Support\PartnerCodeNormalizer;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PartnerIntegration extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'display_name',
        'is_enabled',
        'refresh_timestamp_header_without_signing',
        'base_url',
        'auth_type',
        'auth_config_json',
        'headers_json',
        'signature_config_json',
        'endpoints_json',
        'path_param_bindings_json',
        'response_mapping_json',
        'pagination_mapping_json',
        'metrics_mapping_json',
        'validation_contract_json',
        'rate_limit_per_minute',
        'timeout_seconds',
        'retry_attempts',
        'retry_backoff_ms_json',
        'notes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'refresh_timestamp_header_without_signing' => 'boolean',
        'auth_config_json' => 'array',
        'headers_json' => 'array',
        'signature_config_json' => 'array',
        'endpoints_json' => 'array',
        'path_param_bindings_json' => 'array',
        'response_mapping_json' => 'array',
        'pagination_mapping_json' => 'array',
        'metrics_mapping_json' => 'array',
        'validation_contract_json' => 'array',
        'retry_backoff_ms_json' => 'array',
    ];

    public function setPartnerCodeAttribute(mixed $value): void
    {
        $this->attributes['partner_code'] = is_string($value)
            ? PartnerCodeNormalizer::normalize($value)
            : $value;
    }

    /**
     * Backpack sends JSON columns as strings from textareas. The model's array/json cast
     * would otherwise json_encode() those strings again, storing invalid double-encoded JSON.
     */
    public function setAuthConfigJsonAttribute(mixed $value): void
    {
        $this->attributes['auth_config_json'] = $this->normalizeJsonColumnForStorage($value, 'auth_config_json');
    }

    public function setHeadersJsonAttribute(mixed $value): void
    {
        $this->attributes['headers_json'] = $this->normalizeJsonColumnForStorage($value, 'headers_json');
    }

    public function setSignatureConfigJsonAttribute(mixed $value): void
    {
        $this->attributes['signature_config_json'] = $this->normalizeJsonColumnForStorage($value, 'signature_config_json');
    }

    public function setEndpointsJsonAttribute(mixed $value): void
    {
        $this->attributes['endpoints_json'] = $this->normalizeJsonColumnForStorage($value, 'endpoints_json');
    }

    public function setPathParamBindingsJsonAttribute(mixed $value): void
    {
        $this->attributes['path_param_bindings_json'] = $this->normalizeJsonColumnForStorage($value, 'path_param_bindings_json');
    }

    public function setResponseMappingJsonAttribute(mixed $value): void
    {
        $this->attributes['response_mapping_json'] = $this->normalizeJsonColumnForStorage($value, 'response_mapping_json');
    }

    public function setPaginationMappingJsonAttribute(mixed $value): void
    {
        $this->attributes['pagination_mapping_json'] = $this->normalizeJsonColumnForStorage($value, 'pagination_mapping_json');
    }

    public function setMetricsMappingJsonAttribute(mixed $value): void
    {
        $this->attributes['metrics_mapping_json'] = $this->normalizeJsonColumnForStorage($value, 'metrics_mapping_json');
    }

    public function setValidationContractJsonAttribute(mixed $value): void
    {
        $this->attributes['validation_contract_json'] = $this->normalizeJsonColumnForStorage($value, 'validation_contract_json');
    }

    public function setRetryBackoffMsJsonAttribute(mixed $value): void
    {
        $this->attributes['retry_backoff_ms_json'] = $this->normalizeJsonColumnForStorage($value, 'retry_backoff_ms_json');
    }

    private function normalizeJsonColumnForStorage(mixed $value, string $column): ?string
    {
        if ($value === null) {
            return null;
        }
        // Handle textarea payloads from Backpack.
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException("Invalid {$column}: must be valid JSON.");
            }

            return json_encode($decoded, JSON_UNESCAPED_SLASHES);
        }

        // Accept already-decoded JSON values (array/object/bool/number) and canonicalize.
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new InvalidArgumentException("Invalid {$column}: unable to encode JSON value.");
        }
        json_decode($encoded, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid {$column}: value cannot be normalized to JSON.");
        }

        return $encoded;
    }
}

