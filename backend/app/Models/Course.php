<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'centre_id',
        'programme_id',
        'course_name',
        'location',
        'duration',
        'start_date',
        'end_date',
        'status',
        'auto_admit_on',
        'auto_admit_limit',
        'auto_admit_enabled',
    ];

    protected $casts = [
        'status' => 'boolean',
        'auto_admit_on' => 'date',
        'auto_admit_enabled' => 'boolean',
        'last_auto_admit_at' => 'datetime',
    ];

    protected $with = ['batch'];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'course_batches', 'course_id', 'batch_id');
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

    /**
     * Get all rules assigned to this course
     */
    public function rules()
    {
        return $this->morphToMany(Rule::class, 'ruleable', 'rule_assignments')
            ->withPivot(['value', 'priority'])
            ->withTimestamps()
            ->orderBy('rule_assignments.priority', 'asc');
    }

    /**
     * Get effective rules for admission
     * Returns course rules if exists, otherwise inherits from programme
     */
    public function getEffectiveRules()
    {
        $courseRules = $this->rules()->where('rule_assignments.is_active', true)->get();

        if ($courseRules->isNotEmpty()) {
            return $courseRules;
        }
        // Fallback to programme rules
        return $this->programme->rules()->where('rule_assignments.is_active', true)->get();
    }

    public function getAllRules()
    {
        // always add pass mark rule 
        $courseRules = $this->rules()->get();

        if ($courseRules->isNotEmpty()) {
            return $courseRules;
        }
        // Fallback to programme rules
        return $this->programme->rules()->get();
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
            $course->no_of_days = $durations[$course->duration] ?? null;

            $programme = $course->programme()->first();
            $centre = $course->centre()->with('branch')->first();
            $branch = $centre?->branch;

            $course->course_name = $programme && $branch
                ? "{$programme->title} - ({$branch->title})"
                : $course->course_name;

            $course->location = $branch?->title;
        });
    }
}
