<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserAdmission extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('user_admission')
            ->setDescriptionForEvent(fn(string $event) => "User Admission {$event}");
    }

    protected $table = 'user_admission';

    protected $fillable = [
        'user_id',
        'course_batch_id',
        'programme_batch_id',
        'batch_id',
        'course_id',
        'email_sent',
        'session',
        'location',
        'confirmed'
    ];

    protected $casts = [
        'confirmed' => 'datetime',
        'email_sent' => 'datetime',
    ];

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
        return $this->sessionRecord();
    }

    public function sessionRecord()
    {
        return $this->belongsTo(CourseSession::class, 'session');
    }

    public function courseSessionOnly()
    {
        return $this->sessionRecord()->where('session_type', CourseSession::TYPE_COURSE);
    }

    public function centreSession()
    {
        return $this->sessionRecord()->where('session_type', CourseSession::TYPE_CENTRE);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function programmeBatch()
    {
        return $this->belongsTo(ProgrammeBatch::class, 'programme_batch_id');
    }
}
