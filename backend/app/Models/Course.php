<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use CrudTrait;
    use HasFactory;

    use \StatamicRadPack\Runway\Traits\HasRunwayResource;

    protected $fillable = [
        'centre_id',
        'programme_id',
        'course_name',
        'location',
        'duration',
        'start_date',
        'end_date',
        'status',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function assignedAdmins()
    {
        return $this->belongsToMany(Admin::class, 'admin_course', 'course_id', 'admin_id');
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class, 'course_id',);
    }

    public function scopeMyAssignedCourses($query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereNull('id');
        }

        if ($user->can('attendance.status')) {
            return $query;
        } else {
            return $query->whereIn('courses.id', $user->assignedCourses()->pluck('id')->toArray());
        }
    }
}
