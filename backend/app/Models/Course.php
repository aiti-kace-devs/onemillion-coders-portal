<?php

namespace App\Models;

use App\Models\CourseBatch;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Course extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('course')
            ->setDescriptionForEvent(fn(string $event) => "Course {$event}");
    }

    protected $fillable = [
        'centre_id',
        'programme_id',
        'course_name',
        // 'location',
        'duration',
        'start_date',
        'batch_id',
        'end_date',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Get the display name with centre for dropdowns
     */
    public function getDisplayNameAttribute()
    {
        return $this->course_name ?: ($this->centre?->title ?? 'Unknown Centre');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function isOnlineProgramme(): bool
    {
        $mode = $this->programme?->mode_of_delivery;
        return strtolower(trim((string) $mode)) === 'online';
    }

    public function siblingCourseIdsForProgrammeBatch(): array
    {
        if (!$this->programme_id || !$this->batch_id) {
            return $this->id ? [$this->id] : [];
        }

        return self::query()
            ->where('programme_id', $this->programme_id)
            ->where('batch_id', $this->batch_id)
            ->pluck('id')
            ->all();
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function batches()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function assignedAdmins()
    {
        return $this->belongsToMany(Admin::class, 'admin_course', 'course_id', 'admin_id')
            ->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class, 'course_id');
    }

    public function programmeBatches()
    {
        return $this->hasMany(CourseBatch::class, 'course_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function scopeMyAssignedCourses($query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereNull('courses.id');
        }

        if ($user->can('attendance.status')) {
            return $query;
        } else {
            // Get the assigned course IDs for the current user
            $assignedCourseIds = $user->assignedCourses()->pluck('courses.id')->toArray();

            // If user has no assigned courses, return empty result
            if (empty($assignedCourseIds)) {
                return $query->whereNull('courses.id');
            }

            return $query->whereIn('courses.id', $assignedCourseIds);
        }
    }


    protected static function booted()
    {
        static::deleting(function ($course) {
            // Ensure dependent records are removed first (FK constraints are restrict in the DB).
            $course->sessions()->delete();
            $course->assignedAdmins()->detach();
        });

        static::saving(function ($course) {
            $durations = [
                '1 Week' => 5,
                '1 week' => 5,
                '2 Weeks' => 10,
                '2 weeks' => 10,
                '3 Weeks' => 15,
                '3 weeks' => 15,
                '4 Weeks' => 20,
                '4 weeks' => 20,
                '1 Month' => 20,
                '1 month' => 20,
                '2 Months' => 40,
                '2 months' => 40,
                '3 Months' => 90,
                '3 months' => 90,
                '4 Months' => 120,
                '4 months' => 120,
            ];
            // $course->no_of_days = $durations[$course->duration] ?? null;

            $programme = $course->programme()->first();
            $centre = $course->centre()->with('branch')->first();
            $branch = $centre?->branch;

            $course->course_name = $programme && $centre
                ? "{$programme->title} - ({$centre->title})"
                : $course->course_name;

            // $course->location = $branch?->title;
        });

        static::saved(function ($course) {
            if ($course->wasChanged(['course_name'])) {
                $course->sessions()->get()->each(function ($session) {
                    $session->setSessionName();
                    $session->save();
                });
            }
        });
    }
}
