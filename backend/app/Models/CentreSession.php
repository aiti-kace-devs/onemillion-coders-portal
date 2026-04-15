<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CentreSession extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    protected $table = 'course_sessions';

    protected $fillable = [
        'name',
        'master_session_id',
        'course_id',
        'centre_id',
        'session_type',
        'centre_sync_key',
        'limit',
        'course_time',
        'session',
        'link',
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
            ->useLogName('centre_session')
            ->setDescriptionForEvent(fn (string $event) => "Centre Session {$event}");
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id', 'id');
    }

    public function masterSession()
    {
        return $this->belongsTo(MasterSession::class, 'master_session_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('centre', function ($query) {
            $query->where('session_type', CourseSession::TYPE_CENTRE);
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->centre_sync_key)) {
                $model->centre_sync_key = (string) Str::uuid();
            }
            $model->session_type = CourseSession::TYPE_CENTRE;
            $model->course_id = null;
            $model->setSessionName();
        });

        static::updating(function ($model) {
            $model->session_type = CourseSession::TYPE_CENTRE;
            $model->course_id = null;
            $model->setSessionName();
        });
    }

    public function setSessionName(): void
    {
        $centre = $this->centre()->first();
        if ($centre) {
            $this->name = "{$centre->title} - {$this->session} Session";
        }
    }
}
