<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CourseSession extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public const TYPE_COURSE = 'course';

    public const TYPE_CENTRE = 'centre';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('course_session')
            ->setDescriptionForEvent(fn (string $event) => "Course Session {$event}");
    }

    protected $table = 'course_sessions';

    protected $fillable = [
        'name',
        'course_id',
        'centre_id',
        'session_type',
        'centre_sync_key',
        'limit',
        'course_time',
        'session',
        'link',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->session_type)) {
                $model->session_type = self::TYPE_COURSE;
            }

            $model->setSessionName();
        });

        static::updating(function ($model) {
            if (empty($model->session_type)) {
                $model->session_type = self::TYPE_COURSE;
            }

            $model->setSessionName();
        });
    }

    public function setSessionName()
    {
        if ($this->session_type && $this->session_type !== self::TYPE_COURSE) {
            return;
        }

        $course = $this->course()->first();
        if ($course) {
            $this->name = "{$course->course_name} - {$this->session} Session";
        }
    }

    public function scopeCourseType($query)
    {
        return $query->where('session_type', self::TYPE_COURSE);
    }

    public function scopeCentreType($query)
    {
        return $query->where('session_type', self::TYPE_CENTRE);
    }

    public function slotLeft()
    {
        $sessionIds = $this->sharedSessionIds();
        $used = UserAdmission::whereIn('session', $sessionIds)->whereNotNull('confirmed')->count();

        return $this->limit - $used;
    }

    protected function sharedSessionIds(): array
    {
        $course = $this->course;
        if (! $course || ! $course->isOnlineProgramme()) {
            return [$this->id];
        }

        $courseIds = $course->siblingCourseIdsForProgrammeBatch();
        if (empty($courseIds)) {
            return [$this->id];
        }

        return self::query()
            ->whereIn('course_id', $courseIds)
            ->where('session', $this->session)
            ->pluck('id')
            ->all();
    }
}
