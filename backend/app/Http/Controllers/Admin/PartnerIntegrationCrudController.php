<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PartnerIntegrationRequest;
use App\Models\PartnerIntegration;
use App\Models\Programme;
use App\Support\PartnerCodeNormalizer;
use App\Services\Partners\PartnerIntegrityService;
use App\Services\Partners\PartnerRegistry;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class PartnerIntegrationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(PartnerIntegration::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/partner-integration');
        CRUD::setEntityNameStrings('partner integration', 'partner integrations');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('partner_code')->label('Partner Code');
        CRUD::addColumn([
            'name' => 'integrity',
            'label' => 'Checks',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $issues = app(PartnerIntegrityService::class)->issuesForIntegration($entry);
                if ($issues === []) {
                    return '<span class="badge bg-success">OK</span>';
                }

                return '<span class="badge bg-warning text-dark" title="'.e(implode('; ', $issues)).'">'
                    .count($issues).' issue(s)</span>';
            },
            'escaped' => false,
        ]);
        CRUD::column('display_name')->label('Display Name');
        CRUD::column('is_enabled')->type('boolean')->label('Enabled');
        CRUD::column('base_url')->label('Base URL');
        CRUD::column('auth_type')->label('Auth Type');
        CRUD::column('rate_limit_per_minute')->label('Rate Limit / min');
        CRUD::column('timeout_seconds')->label('Timeout (s)');
        CRUD::column('updated_at')->label('Updated');
    }

    protected function setupShowOperation(): void
    {
        CRUD::addColumn([
            'name' => 'integrity_banner',
            'label' => '',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $issues = app(PartnerIntegrityService::class)->issuesForIntegration($entry);
                if ($issues === []) {
                    return '<div class="alert alert-success border-0 shadow-sm mb-3">'
                        .'<strong>Integration checks</strong> passed for this partner code.</div>';
                }

                return '<div class="alert alert-warning border-0 shadow-sm mb-3">'
                    .'<strong>Integration checks</strong><ul class="mb-0 mt-2">'
                    .collect($issues)->map(fn (string $i) => '<li>'.e($i).'</li>')->implode('')
                    .'</ul></div>';
            },
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addColumn([
            'name' => 'diagnostics',
            'label' => 'Diagnostics',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $id = (int) $entry->id;
                $csrf = csrf_token();
                $prefix = trim((string) config('backpack.base.route_prefix', 'admin'), '/');

                return '
                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="small text-muted mb-2">Run against this saved integration (uses live HTTP where noted).</div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-partner-test="connection" data-id="'.$id.'">Test connection</button>
                    </div>
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">OMCP user id (single-student fetch + normalize)</label>
                            <input type="text" class="form-control form-control-sm" id="partner-test-omcp-'.$id.'" placeholder="userId" />
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-partner-test="parse-single" data-id="'.$id.'">Test single payload parse</button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-0">Bulk item JSON (bulk normalize)</label>
                        <textarea class="form-control form-control-sm font-monospace" rows="4" id="partner-test-bulk-'.$id.'" placeholder="{&quot;omcp_id&quot;:&quot;...&quot;}"></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-partner-test="parse-bulk" data-id="'.$id.'">Test bulk item parse</button>
                    <pre class="mt-3 small mb-0 p-2 border rounded bg-white" id="partner-test-out-'.$id.'" style="white-space:pre-wrap;word-break:break-word;"></pre>
                </div>
                <script>
                (function(){
                    document.addEventListener("click", function (ev) {
                        var btn = ev.target.closest("[data-partner-test]");
                        if (!btn) return;
                        var kind = btn.getAttribute("data-partner-test");
                        var id = btn.getAttribute("data-id");
                        var out = document.getElementById("partner-test-out-" + id);
                        var omcp = document.getElementById("partner-test-omcp-" + id);
                        var bulk = document.getElementById("partner-test-bulk-" + id);
                        var url = "/' . $prefix . '/partner-integration/" + id + "/";
                        var body = "{}";
                        var method = "POST";
                        var headers = { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": "'.$csrf.'" };
                        if (kind === "connection") {
                            url += "test-connection";
                        } else if (kind === "parse-single") {
                            url += "test-parse-single";
                            body = JSON.stringify({ omcp_id: (omcp && omcp.value) ? omcp.value : "" });
                        } else if (kind === "parse-bulk") {
                            url += "test-parse-bulk";
                            var raw = (bulk && bulk.value) ? bulk.value : "{}";
                            try {
                                var parsed = JSON.parse(raw);
                                body = JSON.stringify({ item: parsed, program_slug: "test-program" });
                            } catch (e) {
                                if (out) out.textContent = "Invalid JSON in bulk item field.";
                                return;
                            }
                        }
                        if (out) out.textContent = "Loading...";
                        fetch(url, { method: method, headers: headers, body: body, credentials: "same-origin" })
                            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j }; }); })
                            .then(function (res) {
                                if (out) out.textContent = JSON.stringify(res.json, null, 2);
                            })
                            .catch(function (e) {
                                if (out) out.textContent = String(e);
                            });
                    });
                })();
                </script>';
            },
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::column('id')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::column('partner_code')->label('Partner Code')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::column('display_name')->label('Display Name')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::column('is_enabled')->type('boolean')->label('Enabled')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::column('base_url')->label('Base URL')->wrapper(['class' => 'form-group col-md-8']);
        CRUD::column('auth_type')->label('Auth Type')->wrapper(['class' => 'form-group col-md-4']);

        CRUD::addColumn([
            'name' => 'auth_config_json',
            'label' => 'Auth Config (JSON)',
            'type' => 'text',
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'value' => function ($entry) {
                $value = $entry->auth_config_json;
                if (empty($value)) {
                    return null;
                }

                $json = is_array($value)
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : (string) $value;
                $modalId = 'partner-json-auth-' . $entry->id;

                return '
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">JSON</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#' . e($modalId) . '">
                            View JSON
                        </button>
                    </div>
                    <div class="modal fade" id="' . e($modalId) . '" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Auth Config (JSON)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <pre class="mb-0 p-3 border rounded small" style="background:#f3f4f6;color:#111827;white-space:pre-wrap;word-break:break-word;line-height:1.45;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">' . e($json) . '</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            },
        ]);

        CRUD::addColumn([
            'name' => 'headers_json',
            'label' => 'Headers Config (JSON)',
            'type' => 'text',
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'value' => function ($entry) {
                $value = $entry->headers_json;
                if (empty($value)) {
                    return null;
                }

                $json = is_array($value)
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : (string) $value;
                $modalId = 'partner-json-headers-' . $entry->id;

                return '
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">JSON</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#' . e($modalId) . '">
                            View JSON
                        </button>
                    </div>
                    <div class="modal fade" id="' . e($modalId) . '" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Headers Config (JSON)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <pre class="mb-0 p-3 border rounded small" style="background:#f3f4f6;color:#111827;white-space:pre-wrap;word-break:break-word;line-height:1.45;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">' . e($json) . '</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            },
        ]);

        CRUD::addColumn([
            'name' => 'signature_config_json',
            'label' => 'Signature Config (JSON)',
            'type' => 'text',
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'value' => function ($entry) {
                $value = $entry->signature_config_json;
                if (empty($value)) {
                    return null;
                }

                $json = is_array($value)
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : (string) $value;
                $modalId = 'partner-json-signature-' . $entry->id;

                return '
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">JSON</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#' . e($modalId) . '">
                            View JSON
                        </button>
                    </div>
                    <div class="modal fade" id="' . e($modalId) . '" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Signature Config (JSON)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <pre class="mb-0 p-3 border rounded small" style="background:#f3f4f6;color:#111827;white-space:pre-wrap;word-break:break-word;line-height:1.45;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">' . e($json) . '</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            },
        ]);

        CRUD::column('rate_limit_per_minute')->label('Rate Limit / minute')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::column('timeout_seconds')->label('Timeout (seconds)')->wrapper(['class' => 'form-group col-md-4']);
        CRUD::column('retry_attempts')->label('Retry Attempts')->wrapper(['class' => 'form-group col-md-4']);

        CRUD::addColumn([
            'name' => 'endpoints_json',
            'label' => 'Endpoints (JSON)',
            'type' => 'text',
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'value' => function ($entry) {
                $value = $entry->endpoints_json;
                if (empty($value)) {
                    return null;
                }

                $json = is_array($value)
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : (string) $value;
                $modalId = 'partner-json-endpoints-' . $entry->id;

                return '
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">JSON</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#' . e($modalId) . '">
                            View JSON
                        </button>
                    </div>
                    <div class="modal fade" id="' . e($modalId) . '" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Endpoints (JSON)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <pre class="mb-0 p-3 border rounded small" style="background:#f3f4f6;color:#111827;white-space:pre-wrap;word-break:break-word;line-height:1.45;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">' . e($json) . '</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            },
        ]);

        CRUD::addColumn([
            'name' => 'retry_backoff_ms_json',
            'label' => 'Retry Backoff (JSON ms array)',
            'type' => 'text',
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'value' => function ($entry) {
                $value = $entry->retry_backoff_ms_json;
                if (empty($value)) {
                    return null;
                }

                $json = is_array($value)
                    ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : (string) $value;
                $modalId = 'partner-json-backoff-' . $entry->id;

                return '
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">JSON</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#' . e($modalId) . '">
                            View JSON
                        </button>
                    </div>
                    <div class="modal fade" id="' . e($modalId) . '" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Retry Backoff (JSON ms array)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <pre class="mb-0 p-3 border rounded small" style="background:#f3f4f6;color:#111827;white-space:pre-wrap;word-break:break-word;line-height:1.45;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">' . e($json) . '</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            },
        ]);

        CRUD::column('notes')->type('textarea')->label('Notes')->wrapper(['class' => 'form-group col-md-12']);

        CRUD::addColumn([
            'name' => 'path_param_bindings_json',
            'label' => 'Path Param Bindings (JSON)',
            'type' => 'text',
            'escaped' => false,
            'wrapper' => ['class' => 'form-group col-md-12'],
            'value' => function ($entry) {
                $value = $entry->path_param_bindings_json;
                if (empty($value)) {
                    return null;
                }
                $json = is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $value;
                $modalId = 'partner-json-bindings-' . $entry->id;
                return '
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">JSON</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#' . e($modalId) . '">View JSON</button>
                    </div>
                    <div class="modal fade" id="' . e($modalId) . '" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Path Param Bindings (JSON)</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                <div class="modal-body"><pre class="mb-0 p-3 border rounded small" style="background:#f3f4f6;color:#111827;white-space:pre-wrap;word-break:break-word;line-height:1.45;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">' . e($json) . '</pre></div>
                            </div>
                        </div>
                    </div>';
            },
        ]);
        CRUD::column('created_at')->label('Created')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::column('updated_at')->label('Updated')->wrapper(['class' => 'form-group col-md-6']);
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(PartnerIntegrationRequest::class);

        CRUD::field('wizard_intro')
            ->type('custom_html')
            ->value('
                <div id="partner-integration-wizard" class="partner-wizard-shell mb-3 p-3 border rounded">
                    <style>
                        .partner-wizard-shell {
                            --pw-fill: var(--bs-primary, #206bc4);
                            --pw-muted: color-mix(in srgb, CanvasText 35%, transparent);
                            --pw-border: var(--bs-border-color, rgba(128,128,128,.35));
                            color: var(--bs-body-color, inherit);
                            background: var(--bs-body-bg, transparent);
                        }
                        .partner-wizard-progress-track {
                            height: 8px;
                            border-radius: 999px;
                            background: color-mix(in srgb, var(--pw-fill) 18%, transparent);
                            border: 1px solid var(--pw-border);
                            overflow: hidden;
                            margin-top: 0.75rem;
                        }
                        .partner-wizard-progress-fill {
                            height: 100%;
                            width: 25%;
                            background: var(--pw-fill);
                            transition: width 0.2s ease;
                            border-radius: inherit;
                        }
                        .partner-wizard-steps {
                            display: grid;
                            grid-template-columns: repeat(4, 1fr);
                            gap: 0.35rem;
                            font-size: 0.8rem;
                            line-height: 1.25;
                        }
                        @media (max-width: 768px) {
                            .partner-wizard-steps { font-size: 0.72rem; }
                        }
                        .wizard-step-pill {
                            cursor: pointer;
                            padding: 0.35rem 0.4rem;
                            border-radius: 0.35rem;
                            border: 1px solid var(--pw-border);
                            text-align: center;
                            opacity: 0.72;
                            transition: opacity 0.15s ease, border-color 0.15s ease;
                        }
                        .wizard-step-pill.active {
                            opacity: 1;
                            font-weight: 600;
                            border-color: var(--pw-fill);
                            box-shadow: 0 0 0 1px color-mix(in srgb, var(--pw-fill) 35%, transparent);
                        }
                    </style>
                    <div class="small text-body-secondary mb-1">Partner connection — step by step</div>
                    <div class="partner-wizard-steps" role="navigation" aria-label="Form steps">
                        <span class="wizard-step-pill active" data-step="1" title="Step 1">1 · Basics</span>
                        <span class="wizard-step-pill" data-step="2" title="Step 2">2 · Sign-in</span>
                        <span class="wizard-step-pill" data-step="3" title="Step 3">3 · API URLs &amp; maps</span>
                        <span class="wizard-step-pill" data-step="4" title="Step 4">4 · Timing &amp; notes</span>
                    </div>
                    <div class="partner-wizard-progress-track" aria-hidden="true">
                        <div class="partner-wizard-progress-fill" id="partner-wizard-progress-fill"></div>
                    </div>
                    <div class="small mt-2 text-body-secondary" id="partner-wizard-step-caption">
                        Basics: name and web address for this partner.
                    </div>
                </div>
            ');

        $partnerProviderPairs = $this->partnerCodeProviderPairs();
        $defaultPartnerPreset = array_key_first($partnerProviderPairs);
        if ($defaultPartnerPreset === null) {
            $defaultPartnerPreset = PartnerIntegrationRequest::PARTNER_CODE_OTHER;
        }

        CRUD::field('partner_code_preset')
            ->label('Partner Code')
            ->type('select_from_array')
            ->options($this->partnerCodePartnerSelectOptions())
            ->default($defaultPartnerPreset)
            ->allows_null(false)
            ->attributes(['required' => 'required', 'id' => 'partner-code-preset'])
            ->wrapper(['class' => 'form-group col-md-6 wizard-step wizard-step-1'])
            ->hint('Choose a programme provider from your catalog, or Other to type a custom partner code. Must match programme.provider and partner course mappings.')
            ->fake(true);

        CRUD::field('partner_code_manual')
            ->label('Partner Code (custom)')
            ->type('text')
            ->attributes([
                'id' => 'partner-code-manual',
                'placeholder' => 'e.g. acme-lms',
                'autocomplete' => 'off',
            ])
            ->wrapper(['class' => 'form-group col-md-6 wizard-step wizard-step-1'.($defaultPartnerPreset === PartnerIntegrationRequest::PARTNER_CODE_OTHER ? '' : ' d-none'), 'id' => 'partner-code-manual-wrapper'])
            ->hint('A short, unique ID (lowercase letters, numbers, dashes). Use the same ID wherever this partner is linked to courses.')
            ->fake(true);

        CRUD::field('partner_code_toggle_script')
            ->type('custom_html')
            ->value(view('admin.partner_integration.partner_code_toggle_script'))
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-1']);

        CRUD::field('display_name')
            ->label('Display Name')
            ->type('text')
            ->attributes(['required' => 'required'])
            ->wrapper(['class' => 'form-group col-md-6 wizard-step wizard-step-1']);

        CRUD::field('is_enabled')
            ->label('Enabled')
            ->type('checkbox')
            ->wrapper(['class' => 'form-group col-md-6 wizard-step wizard-step-1'])
            ->default(true);

        CRUD::field('base_url')
            ->label('Base URL')
            ->type('url')
            ->wrapper(['class' => 'form-group col-md-6 wizard-step wizard-step-1'])
            ->hint('The partner’s main web address for their API (usually starts with https://).');

        CRUD::field('auth_type')
            ->label('Auth Type')
            ->type('select_from_array')
            ->options([
                'none' => 'None',
                'bearer_token' => 'Bearer Token',
                'api_key_header' => 'API Key Header',
                'basic' => 'Basic Auth',
                'custom' => 'Custom',
            ])
            ->attributes(['required' => 'required'])
            ->wrapper(['class' => 'form-group col-md-6 wizard-step wizard-step-2'])
            ->default('none');

        CRUD::field('auth_config_json')
            ->label('Auth Config (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-2'])
            ->attributes([
                'rows' => 5,
                'placeholder' => "{\n  \"header_name\": \"X-Partner-Key\",\n  \"value\": \"pk_...\"\n}",
            ])
            ->hint('Secret settings for sign-in (structured text). Use the examples below or ask IT for a copy-paste block.');

        CRUD::field('auth_config_json_helper')
            ->type('custom_html')
            ->value('
                <div class="wizard-step wizard-step-2 form-group col-md-12">
                    <label class="d-block">Auth Config Helper</label>
                    <div class="small text-muted mb-2" id="auth-config-helper-note"></div>
                    <pre id="auth-config-helper-example" class="mb-2 p-2 border rounded small text-body bg-body-secondary bg-opacity-10" style="white-space:pre-wrap;"></pre>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="auth-config-apply-example-btn">
                        Apply example to Auth Config JSON
                    </button>
                </div>
            ');

        CRUD::field('partner_connection_plain_guide')
            ->type('custom_html')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->value('
                <div class="alert alert-light border shadow-sm mb-0">
                    <h6 class="alert-heading mb-2">Connecting to a partner API (plain language)</h6>
                    <p class="small mb-2 text-muted">You do not need to read every technical detail. Ask the partner for a <strong>staging checklist</strong> or copy these roles into the right boxes:</p>
                    <ol class="small mb-2 ps-3">
                        <li><strong>Base URL</strong> — Where OMCP sends requests (usually <code>https://…</code> from the partner).</li>
                        <li><strong>Auth</strong> — How we send the <em>public</em> key or token (e.g. “API Key Header” + header name + <code>pk_…</code> value).</li>
                        <li><strong>Signature (optional)</strong> — Only if the partner says each request must be <em>signed</em>. Put <code>pk_…</code> and <code>ps_…</code> in <strong>Signature config</strong> with scheme <code>hmac_timestamp_v1</code>. OMCP then generates a <strong>new</strong> time and signature on every call.</li>
                        <li><strong>Headers (optional)</strong> — Extra non-secret headers only (e.g. language). Do <strong>not</strong> paste “Timestamp” or “Signature” from Swagger examples or from a copied <code>curl</code> — those go stale or are wrong type of value.</li>
                        <li><strong>Endpoints</strong> — Paths for one student and for bulk; use presets when they match, then adjust paths the partner gave you.</li>
                    </ol>
                    <p class="small mb-0"><strong>Other partners</strong> may use only Bearer token, or static headers, or different signing — leave Signature empty unless their docs require it. The <span class="badge bg-warning text-dark">Checks</span> column on the list flags common mistakes.</p>
                </div>
            ');

        $metaHeaders = $this->partnerWizardJsonFieldMeta('headers_json');
        CRUD::field('headers_json')
            ->label('Headers Config (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 4], $metaHeaders['attributes']))
            ->hint($metaHeaders['hint']);

        $metaSig = $this->partnerWizardJsonFieldMeta('signature_config_json');
        CRUD::field('signature_config_json')
            ->label('Signature Config (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 4], $metaSig['attributes']))
            ->hint($metaSig['hint']);

        CRUD::field('refresh_timestamp_header_without_signing')
            ->label('Refresh timestamp header each request (no HMAC)')
            ->type('checkbox')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->hint('If the partner only checks that <code>X-Partner-Timestamp</code> is “current” (within ~5 minutes) and does <strong>not</strong> use HMAC signing, enable this so OMCP replaces any header whose name contains “Timestamp” with the current unix time in seconds. '
                .'<strong>If the partner uses HMAC signing</strong>, leave this off and use <strong>Signature config</strong> instead — refreshing the timestamp alone would break the signature.');

        $metaEndpoints = $this->partnerWizardJsonFieldMeta('endpoints_json');
        CRUD::field('endpoints_json')
            ->label('Endpoints (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 6], $metaEndpoints['attributes']))
            ->hint($metaEndpoints['hint']);

        CRUD::field('endpoints_json_presets')
            ->type('custom_html')
            ->value('
                <div class="wizard-step wizard-step-3 form-group col-md-12">
                    <label class="d-block">Endpoint Presets</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-endpoint-preset="startocode-default">Use sample GH integration paths</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-endpoint-preset="generic-v1">Use Generic V1</button>
                    </div>
                    <p class="help-block mt-1">Pick a sample to fill the box, then change only what your partner asked for.</p>
                </div>
            ');

        $metaBindings = $this->partnerWizardJsonFieldMeta('path_param_bindings_json');
        CRUD::field('path_param_bindings_json')
            ->label('Path Param Bindings (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 6], $metaBindings['attributes']))
            ->hint($metaBindings['hint']);

        $metaResponse = $this->partnerWizardJsonFieldMeta('response_mapping_json');
        CRUD::field('response_mapping_json')
            ->label('Response mapping (JSON) — OMCP canonical paths')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 4], $metaResponse['attributes']))
            ->hint($metaResponse['hint']);

        $metaPagination = $this->partnerWizardJsonFieldMeta('pagination_mapping_json');
        CRUD::field('pagination_mapping_json')
            ->label('Pagination mapping (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 4], $metaPagination['attributes']))
            ->hint($metaPagination['hint']);

        $metaMetrics = $this->partnerWizardJsonFieldMeta('metrics_mapping_json');
        CRUD::field('metrics_mapping_json')
            ->label('Metrics mapping (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 4], $metaMetrics['attributes']))
            ->hint($metaMetrics['hint']);

        $metaValidation = $this->partnerWizardJsonFieldMeta('validation_contract_json');
        CRUD::field('validation_contract_json')
            ->label('Validation contract (JSON)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-3'])
            ->attributes(array_merge(['rows' => 4], $metaValidation['attributes']))
            ->hint($metaValidation['hint']);

        CRUD::field('path_param_bindings_builder')
            ->type('custom_html')
            ->value('
                <div class="wizard-step wizard-step-3 form-group col-md-12">
                    <label class="d-block">Binding Helper</label>
                    <div class="row g-2">
                        <div class="col-md-3"><input id="binding_placeholder" class="form-control form-control-sm" placeholder="placeholder (e.g. stud_id)" /></div>
                        <div class="col-md-3"><select id="binding_table" class="form-select form-select-sm"><option value="">Select table</option></select></div>
                        <div class="col-md-3"><select id="binding_column" class="form-select form-select-sm"><option value="">Select column</option></select></div>
                        <div class="col-md-3"><button type="button" class="btn btn-sm btn-outline-secondary w-100" id="binding_add_btn">Add Binding</button></div>
                    </div>
                    <p class="help-block mt-1">Adds a <strong>db_lookup</strong> binding only. For fixed text (e.g. program code) use JSON with <code>source: literal</code> in the textarea, or shorthand <code>"program_slug": "gh-program"</code>. <code>{omcp_id}</code> and <code>{program_slug}</code> in endpoint paths are filled automatically — leave bindings empty unless you use extra placeholders.</p>
                </div>
            ');

        CRUD::field('rate_limit_per_minute')
            ->label('Rate Limit / minute')
            ->type('number')
            ->wrapper(['class' => 'form-group col-md-4 wizard-step wizard-step-4'])
            ->hint('Optional — how many requests per minute. Leave blank for normal.');

        CRUD::field('timeout_seconds')
            ->label('Timeout (seconds)')
            ->type('number')
            ->wrapper(['class' => 'form-group col-md-4 wizard-step wizard-step-4']);

        CRUD::field('retry_attempts')
            ->label('Retry Attempts')
            ->type('number')
            ->wrapper(['class' => 'form-group col-md-4 wizard-step wizard-step-4']);

        $metaRetry = $this->partnerWizardJsonFieldMeta('retry_backoff_ms_json');
        CRUD::field('retry_backoff_ms_json')
            ->label('Retry Backoff (JSON ms array)')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-4'])
            ->attributes(array_merge(['rows' => 3], $metaRetry['attributes']))
            ->hint($metaRetry['hint']);

        CRUD::field('notes')
            ->label('Notes')
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12 wizard-step wizard-step-4'])
            ->attributes([
                'rows' => 4,
                'placeholder' => 'Optional notes for staff (not sent to the partner).',
            ]);

        $endpointPresetsJson = json_encode(config('services.partner_endpoint_presets', []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $endpointPresetsJson = is_string($endpointPresetsJson) ? $endpointPresetsJson : '{}';

        CRUD::field('wizard_controls')
            ->type('custom_html')
            ->value('
                <input type="hidden" name="wizard_current_step" id="wizard-current-step" value="1" />
                <div id="wizard-last-step-hint" class="alert alert-info border py-2 px-3 small mb-2 d-none" role="status">
                    Last step — scroll down and click <strong>Save</strong> to keep your changes. You can also save from any earlier step.
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 mb-3 gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" id="wizard-back-btn" disabled>Back</button>
                    <button type="button" class="btn btn-primary" id="wizard-next-btn">Next</button>
                </div>
                <script>
                    (function () {
                        if (window.__partnerWizardInit) return;
                        window.__partnerWizardInit = true;

                        function ready(fn) {
                            if (document.readyState === "loading") {
                                document.addEventListener("DOMContentLoaded", fn);
                            } else {
                                fn();
                            }
                        }

                        ready(function () {
                            var maxStep = 4;
                            var currentStep = 1;
                            var backBtn = document.getElementById("wizard-back-btn");
                            var nextBtn = document.getElementById("wizard-next-btn");
                            var stepInput = document.getElementById("wizard-current-step");
                            var progressFill = document.getElementById("partner-wizard-progress-fill");
                            var stepCaptionEl = document.getElementById("partner-wizard-step-caption");
                            var lastStepHint = document.getElementById("wizard-last-step-hint");
                            var stepCaptions = [
                                "Basics: name and web address for this partner.",
                                "Sign-in: how we prove who we are to the partner system.",
                                "API details: URLs, optional signing, and maps — use partner checklist if unsure.",
                                "Timing and notes: slow-down rules and reminders for staff."
                            ];
                            if (!backBtn || !nextBtn) return;
                            var jsonFieldNames = [
                                "auth_config_json",
                                "headers_json",
                                "signature_config_json",
                                "endpoints_json",
                                "path_param_bindings_json",
                                "response_mapping_json",
                                "pagination_mapping_json",
                                "metrics_mapping_json",
                                "validation_contract_json",
                                "retry_backoff_ms_json"
                            ];
                            var storageKey = "partner_integration_wizard_step";

                            function getInputByName(name) {
                                return document.querySelector("[name=\"" + name + "\"]");
                            }

                            function clearInlineErrors() {
                                document.querySelectorAll(".wizard-inline-error").forEach(function (el) {
                                    el.remove();
                                });
                            }

                            function showError(input, message) {
                                if (!input) return;
                                var err = document.createElement("div");
                                err.className = "wizard-inline-error text-danger small mt-1";
                                err.textContent = message;
                                input.classList.add("is-invalid");
                                input.parentElement.appendChild(err);
                            }

                            function clearInvalidStyles() {
                                document.querySelectorAll(".wizard-step input, .wizard-step textarea, .wizard-step select").forEach(function (el) {
                                    el.classList.remove("is-invalid");
                                });
                            }

                            function isBlank(value) {
                                return (value || "").trim() === "";
                            }

                            function validateCurrentStep() {
                                clearInlineErrors();
                                clearInvalidStyles();
                                var valid = true;

                                if (currentStep === 1) {
                                    var partnerCode = getInputByName("partner_code");
                                    var displayName = getInputByName("display_name");
                                    if (partnerCode && isBlank(partnerCode.value)) {
                                        valid = false;
                                        showError(partnerCode, "Partner code is required.");
                                    }
                                    if (displayName && isBlank(displayName.value)) {
                                        valid = false;
                                        showError(displayName, "Display name is required.");
                                    }
                                }

                                if (currentStep === 2) {
                                    var authType = getInputByName("auth_type");
                                    if (authType && isBlank(authType.value)) {
                                        valid = false;
                                        showError(authType, "Auth type is required.");
                                    }
                                }

                                var currentStepContainer = ".wizard-step-" + currentStep;
                                jsonFieldNames.forEach(function (fieldName) {
                                    var input = getInputByName(fieldName);
                                    if (!input) return;
                                    var isInCurrentStep = input.closest(currentStepContainer) !== null;
                                    if (!isInCurrentStep || isBlank(input.value)) return;
                                    try {
                                        JSON.parse(input.value);
                                    } catch (e) {
                                        valid = false;
                                        var msg = (e && e.message) ? String(e.message) : "Invalid JSON.";
                                        showError(input, "Must be valid JSON: " + msg);
                                    }
                                });

                                return valid;
                            }

                            function paintPills() {
                                document.querySelectorAll(".wizard-step-pill").forEach(function (pill) {
                                    var step = Number(pill.getAttribute("data-step") || "1");
                                    pill.classList.toggle("active", step === currentStep);
                                });
                            }

                            function paintProgress() {
                                if (progressFill) {
                                    progressFill.style.width = (100 * currentStep / maxStep) + "%";
                                }
                                if (stepCaptionEl && stepCaptions[currentStep - 1]) {
                                    stepCaptionEl.textContent = stepCaptions[currentStep - 1];
                                }
                            }

                            function paintFields() {
                                for (var i = 1; i <= maxStep; i++) {
                                    document.querySelectorAll(".wizard-step-" + i).forEach(function (el) {
                                        el.style.display = i === currentStep ? "" : "none";
                                    });
                                }
                            }

                            function paintButtons() {
                                backBtn.disabled = currentStep === 1;
                                if (currentStep >= maxStep) {
                                    nextBtn.classList.add("d-none");
                                    nextBtn.setAttribute("tabindex", "-1");
                                    if (lastStepHint) {
                                        lastStepHint.classList.remove("d-none");
                                    }
                                } else {
                                    nextBtn.classList.remove("d-none");
                                    nextBtn.removeAttribute("tabindex");
                                    if (lastStepHint) {
                                        lastStepHint.classList.add("d-none");
                                    }
                                }
                            }

                            function render() {
                                paintPills();
                                paintFields();
                                paintProgress();
                                paintButtons();
                                if (stepInput) {
                                    stepInput.value = String(currentStep);
                                }
                                try {
                                    window.sessionStorage.setItem(storageKey, String(currentStep));
                                } catch (e) {
                                    // Ignore sessionStorage errors.
                                }
                            }

                            backBtn.addEventListener("click", function () {
                                if (currentStep > 1) {
                                    currentStep -= 1;
                                    render();
                                }
                            });

                            nextBtn.addEventListener("click", function () {
                                if (currentStep < maxStep) {
                                    if (!validateCurrentStep()) {
                                        return;
                                    }
                                    currentStep += 1;
                                    render();
                                }
                            });

                            document.querySelectorAll(".wizard-step-pill").forEach(function (pill) {
                                pill.style.cursor = "pointer";
                                pill.addEventListener("click", function () {
                                    var target = Number(pill.getAttribute("data-step") || "1");
                                    if (target >= 1 && target <= maxStep) {
                                        if (target > currentStep && !validateCurrentStep()) {
                                            return;
                                        }
                                        currentStep = target;
                                        render();
                                    }
                                });
                            });

                            var authTypeField = getInputByName("auth_type");
                            var authConfigField = getInputByName("auth_config_json");
                            var authHelperNote = document.getElementById("auth-config-helper-note");
                            var authHelperExample = document.getElementById("auth-config-helper-example");
                            var authHelperApplyBtn = document.getElementById("auth-config-apply-example-btn");
                            var authExamples = {
                                none: {
                                    note: "No secret needed if the partner does not require sign-in.",
                                    example: {}
                                },
                                bearer_token: {
                                    note: "Paste the long access token the partner gave you.",
                                    example: { token: "your-partner-token" }
                                },
                                api_key_header: {
                                    note: "The partner should tell you the header name and secret value.",
                                    example: { header_name: "X-API-Key", value: "your-api-key" }
                                },
                                basic: {
                                    note: "Username and password for the partner API, if they use this style.",
                                    example: { username: "api-user", password: "api-password" }
                                },
                                custom: {
                                    note: "Only if IT gave you a special JSON block.",
                                    example: { scheme: "custom", key: "value" }
                                }
                            };

                            function updateAuthConfigHelper() {
                                var selected = (authTypeField && authTypeField.value) ? authTypeField.value : "none";
                                var config = authExamples[selected] || authExamples.none;
                                if (authHelperNote) {
                                    authHelperNote.textContent = config.note;
                                }
                                if (authHelperExample) {
                                    authHelperExample.textContent = JSON.stringify(config.example, null, 2);
                                }
                            }

                            if (authTypeField) {
                                authTypeField.addEventListener("change", updateAuthConfigHelper);
                            }

                            if (authHelperApplyBtn) {
                                authHelperApplyBtn.addEventListener("click", function () {
                                    if (!authConfigField) return;
                                    var selected = (authTypeField && authTypeField.value) ? authTypeField.value : "none";
                                    var config = authExamples[selected] || authExamples.none;
                                    authConfigField.value = JSON.stringify(config.example, null, 2);
                                });
                            }

                            var endpointsField = getInputByName("endpoints_json");
                            var endpointPresets = ' . $endpointPresetsJson . ';

                            document.querySelectorAll("[data-endpoint-preset]").forEach(function (btn) {
                                btn.addEventListener("click", function () {
                                    if (!endpointsField) return;
                                    var key = btn.getAttribute("data-endpoint-preset");
                                    var preset = endpointPresets[key];
                                    if (!preset) return;
                                    endpointsField.value = JSON.stringify(preset, null, 2);
                                });
                            });

                            var bindingTable = document.getElementById("binding_table");
                            var bindingColumn = document.getElementById("binding_column");
                            var bindingPlaceholder = document.getElementById("binding_placeholder");
                            var bindingAddBtn = document.getElementById("binding_add_btn");
                            var bindingsField = getInputByName("path_param_bindings_json");
                            function parseJsonSafe(text) { try { return JSON.parse(text || "{}"); } catch (e) { return {}; } }
                            function refreshColumns(tableName) {
                                if (!bindingColumn) return;
                                bindingColumn.innerHTML = "<option value=\"\">Select column</option>";
                                if (!tableName) return;
                                fetch("' . backpack_url('partner-integration/schema-columns') . '?table=" + encodeURIComponent(tableName), { headers: { "Accept": "application/json" } })
                                    .then(function (r) { return r.json(); })
                                    .then(function (resp) {
                                        (resp.columns || []).forEach(function (c) {
                                            var opt = document.createElement("option");
                                            opt.value = c; opt.textContent = c; bindingColumn.appendChild(opt);
                                        });
                                    }).catch(function () {});
                            }
                            if (bindingTable) {
                                fetch("' . backpack_url('partner-integration/schema-tables') . '", { headers: { "Accept": "application/json" } })
                                    .then(function (r) { return r.json(); })
                                    .then(function (resp) {
                                        (resp.tables || []).forEach(function (t) {
                                            var opt = document.createElement("option");
                                            opt.value = t; opt.textContent = t; bindingTable.appendChild(opt);
                                        });
                                    }).catch(function () {});
                                bindingTable.addEventListener("change", function () { refreshColumns(bindingTable.value || ""); });
                            }
                            if (bindingAddBtn) {
                                bindingAddBtn.addEventListener("click", function () {
                                    if (!bindingsField) return;
                                    var placeholder = (bindingPlaceholder && bindingPlaceholder.value || "").trim();
                                    var table = (bindingTable && bindingTable.value || "").trim();
                                    var column = (bindingColumn && bindingColumn.value || "").trim();
                                    if (!placeholder || !table || !column) return;
                                    var obj = parseJsonSafe(bindingsField.value);
                                    obj[placeholder] = {
                                        source: "db_lookup",
                                        table: table,
                                        column: column,
                                        where_column: "userId",
                                        where_context_key: "omcp_id"
                                    };
                                    bindingsField.value = JSON.stringify(obj, null, 2);
                                });
                            }

                            var firstInvalid = document.querySelector(".wizard-step .is-invalid");
                            if (firstInvalid) {
                                for (var step = 1; step <= maxStep; step++) {
                                    var inStep = firstInvalid.closest(".wizard-step-" + step) !== null;
                                    if (inStep) {
                                        currentStep = step;
                                        break;
                                    }
                                }
                            } else {
                                var oldStep = Number((stepInput && stepInput.value) || "0");
                                if (oldStep >= 1 && oldStep <= maxStep) {
                                    currentStep = oldStep;
                                } else {
                                    try {
                                        var stored = Number(window.sessionStorage.getItem(storageKey) || "0");
                                        if (stored >= 1 && stored <= maxStep) {
                                            currentStep = stored;
                                        }
                                    } catch (e) {
                                        // Ignore sessionStorage errors.
                                    }
                                }
                            }

                            var form = backBtn.closest("form") || document.querySelector("form");
                            if (form) {
                                form.addEventListener("submit", function () {
                                    if (stepInput) {
                                        stepInput.value = String(currentStep);
                                    }
                                    try {
                                        window.sessionStorage.setItem(storageKey, String(currentStep));
                                    } catch (e) {
                                        // Ignore sessionStorage errors.
                                    }
                                });
                            }

                            render();
                            updateAuthConfigHelper();
                        });
                    })();
                </script>
            ');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();

        $entry = $this->crud->getCurrentEntry();
        if (! $entry) {
            return;
        }

        foreach (['auth_config_json', 'headers_json', 'signature_config_json', 'endpoints_json', 'path_param_bindings_json', 'response_mapping_json', 'pagination_mapping_json', 'metrics_mapping_json', 'validation_contract_json', 'retry_backoff_ms_json'] as $jsonField) {
            $value = $entry->{$jsonField};
            if (is_array($value)) {
                CRUD::modifyField($jsonField, [
                    'value' => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        $code = PartnerCodeNormalizer::normalize((string) $entry->partner_code);
        $pairs = $this->partnerCodeProviderPairs();
        $isPreset = array_key_exists($code, $pairs);
        CRUD::modifyField('partner_code_preset', [
            'value' => $isPreset ? $code : PartnerIntegrationRequest::PARTNER_CODE_OTHER,
        ]);
        CRUD::modifyField('partner_code_manual', [
            'value' => $isPreset ? '' : $code,
            'wrapper' => [
                'class' => 'form-group col-md-6 wizard-step wizard-step-1'.($isPreset ? ' d-none' : ''),
                'id' => 'partner-code-manual-wrapper',
            ],
        ]);
    }

    /**
     * Normalized provider slug => admin label (from distinct programme.provider).
     *
     * @return array<string, string>
     */
    private function partnerCodeProviderPairs(): array
    {
        if (! Schema::hasTable('programmes')) {
            return [];
        }

        $rows = Programme::query()
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider');

        $out = [];
        foreach ($rows as $p) {
            $n = PartnerCodeNormalizer::normalize((string) $p);
            if ($n === '') {
                continue;
            }
            $out[$n] = $this->partnerCodeProviderLabel((string) $p, $n);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function partnerCodePartnerSelectOptions(): array
    {
        $opts = $this->partnerCodeProviderPairs();
        $opts[PartnerIntegrationRequest::PARTNER_CODE_OTHER] = 'Other (enter manually)';

        return $opts;
    }

    private function partnerCodeProviderLabel(string $rawProvider, string $normalized): string
    {
        $pretty = ucwords(str_replace(['-', '_'], ' ', $normalized));

        return $pretty.' ('.$normalized.')';
    }

    /**
     * Rich help + textarea placeholder for wizard JSON fields (valid shapes the server accepts).
     *
     * @return array{hint: string, attributes: array<string, mixed>}
     */
    private function partnerWizardJsonFieldMeta(string $field): array
    {
        $endpointsExample = <<<'JSON'
{
  "single_progress": {
    "path": "/api/v2/partners/gh/integration/progress/{omcp_id}",
    "query_map": {
      "updated_since": "updated_since"
    }
  },
  "bulk_progress": {
    "path": "/api/v2/partners/gh/integration/progress/programs/{program_slug}",
    "query_map": {
      "page": "page",
      "per_page": "per_page",
      "updated_since": "updated_since"
    }
  }
}
JSON;

        $bindingsExample = <<<'JSON'
{
  "stud_id": {
    "source": "db_lookup",
    "table": "users",
    "column": "student_id",
    "where_column": "userId",
    "where_context_key": "omcp_id"
  },
  "program_slug": {
    "source": "literal",
    "value": "gh-program"
  },
  "extra_token": {
    "source": "context",
    "key": "omcp_id"
  }
}
JSON;

        $bindingsNote = 'Each key is a path placeholder name (without braces). '
            .'<strong>source</strong> must be one of: <code>context</code> (requires <code>key</code>), '
            .'<code>literal</code> (requires <code>value</code>), <code>db_lookup</code> (requires <code>table</code>, <code>column</code>, <code>where_column</code>, <code>where_context_key</code>). '
            .'You may use shorthand <code>"program_slug": "gh-program"</code> for a literal string. '
            .'Paths with only <code>{omcp_id}</code> and <code>{program_slug}</code> are filled by the sync — use <code>{}</code> or omit bindings unless you need other placeholders.';

        $responseExample = <<<'JSON'
{
  "single_student": {
    "data_root": "data",
    "external_student_ref_path": "partner_student_ref",
    "progress_root": "progress",
    "learning_paths_key": "learning_paths",
    "courses_key": "courses",
    "raw_snapshot_path": "data"
  },
  "bulk_item": {
    "internal_learner_key_paths": ["omcp_id", "external_student_id"],
    "external_student_ref_path": "partner_student_ref",
    "single_unit_path": "learning_path",
    "progress_root": "progress",
    "learning_paths_key": "learning_paths",
    "courses_key": "courses"
  }
}
JSON;

        return match ($field) {
            'headers_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Optional — extra headers only.</strong> Leave empty unless the partner names specific headers (e.g. language). '
                    .'<strong>Do not</strong> put <code>X-Partner-Timestamp</code>, a long <code>ps_…</code> string, or a 64-character hex value in <code>X-Partner-Signature</code> here — use <strong>Signature config</strong> for signing, or only what the partner confirmed is a static secret.</p>'
                    .'<p>If <strong>Signature config</strong> uses a signing <code>scheme</code>, do not duplicate those header names here (OMCP merges signing output after this).</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:240px;overflow:auto">'
                    .e('{
  "Accept-Language": "en"
}')
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => "{\n  \"X-Partner-Id\": \"partner-name\"\n}",
                ],
            ],
            'signature_config_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Optional — only for partners that document request signing.</strong> Each integration picks a <code>scheme</code>; '
                    .'unsupported schemes are ignored. For partner codes listed in env <code>PARTNER_PROGRESS_SIGNATURE_SECRET_HEADER_PARTNER_CODES</code> (default includes <code>startocode</code>, <code>telecel</code>), you may put the <code>ps_…</code> secret in <strong>Headers JSON</strong> under a header whose name contains <code>Signature</code> — OMCP will derive the live HMAC per request without pasting hex into Signature config.</p>'
                    .'<p>Otherwise use <code>hmac_timestamp_v1</code> here with <code>api_key</code> + <code>hmac_secret</code> (no static Swagger paste).</p>'
                    .'<p>Override header names if your API uses different names: <code>api_key_header</code>, <code>timestamp_header</code>, <code>signature_header</code>.</p>'
                    .'<p>Query <code>updated_since</code> is separate from auth header timestamps.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:280px;overflow:auto">'
                    .e('{
  "scheme": "hmac_timestamp_v1",
  "api_key": "pk_...",
  "hmac_secret": "ps_...",
  "message_format": "timestamp",
  "signature_encoding": "hex"
}')
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => "{\n  \"scheme\": \"hmac_timestamp_v1\",\n  \"api_key\": \"pk_...\",\n  \"hmac_secret\": \"ps_...\"\n}",
                ],
            ],
            'endpoints_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Required shape:</strong> JSON object with optional keys <code>single_progress</code> and <code>bulk_progress</code>. Each value is either a <strong>string path</strong> or '
                    .'<code>{"path":"...","query_map":{...},"skip_updated_since":false}</code>. '
                    .'Set <code>skip_updated_since</code> to <code>true</code> if the partner API rejects the incremental <code>updated_since</code> query param (sometimes reported as “invalid timestamp”).</p>'
                    .'<p><strong>Common mistakes:</strong> missing <code>"</code> on a string, missing <code>}</code>, or trailing commas. '
                    .'<code>{omcp_id}</code> and <code>{program_slug}</code> in paths are substituted automatically; use the preset buttons when possible.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:280px;overflow:auto">'
                    .e($endpointsExample)
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '{"single_progress":{"path":"/api/.../progress/{omcp_id}"},"bulk_progress":{"path":"/api/.../programs/{program_slug}"}}',
                ],
            ],
            'path_param_bindings_json' => [
                'hint' => '<div class="small">'
                    .'<p>'.$bindingsNote.'</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:320px;overflow:auto">'
                    .e($bindingsExample)
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '{}',
                ],
            ],
            'response_mapping_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Advanced.</strong> Overrides where OMCP reads fields inside the partner JSON. Use <code>single_student</code> and <code>bulk_item</code> sections; leave blank to use defaults.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:320px;overflow:auto">'
                    .e($responseExample)
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '{"single_student":{"data_root":"data","progress_root":"progress"},"bulk_item":{"internal_learner_key_paths":["omcp_id"]}}',
                ],
            ],
            'pagination_mapping_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Reserved / future.</strong> Leave empty unless IT provides a mapping object.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:200px;overflow:auto">'
                    .e('{
  "next_cursor_path": "pagination.next",
  "items_path": "data.items"
}')
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '{}',
                ],
            ],
            'metrics_mapping_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Reserved / future.</strong> Leave empty unless IT provides metrics field mapping.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:200px;overflow:auto">'
                    .e('{
  "latency_ms_path": "meta.duration_ms"
}')
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '{}',
                ],
            ],
            'validation_contract_json' => [
                'hint' => '<div class="small">'
                    .'<p><strong>Optional.</strong> Extra validation rules for stored payloads (when implemented). Leave empty unless you have a supplied contract.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:200px;overflow:auto">'
                    .e('{
  "require_fields": ["data.partner_student_ref"]
}')
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '{}',
                ],
            ],
            'retry_backoff_ms_json' => [
                'hint' => '<div class="small">'
                    .'<p>JSON <strong>array of numbers</strong> — wait times between retries in milliseconds.</p>'
                    .'<pre class="p-2 border rounded bg-light mb-0 mt-1 small" style="max-height:120px;overflow:auto">'
                    .e('[200, 600, 1200]')
                    .'</pre></div>',
                'attributes' => [
                    'placeholder' => '[200, 600, 1200]',
                ],
            ],
            default => [
                'hint' => '',
                'attributes' => [],
            ],
        };
    }

    public function schemaTables(Request $request): JsonResponse
    {
        if (!backpack_auth()->check()) {
            return response()->json(['tables' => []], 403);
        }

        $tables = config('services.partner_binding_allowed_tables', ['users']);
        if (!is_array($tables) || $tables === []) {
            $tables = ['users'];
        }

        return response()->json(['tables' => array_values($tables)]);
    }

    public function schemaColumns(Request $request): JsonResponse
    {
        if (!backpack_auth()->check()) {
            return response()->json(['columns' => []], 403);
        }
        $table = trim((string) $request->query('table', ''));
        $tables = config('services.partner_binding_allowed_tables', ['users']);
        if (!is_array($tables) || $tables === []) {
            $tables = ['users'];
        }
        if ($table === '' || !in_array($table, $tables, true) || !Schema::hasTable($table)) {
            return response()->json(['columns' => []]);
        }
        $columns = Schema::getColumnListing($table);
        $allowMap = config('services.partner_binding_allowed_columns', []);
        if (!is_array($allowMap)) {
            $allowMap = [];
        }
        $allowColumns = $allowMap[$table] ?? null;
        if (is_array($allowColumns) && $allowColumns !== []) {
            $columns = array_values(array_filter($columns, fn ($c) => in_array($c, $allowColumns, true)));
        }

        return response()->json(['columns' => $columns]);
    }

    public function testConnection(int $id): JsonResponse
    {
        if (! backpack_auth()->check()) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $integration = PartnerIntegration::query()->findOrFail($id);
        $base = rtrim((string) $integration->base_url, '/');
        if ($base === '') {
            return response()->json(['ok' => false, 'message' => 'base_url is empty'], 422);
        }

        $timeout = max(1, min((int) ($integration->timeout_seconds ?? 5), 30));

        try {
            $response = Http::timeout($timeout)->acceptJson()->get($base);
            $status = $response->status();

            return response()->json([
                'ok' => $status > 0 && $status < 500,
                'http_status' => $status,
                'message' => 'Received HTTP response from base URL.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function testParseSingle(Request $request, int $id): JsonResponse
    {
        if (! backpack_auth()->check()) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $integration = PartnerIntegration::query()->findOrFail($id);
        $omcpId = trim((string) $request->input('omcp_id', ''));
        if ($omcpId === '') {
            return response()->json(['ok' => false, 'message' => 'omcp_id is required'], 422);
        }

        $registry = app(PartnerRegistry::class);
        if (! $registry->has($integration->partner_code)) {
            return response()->json(['ok' => false, 'message' => 'No driver for partner_code'], 422);
        }

        $driver = $registry->get($integration->partner_code);
        $result = $driver->fetchStudentProgress($omcpId, null);
        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'fetch' => $result,
            ], 422);
        }

        $payload = is_array($result['payload'] ?? null) ? $result['payload'] : [];
        $normalized = $driver->normalizeSinglePayload($payload);

        return response()->json([
            'ok' => true,
            'normalized' => $normalized,
        ]);
    }

    public function testParseBulk(Request $request, int $id): JsonResponse
    {
        if (! backpack_auth()->check()) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $integration = PartnerIntegration::query()->findOrFail($id);
        $item = $request->input('item');
        if (! is_array($item)) {
            return response()->json(['ok' => false, 'message' => 'item must be a JSON object'], 422);
        }

        $programSlug = trim((string) $request->input('program_slug', 'test-program'));

        $registry = app(PartnerRegistry::class);
        if (! $registry->has($integration->partner_code)) {
            return response()->json(['ok' => false, 'message' => 'No driver for partner_code'], 422);
        }

        $driver = $registry->get($integration->partner_code);
        $normalized = $driver->normalizeBulkItem($item, $programSlug);

        return response()->json([
            'ok' => true,
            'normalized' => $normalized,
        ]);
    }
}

