<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model
{
    use HasFactory;

    public const KEY_OCCUPANCY_DRIFT = 'occupancy_drift';

    public const STATUS_PENDING = 'pending';
    public const STATUS_REPAIRING = 'repairing';
    public const STATUS_REPAIRED = 'repaired';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'key',
        'type',
        'severity',
        'status',
        'title',
        'message',
        'payload',
        'detected_at',
        'action_due_at',
        'resolved_at',
        'resolved_by_admin_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'detected_at' => 'datetime',
        'action_due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function scopeVisible($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_REPAIRING,
            self::STATUS_FAILED,
        ]);
    }

    public static function visibleOccupancyDrift(): ?self
    {
        return self::query()
            ->where('key', self::KEY_OCCUPANCY_DRIFT)
            ->visible()
            ->latest('detected_at')
            ->first();
    }
}
