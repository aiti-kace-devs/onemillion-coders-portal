<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CourseSession extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'course_sessions';

    protected $fillable = [
        'name',
        'course_id',
        'limit',
        'course_time',
        'session',
        'link'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            $model->setSessionName();
        });

        static::updating(function ($model) {
            $model->setSessionName();
        });
    }

    public function setSessionName()
    {
        $course = $this->course()->first();
        if ($course) {
            $this->name = "{$course->course_name} - {$this->session} Session";
        }
    }

    public function slotLeft()
    {
        $used = UserAdmission::where('session', $this->id)->whereNotNull('confirmed')->count();
        return $this->limit - $used;
    }
}
