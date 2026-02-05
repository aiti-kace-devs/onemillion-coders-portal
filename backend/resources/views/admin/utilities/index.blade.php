@extends(backpack_view('blank'))

@php
    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Utilities' => false,
    ];
@endphp

@section('header')
    <section class="container-fluid d-flex align-items-center justify-content-between">
        <div>
            <h2 class="mb-0">Utilities</h2>
            <small class="text-muted">
                Run maintenance tasks and custom Artisan commands. Visible only to super admins.
            </small>
        </div>
    </section>
@endsection

@section('content')
    <div class="container-fluid">
        @if (!empty($utilities))
            <div class="row g-3 mb-4">
                @foreach ($utilities as $key => $utility)
                    <div class="col-12 col-md-4 col-xl-3">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-1">{{ $utility['label'] ?? ucfirst($key) }}</h5>
                                <p class="card-text text-muted flex-grow-1">
                                    {{ $utility['description'] ?? '' }}
                                </p>
                                <button type="button" class="btn btn-primary mt-2 js-run-utility"
                                    data-key="{{ $key }}">
                                    {{ $utility['button_label'] ?? 'Run' }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Custom Artisan Commands</span>
                <span class="text-muted small">Commands discovered in <code>App\Console\Commands</code></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Command</th>
                                <th>Description</th>
                                <th class="text-end" style="width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($commands as $command)
                                <tr>
                                    <td>
                                        <code>{{ $command['name'] }}</code>
                                    </td>
                                    <td>{{ $command['description'] }}</td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary js-run-command"
                                                data-command="{{ $command['name'] }}">
                                                Run
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary js-open-command-options"
                                                data-command="{{ $command['name'] }}">
                                                Options
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        No custom Artisan commands were found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-4" id="utilities-output-card" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Last Command Output</span>
                <span id="utilities-output-status" class="badge bg-secondary"></span>
            </div>
            <div class="card-body">
                <pre id="utilities-output" class="small mb-0"
                    style="white-space: pre-wrap; word-break: break-word; max-height: 400px; overflow: auto;"></pre>
            </div>
        </div>

        {{-- Options modal --}}
        <div class="modal fade" id="utilitiesOptionsModal" tabindex="-1" aria-labelledby="utilitiesOptionsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="utilitiesOptionsModalLabel">Command Options</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="utilities-options-form">
                            <div id="utilities-options-fields" class="row g-3 mb-3"></div>
                            <div class="mb-0">
                                <label for="utilities-raw-options" class="form-label">
                                    Additional options (CLI style, optional)
                                </label>
                                <textarea id="utilities-raw-options" class="form-control" rows="2"
                                    placeholder="Example: --force --id=123 mode=fast"></textarea>
                                <div class="form-text">
                                    Parsed like a shell: <code>--flag</code>, <code>--key=value</code>,
                                    or <code>key=value</code>.
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="utilities-options-run">
                            Run with options
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after_scripts')
    <script>
        (function() {
            const csrfToken = '{{ csrf_token() }}';
            const runUrl = '{{ backpack_url('utilities/run') }}';
            const commandConfigs = @json($commandConfigs ?? []);

            let currentCommand = null;

            function buildFieldHtml(commandName) {
                const config = commandConfigs[commandName] || {};
                const fields = Array.isArray(config.fields) ? config.fields : [];
                const parts = [];

                if (!fields.length) {
                    parts.push(
                        '<div class="col-12"><p class="text-muted small mb-2">No structured fields are configured for this command. You can still use the additional options textarea below.</p></div>'
                    );
                }

                fields.forEach(function(field) {
                    const name = field.name || '';
                    const type = field.type || 'text';
                    const label = field.label || name;
                    const placeholder = field.placeholder || '';
                    const defaultValue = field.default ?? '';

                    if (!name) {
                        return;
                    }

                    if (type === 'boolean') {
                        parts.push(
                            '<div class="col-12">' +
                            '<div class="form-check">' +
                            '<input class="form-check-input" type="checkbox" id="field-' + name +
                            '" data-name="' + name + '"' + (defaultValue ? ' checked' : '') +
                            ' />' +
                            '<label class="form-check-label" for="field-' + name + '">' +
                            label +
                            '</label>' +
                            '</div>' +
                            '</div>'
                        );
                        return;
                    }

                    if (type === 'select') {
                        const options = Array.isArray(field.options) ? field.options : [];
                        const optsHtml = options.map(function(opt) {
                            const value = typeof opt === 'object' ? (opt.value ?? '') : opt;
                            const text = typeof opt === 'object' ? (opt.label ?? value) : opt;
                            const selected = String(defaultValue) === String(value) ? ' selected' : '';
                            return '<option value="' + value + '"' + selected + '>' + text +
                                '</option>';
                        }).join('');

                        parts.push(
                            '<div class="col-12 col-md-6">' +
                            '<label class="form-label" for="field-' + name + '">' + label + '</label>' +
                            '<select class="form-select" id="field-' + name + '" data-name="' + name +
                            '">' +
                            '<option value="">-- select --</option>' +
                            optsHtml +
                            '</select>' +
                            '</div>'
                        );
                        return;
                    }

                    const inputType = (type === 'number') ? 'number' : 'text';

                    parts.push(
                        '<div class="col-12 col-md-6">' +
                        '<label class="form-label" for="field-' + name + '">' + label + '</label>' +
                        '<input type="' + inputType + '" class="form-control" id="field-' + name +
                        '" data-name="' + name + '" placeholder="' + placeholder + '" value="' +
                        defaultValue +
                        '" />' +
                        '</div>'
                    );
                });

                return parts.join('');
            }

            function collectOptionsFromForm() {
                const container = document.getElementById('utilities-options-fields');
                const inputs = container ? container.querySelectorAll('[data-name]') : [];
                const result = {};

                inputs.forEach(function(input) {
                    const name = input.getAttribute('data-name');

                    if (!name) {
                        return;
                    }

                    if (input.type === 'checkbox') {
                        result[name] = input.checked;
                    } else {
                        const value = input.value;
                        if (value !== '') {
                            result[name] = value;
                        }
                    }
                });

                return result;
            }

            async function runCommand(type, key, button, extraPayload) {
                if (!runUrl) {
                    return;
                }

                const originalHtml = button ? button.innerHTML : null;

                if (button) {
                    button.disabled = true;
                    button.classList.add('disabled');
                    button.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Running';
                }

                try {
                    const payload = Object.assign({
                        type,
                        key
                    }, (extraPayload || {}));

                    const response = await fetch(runUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    const outputCard = document.getElementById('utilities-output-card');
                    const outputStatus = document.getElementById('utilities-output-status');
                    const outputEl = document.getElementById('utilities-output');

                    if (outputCard && outputStatus && outputEl) {
                        outputCard.style.display = 'block';
                        outputEl.textContent = data.output || '';

                        if (data.status === 'success') {
                            outputStatus.className = 'badge bg-success';
                            outputStatus.textContent = 'Success';
                        } else {
                            outputStatus.className = 'badge bg-danger';
                            outputStatus.textContent = 'Error';
                        }
                    }

                    if (typeof Noty !== 'undefined') {
                        new Noty({
                            type: data.status === 'success' ? 'success' : 'error',
                            text: data.message || (data.status === 'success' ?
                                'Command executed successfully.' :
                                'Command failed.'),
                        }).show();
                    }
                } catch (e) {
                    if (typeof Noty !== 'undefined') {
                        new Noty({
                            type: 'error',
                            text: 'Unexpected error while running the command.',
                        }).show();
                    }
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.classList.remove('disabled');
                        if (originalHtml) {
                            button.innerHTML = originalHtml;
                        }
                    }
                }
            }

            document.addEventListener('click', function(event) {
                const commandButton = event.target.closest('.js-run-command');
                if (commandButton) {
                    runCommand('custom', commandButton.dataset.command, commandButton);
                    return;
                }

                const optionsButton = event.target.closest('.js-open-command-options');
                if (optionsButton) {
                    currentCommand = optionsButton.dataset.command;

                    const modalEl = document.getElementById('utilitiesOptionsModal');
                    const fieldsContainer = document.getElementById('utilities-options-fields');
                    const rawOptions = document.getElementById('utilities-raw-options');
                    const titleEl = document.getElementById('utilitiesOptionsModalLabel');

                    if (fieldsContainer) {
                        fieldsContainer.innerHTML = buildFieldHtml(currentCommand);
                    }

                    if (rawOptions) {
                        rawOptions.value = '';
                    }

                    if (titleEl) {
                        titleEl.textContent = 'Options for ' + currentCommand;
                    }

                    if (typeof bootstrap !== 'undefined' && modalEl) {
                        // Ensure the modal is attached directly to <body> so the backdrop
                        // and stacking context behave correctly in Backpack layouts.
                        if (modalEl.parentNode !== document.body) {
                            document.body.appendChild(modalEl);
                        }

                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    }

                    return;
                }

                const utilityButton = event.target.closest('.js-run-utility');
                if (utilityButton) {
                    runCommand('utility', utilityButton.dataset.key, utilityButton);
                }
            });

            const runWithOptionsButton = document.getElementById('utilities-options-run');
            if (runWithOptionsButton) {
                runWithOptionsButton.addEventListener('click', function() {
                    if (!currentCommand) {
                        return;
                    }

                    const rawOptionsEl = document.getElementById('utilities-raw-options');
                    const options = collectOptionsFromForm();
                    const rawOptions = rawOptionsEl ? rawOptionsEl.value : '';

                    runCommand('custom', currentCommand, runWithOptionsButton, {
                        options: options,
                        raw_options: rawOptions
                    });
                });
            }
        })();
    </script>
@endpush
