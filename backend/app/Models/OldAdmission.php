<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OldAdmission extends Model
{
    protected $table = 'old_admissions';

    protected $fillable = ['user_id', 'course_id', 'confirmed', 'email_sent', 'location', 'session'];

    protected $casts = [
        'confirmed' => 'datetime',
        'email_sent' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }
}
