<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_credentials',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function programmes()
    {
        return $this->hasMany(Programme::class);
    }

    /**
     * Accessor: returns decrypted credentials as a pretty-printed JSON STRING.
     * Backpack's textarea will echo this directly without issues.
     */
    public function getApiCredentialsAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            $decrypted = decrypt($value);

            // New format: JSON string stored
            $decoded = json_decode($decrypted, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            // Old format: PHP-serialized array (encrypted:array cast)
            $unserialized = @unserialize($decrypted);
            if ($unserialized !== false && is_array($unserialized)) {
                return json_encode($unserialized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mutator: accepts a JSON string (from textarea) or array, encrypts and stores it.
     */
    public function setApiCredentialsAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['api_credentials'] = encrypt(json_encode($value));
        } elseif (is_string($value) && !empty(trim($value))) {
            // Validate it's parseable JSON before storing
            $decoded = json_decode($value, true);
            $this->attributes['api_credentials'] = json_last_error() === JSON_ERROR_NONE
                ? encrypt(json_encode($decoded))
                : encrypt(json_encode([]));
        } else {
            $this->attributes['api_credentials'] = null;
        }
    }

    /**
     * Helper used by integrations: returns credentials as a PHP array.
     */
    public function getCredentialsArray(): array
    {
        $json = $this->api_credentials; // calls accessor → JSON string
        if (!$json) {
            return [];
        }
        return json_decode($json, true) ?? [];
    }

    public function admissions()
    {
        return $this->hasMany(PartnerStudentAdmission::class);
    }
}
