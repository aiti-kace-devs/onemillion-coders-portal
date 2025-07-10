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


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}
