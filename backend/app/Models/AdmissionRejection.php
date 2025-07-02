<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionRejection extends Model
{
    use HasFactory;
    protected $table = 'admission_rejections';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'course_id',
        'rejected_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}
