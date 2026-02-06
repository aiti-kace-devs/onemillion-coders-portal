<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseBatch extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'course_batches';

    protected $fillable = ['course_id', 'batch_id', 'duration', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * Get all admissions for this course batch
     */
    public function admissions()
    {
        return $this->hasMany(UserAdmission::class, 'course_batch_id');
    }

    /**
     * Get all users admitted to this course batch
     */
    public function admittedUsers()
    {
        return $this->hasManyThrough(User::class, UserAdmission::class, 'course_batch_id', 'userId', 'id', 'user_id');
    }

    /**
     * Get all attendances for this course batch
     */
    public function attendances()
    {
        return $this->hasManyThrough(
            Attendance::class,
            UserAdmission::class,
            'course_batch_id',
            'user_id',
            'id',
            'user_id'
        )->whereBetween('attendances.date', [$this->start_date, $this->end_date]);
    }
}
