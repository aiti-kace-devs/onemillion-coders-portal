<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class UtilitiesController extends AdminController
{
    /**
     * Show the utilities dashboard.
     */
    public function index()
    {
        $this->authorizeUtilities();

        $commands = $this->getCustomCommands();
        $utilities = config('utilities.utilities', []);
        $commandConfigs = config('utilities.commands', []);

        return view('admin.utilities.index', [
            'commands' => $commands,
            'utilities' => $utilities,
            'commandConfigs' => $commandConfigs,
        ]);
    }

    /**
     * Run a selected command or utility action via AJAX.
     */
    public function run(Request $request): JsonResponse
    {
        $this->authorizeUtilities();

        $validated = $request->validate([
            'type' => ['required', 'in:custom,utility'],
            'key' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
            'raw_options' => ['sometimes', 'string'],
        ]);

        $type = $validated['type'];
        $key = $validated['key'];
        $options = $validated['options'] ?? [];
        $rawOptions = $validated['raw_options'] ?? null;

        try {
            if ($type === 'custom') {
                $command = $this->getCustomCommands()
                    ->firstWhere('name', $key);

                if (! $command) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Command not allowed or not found.',
                        'output' => '',
                        'exit_code' => 1,
                    ], 404);
                }

                $args = $this->buildCommandArguments($key, $options, $rawOptions);

                Artisan::call($key, $args);
            } else {
                $utilities = config('utilities.utilities', []);
                $utility = Arr::get($utilities, $key);

                if (! $utility) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Utility action not allowed or not found.',
                        'output' => '',
                        'exit_code' => 1,
                    ], 404);
                }

                $artisanCommand = $utility['command'] ?? null;
                $baseOptions = $utility['options'] ?? [];

                if (! $artisanCommand) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Utility action is misconfigured.',
                        'output' => '',
                        'exit_code' => 1,
                    ], 500);
                }

                $args = $this->buildCommandArguments($artisanCommand, $options, $rawOptions, $baseOptions);

                Artisan::call($artisanCommand, $args);
            }

            $output = Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Command executed successfully.',
                'output' => $output,
                'exit_code' => 0,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'output' => $e->getTraceAsString(),
                'exit_code' => $e->getCode() ?: 1,
            ], 500);
        }
    }

    /**
     * Discover custom Artisan commands in the application.
     */
    protected function getCustomCommands(): Collection
    {
        $all = collect(Artisan::all());
        $blacklist = [
            'app:admit-students',
        ];

        return $all
            ->filter(function ($command, $name) {
                $class = get_class($command);

                return str_starts_with($class, 'App\\Console\\Commands\\');
            })
            ->reject(function ($command, $name) use ($blacklist) {
                return in_array($name, $blacklist, true);
            })
            ->map(function ($command, $name) {
                $config = config('utilities.commands.' . $name, []);

                return [
                    'name' => $name,
                    'label' => $config['label'] ?? $name,
                    'description' => $config['description'] ?? ($command->getDescription() ?: 'No description'),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * Build Artisan argument array from configured fields and raw options.
     */
    protected function buildCommandArguments(string $signature, array $options = [], ?string $rawOptions = null, array $baseOptions = []): array
    {
        $args = $baseOptions;

        $config = config('utilities.commands.' . $signature);

        if (is_array($config) && ! empty($config['fixed_options']) && is_array($config['fixed_options'])) {
            $args = array_merge($args, $config['fixed_options']);
        }

        if (is_array($config) && ! empty($config['fields']) && is_array($config['fields'])) {
            foreach ($config['fields'] as $field) {
                $name = $field['name'] ?? null;

                if (! $name || ! array_key_exists($name, $options)) {
                    continue;
                }

                $value = $options[$name];

                // Skip empty strings/null unless boolean
                if ($value === '' || $value === null) {
                    continue;
                }

                $mode = $field['mode'] ?? 'option'; // 'option' or 'argument'

                if ($mode === 'argument') {
                    $args[$name] = $value;
                } else {
                    $optionName = $field['option'] ?? $name;

                    if (! str_starts_with($optionName, '--')) {
                        $optionName = '--' . $optionName;
                    }

                    $args[$optionName] = $value;
                }
            }
        }

        if ($rawOptions) {
            $args = array_merge($args, $this->parseRawOptions($rawOptions));
        }

        return $args;
    }

    /**
     * Parse a CLI-style options string into an array for Artisan::call().
     */
    protected function parseRawOptions(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        $tokens = str_getcsv($raw, ' ');
        $parsed = [];

        foreach ($tokens as $token) {
            $token = trim($token);

            if ($token === '') {
                continue;
            }

            if (str_starts_with($token, '--')) {
                $equalsPosition = strpos($token, '=');

                if ($equalsPosition === false) {
                    $parsed[$token] = true;
                } else {
                    $name = substr($token, 0, $equalsPosition);
                    $value = substr($token, $equalsPosition + 1);
                    $parsed[$name] = $value;
                }
            } elseif (str_contains($token, '=')) {
                [$name, $value] = explode('=', $token, 2);
                $parsed[$name] = $value;
            } else {
                $parsed[$token] = true;
            }
        }

        return $parsed;
    }

    /**
     * Ensure the current user is allowed to access utilities.
     */
    protected function authorizeUtilities(): void
    {
        $user = backpack_auth()->user();

        if (! $user) {
            abort(403);
        }

        $requiredRole = config('utilities.super_admin_role', 'super-admin');

        if (method_exists($user, 'hasRole') && $user->hasRole($requiredRole)) {
            return;
        }

        if (method_exists($user, 'isSuper') && $user->isSuper()) {
            return;
        }

        abort(403);
    }
}
