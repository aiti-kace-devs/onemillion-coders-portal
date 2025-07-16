<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = ['user_id', 'course_id', 'location', 'date', 'status'];


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
