<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseBatch extends Model
{
    use CrudTrait;
    use HasFactory;

    // Renamed from course_batches to programme_batches
    protected $table = 'programme_batches';

    protected $fillable = ['course_id', 'batch_id', 'duration', 'start_date', 'end_date', 'available_slots'];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'available_slots' => 'integer',
        'duration'        => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * Get all admissions for this programme batch
     */
    public function admissions()
    {
        return $this->hasMany(UserAdmission::class, 'programme_batch_id');
    }

    /**
     * Get all users admitted to this programme batch
     */
    public function admittedUsers()
    {
        return $this->hasManyThrough(User::class, UserAdmission::class, 'programme_batch_id', 'userId', 'id', 'user_id');
    }

    /**
     * Get all attendances for this programme batch
     */
    public function attendances()
    {
        return $this->hasManyThrough(
            Attendance::class,
            UserAdmission::class,
            'programme_batch_id',
            'user_id',
            'id',
            'user_id'
        )->whereBetween('attendances.date', [$this->start_date, $this->end_date]);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeOverlapping($query, string $start, string $end)
    {
        return $query->where('start_date', '<=', $end)->where('end_date', '>=', $start);
    }

    public function scopeHasAvailableSlots($query)
    {
        return $query->where('available_slots', '>', 0);
    }
}
