<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class AppConfig extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'app_configs';

    protected $fillable = [
        'key',
        'value',
        'type',
        'is_cached',
    ];

    protected $casts = [ // Add this for automatic type casting
        'is_cached' => 'boolean',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Listen for the updated event to reset cached configuration
        static::updated(function (AppConfig $config) {
            $config->updateLaravelConfig();
        });
    }

    /**
     * Update Laravel configuration and reset cache if needed.
     */
    protected function updateLaravelConfig()
    {
        $value = self::castValue($this);

        if ($this->is_cached) {
            Cache::forget($this->key);
            Cache::rememberForever($this->key, function () use ($value) {
                return $value;
            });
        }

        Config::set($this->key, $value);
    }

    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();

        if (!$config) {
            return $default;
        }

        return self::castValue($config);
    }

    public static function castValue(AppConfig $config)
    {
        switch ($config->type) {
            case 'integer':
                return (int) $config->value;
            case 'boolean':
                return (bool) $config->value;
            case 'json':
                return json_decode($config->value, true);
            case 'array':
                return unserialize($config->value);
            default:
                return $config->value;
        }
    }
}
