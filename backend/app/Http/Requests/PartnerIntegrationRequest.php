<?php

namespace App\Http\Requests;

use App\Models\Programme;
use App\Support\PartnerCodeNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PartnerIntegrationRequest extends FormRequest
{
    /** Select value for “Other (manual partner code)” in Partner Integrations admin. */
    public const PARTNER_CODE_OTHER = '__other__';
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'partner_code_preset' => ['required', 'string', Rule::in($this->allowedPartnerCodePresetValues())],
            'partner_code_manual' => [
                'nullable',
                'string',
                'min:2',
                'max:64',
                'regex:/^[a-z0-9_-]+$/',
                Rule::requiredIf(fn () => (string) $this->input('partner_code_preset') === self::PARTNER_CODE_OTHER),
            ],
            'partner_code' => [
                'required',
                'string',
                'min:2',
                'max:64',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('partner_integrations', 'partner_code')->ignore($id),
            ],
            'display_name' => 'required|string|min:2|max:120',
            'is_enabled' => 'nullable|boolean',
            'refresh_timestamp_header_without_signing' => 'nullable|boolean',
            'base_url' => ['nullable', 'string', 'max:255', $this->optionalAbsoluteUrlRule()],
            'auth_type' => 'required|string|in:none,bearer_token,api_key_header,basic,custom',
            'auth_config_json' => 'nullable|string',
            'headers_json' => 'nullable|string',
            'signature_config_json' => 'nullable|string',
            'endpoints_json' => 'nullable|string',
            'path_param_bindings_json' => 'nullable|string',
            'response_mapping_json' => 'nullable|string',
            'pagination_mapping_json' => 'nullable|string',
            'metrics_mapping_json' => 'nullable|string',
            'validation_contract_json' => 'nullable|string',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:100000',
            'timeout_seconds' => 'nullable|integer|min:1|max:120',
            'retry_attempts' => 'nullable|integer|min:0|max:10',
            'retry_backoff_ms_json' => 'nullable|string',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_enabled' => $this->boolean('is_enabled'),
            'refresh_timestamp_header_without_signing' => $this->boolean('refresh_timestamp_header_without_signing'),
        ]);

        if ($this->has('partner_code_preset')) {
            $preset = (string) $this->input('partner_code_preset');
            if ($preset === self::PARTNER_CODE_OTHER) {
                $manual = trim((string) $this->input('partner_code_manual', ''));
                $this->merge(['partner_code' => PartnerCodeNormalizer::normalize($manual)]);
            } elseif ($preset !== '') {
                $this->merge(['partner_code' => PartnerCodeNormalizer::normalize($preset)]);
            }
        } else {
            $pc = $this->input('partner_code');
            if (is_string($pc) && trim($pc) !== '') {
                $this->merge(['partner_code' => PartnerCodeNormalizer::normalize($pc)]);
            }
        }

        $baseUrl = $this->input('base_url');
        if (is_string($baseUrl)) {
            $trimmed = trim($baseUrl);
            if ($trimmed !== '' && ! preg_match('#^https?://#i', $trimmed)) {
                $this->merge(['base_url' => 'https://' . $trimmed]);
            }
        }

        foreach (['auth_config_json', 'headers_json', 'signature_config_json', 'endpoints_json', 'path_param_bindings_json', 'response_mapping_json', 'pagination_mapping_json', 'metrics_mapping_json', 'validation_contract_json', 'retry_backoff_ms_json'] as $jsonField) {
            $value = $this->input($jsonField);
            if (is_string($value) && trim($value) !== '') {
                json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$jsonField => json_encode(json_decode($value, true), JSON_UNESCAPED_SLASHES)]);
                }
            }
        }

        $this->normalizePathParamBindingsScalars();
    }

    /**
     * Shorthand: {"program_slug":"gh-program"} → {"program_slug":{"source":"literal","value":"gh-program"}}.
     */
    private function normalizePathParamBindingsScalars(): void
    {
        $value = $this->input('path_param_bindings_json');
        if (! is_string($value) || trim($value) === '') {
            return;
        }
        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return;
        }
        $changed = false;
        foreach ($decoded as $placeholder => $rule) {
            if (! is_string($placeholder) || trim($placeholder) === '') {
                continue;
            }
            if (is_array($rule)) {
                continue;
            }
            if (! is_scalar($rule)) {
                continue;
            }
            if (is_bool($rule)) {
                $decoded[$placeholder] = [
                    'source' => 'literal',
                    'value' => $rule ? 'true' : 'false',
                ];
            } else {
                $decoded[$placeholder] = [
                    'source' => 'literal',
                    'value' => (string) $rule,
                ];
            }
            $changed = true;
        }
        if ($changed) {
            $this->merge([
                'path_param_bindings_json' => json_encode($decoded, JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function allowedPartnerCodePresetValues(): array
    {
        $keys = [];
        if (Schema::hasTable('programmes')) {
            $rows = Programme::query()
                ->whereNotNull('provider')
                ->where('provider', '!=', '')
                ->distinct()
                ->orderBy('provider')
                ->pluck('provider');
            foreach ($rows as $p) {
                $n = PartnerCodeNormalizer::normalize((string) $p);
                if ($n !== '') {
                    $keys[] = $n;
                }
            }
        }
        $keys[] = self::PARTNER_CODE_OTHER;

        return array_values(array_unique($keys));
    }

    private function optionalAbsoluteUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }
            $candidate = trim($value);
            if (filter_var($candidate, FILTER_VALIDATE_URL) === false) {
                $fail('The base URL must be a valid URL.');
            }
        };
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach (['auth_config_json', 'headers_json', 'signature_config_json', 'endpoints_json', 'path_param_bindings_json', 'response_mapping_json', 'pagination_mapping_json', 'metrics_mapping_json', 'validation_contract_json', 'retry_backoff_ms_json'] as $jsonField) {
                $value = $this->input($jsonField);
                if (is_string($value) && trim($value) !== '') {
                    json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $validator->errors()->add(
                            $jsonField,
                            'Must be valid JSON: '.json_last_error_msg().'.'
                        );
                    }
                }
            }

            $endpoints = $this->decodeJsonField('endpoints_json');
            $bindings = $this->decodeJsonField('path_param_bindings_json');

            $this->validateEndpointsShape($validator, $endpoints);
            $this->validateBindingsShape($validator, $bindings);
            $this->validateEndpointPlaceholders($validator, $endpoints, $bindings);
            $this->validateAuthConfigForAuthType($validator);
        });
    }

    private function validateAuthConfigForAuthType($validator): void
    {
        $authType = (string) $this->input('auth_type', 'none');
        $authConfig = $this->decodeJsonField('auth_config_json');

        if ($authType === 'none') {
            return;
        }

        if (!is_array($authConfig)) {
            $validator->errors()->add('auth_config_json', 'Auth config JSON is required for the selected auth type.');
            return;
        }

        if ($authType === 'bearer_token') {
            $token = trim((string) ($authConfig['token'] ?? ''));
            if ($token === '') {
                $validator->errors()->add('auth_config_json', 'Bearer auth requires non-empty `token` in auth_config_json.');
            }
            return;
        }

        if ($authType === 'api_key_header') {
            $headerName = trim((string) ($authConfig['header_name'] ?? ''));
            $headerValue = trim((string) ($authConfig['value'] ?? ''));
            if ($headerName === '' || $headerValue === '') {
                $validator->errors()->add(
                    'auth_config_json',
                    'API key header auth requires non-empty `header_name` and `value` in auth_config_json.'
                );
            }
            return;
        }

        if ($authType === 'basic') {
            $username = trim((string) ($authConfig['username'] ?? ''));
            $password = trim((string) ($authConfig['password'] ?? ''));
            if ($username === '' && $password === '') {
                $validator->errors()->add(
                    'auth_config_json',
                    'Basic auth requires at least one of `username` or `password` in auth_config_json.'
                );
            }
        }
    }

    private function decodeJsonField(string $field): mixed
    {
        $value = $this->input($field);
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function validateEndpointsShape($validator, mixed $endpoints): void
    {
        if ($endpoints === null) {
            return;
        }
        if (!is_array($endpoints)) {
            $validator->errors()->add('endpoints_json', 'Endpoints JSON must be an object.');
            return;
        }

        foreach (['single_progress', 'bulk_progress'] as $key) {
            $entry = $endpoints[$key] ?? null;
            if ($entry === null) {
                continue;
            }
            if (is_string($entry)) {
                if (trim($entry) === '') {
                    $validator->errors()->add('endpoints_json', "{$key} path must not be empty.");
                }
                continue;
            }
            if (!is_array($entry)) {
                $validator->errors()->add('endpoints_json', "{$key} must be either a string path or object.");
                continue;
            }
            $path = trim((string) ($entry['path'] ?? ''));
            if ($path === '') {
                $validator->errors()->add('endpoints_json', "{$key}.path is required when {$key} is an object.");
            }
            $queryMap = $entry['query_map'] ?? null;
            if ($queryMap !== null && !is_array($queryMap)) {
                $validator->errors()->add('endpoints_json', "{$key}.query_map must be an object.");
            }
        }
    }

    private function validateBindingsShape($validator, mixed $bindings): void
    {
        if ($bindings === null) {
            return;
        }
        if (!is_array($bindings)) {
            $validator->errors()->add('path_param_bindings_json', 'Path param bindings must be a JSON object keyed by placeholder.');
            return;
        }

        $allowedTables = config('services.partner_binding_allowed_tables', ['users']);
        if (!is_array($allowedTables) || $allowedTables === []) {
            $allowedTables = ['users'];
        }
        $allowedColumnsMap = config('services.partner_binding_allowed_columns', []);
        if (!is_array($allowedColumnsMap)) {
            $allowedColumnsMap = [];
        }

        foreach ($bindings as $placeholder => $rule) {
            if (!is_string($placeholder) || trim($placeholder) === '') {
                $validator->errors()->add('path_param_bindings_json', 'Each binding must have a non-empty placeholder key.');
                continue;
            }
            if (!is_array($rule)) {
                $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' must be an object.");
                continue;
            }

            $source = (string) ($rule['source'] ?? 'context');
            if (! in_array($source, ['context', 'db_lookup', 'literal'], true)) {
                $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' has invalid source.");
                continue;
            }

            if ($source === 'context') {
                $key = trim((string) ($rule['key'] ?? ''));
                if ($key === '') {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' requires key for context source.");
                }
                continue;
            }

            if ($source === 'literal') {
                if (! array_key_exists('value', $rule)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' literal source requires value.");
                    continue;
                }
                $lit = $rule['value'];
                if (is_array($lit) || is_object($lit)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' literal value must be scalar.");
                    continue;
                }
                if (! is_scalar($lit) || is_bool($lit)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' literal value must be a string or number.");
                    continue;
                }
                $str = is_string($lit) ? trim($lit) : (string) $lit;
                if ($str === '') {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' literal value must not be empty.");
                }
                continue;
            }

            $table = trim((string) ($rule['table'] ?? ''));
            $column = trim((string) ($rule['column'] ?? ''));
            $whereColumn = trim((string) ($rule['where_column'] ?? ''));
            $whereContextKey = trim((string) ($rule['where_context_key'] ?? ''));
            if ($table === '' || $column === '' || $whereColumn === '' || $whereContextKey === '') {
                $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' requires table, column, where_column, where_context_key.");
                continue;
            }
            if (!in_array($table, $allowedTables, true)) {
                $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' table '{$table}' is not allow-listed.");
                continue;
            }

            $tableAllowedColumns = $allowedColumnsMap[$table] ?? null;
            if (is_array($tableAllowedColumns) && $tableAllowedColumns !== []) {
                if (!in_array($column, $tableAllowedColumns, true)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' column '{$column}' is not allow-listed for table '{$table}'.");
                }
                if (!in_array($whereColumn, $tableAllowedColumns, true)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' where_column '{$whereColumn}' is not allow-listed for table '{$table}'.");
                }
            }

            if (Schema::hasTable($table)) {
                if (!Schema::hasColumn($table, $column)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' column '{$column}' not found in table '{$table}'.");
                }
                if (!Schema::hasColumn($table, $whereColumn)) {
                    $validator->errors()->add('path_param_bindings_json', "Binding '{$placeholder}' where_column '{$whereColumn}' not found in table '{$table}'.");
                }
            }
        }
    }

    private function validateEndpointPlaceholders($validator, mixed $endpoints, mixed $bindings): void
    {
        if (!is_array($endpoints)) {
            return;
        }
        $bindings = is_array($bindings) ? $bindings : [];
        $contextKeys = ['omcp_id', 'program_slug', 'partner_code'];
        $paths = [];

        foreach (['single_progress', 'bulk_progress'] as $key) {
            $entry = $endpoints[$key] ?? null;
            if (is_string($entry)) {
                $paths[] = $entry;
            } elseif (is_array($entry) && isset($entry['path'])) {
                $paths[] = (string) $entry['path'];
            }
        }

        foreach ($paths as $path) {
            if (!preg_match_all('/\{([a-zA-Z0-9_]+)\}/', (string) $path, $matches)) {
                continue;
            }
            foreach (($matches[1] ?? []) as $placeholder) {
                if (in_array($placeholder, $contextKeys, true)) {
                    continue;
                }
                if (!array_key_exists($placeholder, $bindings)) {
                    $validator->errors()->add(
                        'path_param_bindings_json',
                        "Missing binding for placeholder '{$placeholder}' referenced in endpoints_json."
                    );
                }
            }
        }
    }
}

