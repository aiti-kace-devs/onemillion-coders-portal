<?php

namespace App\Services\Partners;

/**
 * Validates normalized payloads against partner_integrations.validation_contract_json (OMCP-side rules).
 */
class PartnerProgressPayloadValidator
{
    /**
     * @param  array<string, mixed>|null  $contract
     */
    public function validateSingleNormalized(array $normalized, ?array $contract): ?string
    {
        if ($contract === null || $contract === []) {
            return null;
        }

        $rules = is_array($contract['single_normalized'] ?? null) ? $contract['single_normalized'] : $contract;

        return $this->applyRules($normalized, $rules);
    }

    /**
     * @param  array<string, mixed>|null  $contract
     */
    public function validateBulkNormalized(array $normalized, ?array $contract): ?string
    {
        if ($contract === null || $contract === []) {
            return null;
        }

        $rules = is_array($contract['bulk_normalized'] ?? null) ? $contract['bulk_normalized'] : [];

        return $rules === [] ? null : $this->applyRules($normalized, $rules);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function applyRules(array $normalized, array $rules): ?string
    {
        $requireKeys = $rules['require_keys'] ?? null;
        if (is_array($requireKeys)) {
            foreach ($requireKeys as $key) {
                if (! is_string($key) || $key === '') {
                    continue;
                }
                if (! array_key_exists($key, $normalized)) {
                    return "validation_contract: missing key `{$key}`";
                }
            }
        }

        if (! empty($rules['units_must_be_array']) && ! is_array($normalized['units'] ?? null)) {
            return 'validation_contract: `units` must be an array';
        }

        if (! empty($rules['summary_must_be_array']) && ! is_array($normalized['summary'] ?? null)) {
            return 'validation_contract: `summary` must be an array';
        }

        if (! empty($rules['raw_must_be_array']) && ! is_array($normalized['raw'] ?? null)) {
            return 'validation_contract: `raw` must be an array';
        }

        if (! empty($rules['require_non_empty_units']) && ($normalized['units'] ?? []) === []) {
            return 'validation_contract: `units` must not be empty';
        }

        return null;
    }
}
