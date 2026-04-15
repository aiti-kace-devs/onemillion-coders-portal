<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MasterSession extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    protected $table = 'master_sessions';

    protected $fillable = [
        'master_name',
        'session_type',
        'time',
        'course_type',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('master_session')
            ->setDescriptionForEvent(fn (string $event) => "Master Session {$event}");
    }

    public function centreSessions()
    {
        return $this->hasMany(CourseSession::class, 'master_session_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
