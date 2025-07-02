<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CourseSession extends Model
{
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function slotLeft()
    {
        $used = UserAdmission::where('session', $this->id)->whereNotNull('confirmed')->count();
        return $this->limit - $used;
    }
}
