<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAdmission extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'user_admission';

    protected $fillable = ['user_id', 'batch_id', 'course_id', 'email_sent', 'session', 'location', 'confirmed'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function courseSession()
    {
        return $this->belongsTo(CourseSession::class, 'session');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }
}
