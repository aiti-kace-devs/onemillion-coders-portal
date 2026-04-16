<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GhanaCardVerification extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = [
        'user_id',
        'pin_number',
        'transaction_guid',
        'success',
        'verified',
        'code',
        'person_data',
        'request_timestamp',
        'response_timestamp',
        'status_message',
    ];

    protected $casts = [
        'success' => 'boolean',
        'verified' => 'boolean',
        'person_data' => 'array',
        'request_timestamp' => 'datetime',
        'response_timestamp' => 'datetime',
    ];

    /**
     * Get the user that owns the verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful verifications.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('code', '00');
    }

    /**
     * Scope for NIA watch list verifications.
     */
    public function scopeOnWatchList($query)
    {
        return $query->where('code', '03');
    }
}
