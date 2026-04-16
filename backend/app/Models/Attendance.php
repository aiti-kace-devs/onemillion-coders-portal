<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Attendance extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('attendance')
            ->setDescriptionForEvent(fn(string $event) => "Attendance {$event}");
    }

    protected $fillable = ['user_id', 'course_id', 'date', 'status'];


    public function userAdmission()
    {
        return $this->hasOne(UserAdmission::class, 'user_id', 'user_id')->latestOfMany();
    }

    public function courseSession()
    {
        return $this->hasOneThrough(
            CourseSession::class,
            UserAdmission::class,
            'user_id',
            'id',
            'user_id',
            'session'
        );
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}
