<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingWaitlist extends Model
{
    protected $table = 'booking_waitlist';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_PROMOTED = 'promoted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'centre_time_block_id',
        'user_admission_id',
        'position',
        'status',
    ];

    protected $casts = [
        'position' => 'integer',
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
