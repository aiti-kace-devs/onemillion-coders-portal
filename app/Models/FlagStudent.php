<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlagStudent extends Model
{
    use HasFactory;
    protected $table = 'flag_students';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'gender',
        'mobile_no',
        'age',
        'registered_course',
        'userId',
        'flag_course',
        'created_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'registered_course', 'id');
    }
}
