<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionWaitlist extends Model
{
    use HasFactory;

    protected $table = 'admission_waitlist';

    protected $fillable = ['user_id', 'course_id', 'notified_at'];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
