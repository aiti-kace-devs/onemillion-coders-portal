<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCentreBooking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'centre_time_block_id',
        'user_admission_id',
        'status',
        'idempotency_key',
    ];

    public function centreTimeBlock(): BelongsTo
    {
        return $this->belongsTo(CentreTimeBlock::class, 'centre_time_block_id');
    }

    public function userAdmission(): BelongsTo
    {
        return $this->belongsTo(UserAdmission::class, 'user_admission_id');
    }
}
