<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppConfig extends Model
{
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
