<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AppConfigController extends Controller
{
    /**
     * Display a listing of the app configs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $configs = AppConfig::all();
        return view('admin.app-config.index', compact('configs'));
    }
    /**
     * Update the application settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validatedData = [];
        $errors = [];

        $configKeys = AppConfig::pluck('key')->toArray();
        // Validate each config value individually
        foreach ($request->all() as $key => $value) {
            // Skip _token and _method fields
            if ($key === '_token' || $key === '_method') {
                continue;
            }

            $config = AppConfig::where('key', $key)->first();

            if (!$config) {
                $errors[$key] = "Configuration key '{$key}' not found.";
                continue; // Skip to the next iteration
            }

            // Use the type from the database to determine validation rules
            switch ($config->type) {
                case 'integer':
                    $validatedData[$key] = ['nullable', 'integer'];
                    break;
                case 'boolean':
                    $validatedData[$key] = ['nullable', 'boolean'];
                    $value = isset($request->{$key}) && in_array($request->{$key}, ['true', '1', 'on', true], true);
                    $request->request->set($key, $value); // Update the request object
                    break;
                case 'json':
                    $validatedData[$key] = ['nullable', 'json'];
                    break;
                case 'array':
                    $validatedData[$key] = ['nullable', 'array'];
                    break;
                case 'string':
                default:
                    $validatedData[$key] = ['nullable', 'string'];
            }
        }
        // Perform the validation
        $validator = Validator::make($request->all(), $validatedData);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update each configuration value
        foreach ($configKeys as $key) {
            $config = AppConfig::where('key', $key)->first();

            if (!$config) {
                continue;
            }

            $value = $request->input($key); // Use $request->input()


            if ($config->type == 'boolean') {
                $value = isset($request->{$key}) && in_array($request->{$key}, ['true', '1', 'on', true], true) ? true : false;
            }

            if ($key == 'send_email_registration') {
                $value = true;
            }

            $updateData = ['value' => $value];

            if ($config->type === 'array') {
                $updateData['value'] = serialize($value);
            }


            $config->update($updateData);

            //update the config in laravel
            $this->updateLaravelConfig($key, $config);
        }

        return redirect()->route('admin.config.index')->with(['key' => 'success', 'flash' => 'Application settings updated successfully.']);
    }

    private function updateLaravelConfig(string $key, AppConfig $config)
    {
        // $value = $config->value;
        $value = AppConfig::castValue($config);
        if ($config->is_cached) {
            Cache::forget($key);
            Cache::rememberForever($key, function () use ($value) {
                return $value;
            });
        }
        Config::set($key, $value);
    }
}
