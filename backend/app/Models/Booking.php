<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use CrudTrait;
    use HasFactory;
    use LogsActivity;

    protected $table = 'bookings';

    protected $fillable = [
        'user_id',
        'programme_batch_id',
        'course_session_id',
        'master_session_id',
        'centre_id',
        'course_id',
        'course_type',
        'status',
        'booked_at',
        'cancelled_at',
        'user_admission_id',
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('booking')
            ->setDescriptionForEvent(fn(string $event) => "Booking {$event}");
    }

    protected static function booted(): void
    {
        // Safety net for ad-hoc writers (Backpack CRUD, seeders, imports).
        // BookingService sets course_type explicitly, so this is skipped on the hot path.
        static::saving(function (Booking $booking) {
            if (empty($booking->course_type) && $booking->course_id) {
                $booking->course_type = self::resolveCourseType($booking->course_id);
            }
        });
    }

    public static function resolveCourseType(int $courseId): string
    {
        $course = Course::with('programme:id,time_allocation')->find($courseId);
        return $course?->programme?->courseType()
            ?? ($course->programme->time_allocation == 2 ? Programme::COURSE_TYPE_SHORT : Programme::COURSE_TYPE_LONG);
    }

    public function programmeBatch(): BelongsTo
    {
        return $this->belongsTo(ProgrammeBatch::class, 'programme_batch_id');
    }

    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'course_session_id');
    }

    public function masterSession(): BelongsTo
    {
        return $this->belongsTo(MasterSession::class, 'master_session_id');
    }

    public function session()
    {
        if ($this->course_session_id) {
            return $this->belongsTo(CourseSession::class, 'course_session_id');
        } else {
            return $this->belongsTo(MasterSession::class, 'master_session_id');
        }
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(Centre::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function userAdmission(): BelongsTo
    {
        return $this->belongsTo(UserAdmission::class, 'user_admission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', true);
    }

    public function reminders()
    {
        return $this->hasMany(BookingReminder::class, 'booking_id');
    }

}
