<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionWaitlist extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'admission_waitlist';

    protected $fillable = [
        'user_id',
        'course_id',
        'programme_batch_id',
        'status',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the waitlist entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    /**
     * Get the course that owns the waitlist entry.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the programme batch that owns the waitlist entry.
     */
    public function programmeBatch(): BelongsTo
    {
        return $this->belongsTo(ProgrammeBatch::class, 'programme_batch_id');
    }

    /**
     * Scope a query to only include pending waitlist entries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include notified waitlist entries.
     */
    public function scopeNotified($query)
    {
        return $query->whereNotNull('notified_at');
    }

    /**
     * Scope a query to order by creation date (oldest first).
     */
    public function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Mark the waitlist entry as notified.
     */
    public function markAsNotified(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }

    /**
     * Mark the waitlist entry as converted (user booked a slot).
     */
    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
        ]);
    }

    /**
     * Remove the waitlist entry (user declined or entry expired).
     */
    public function markAsRemoved(): void
    {
        $this->update([
            'status' => 'removed',
        ]);
    }
}
