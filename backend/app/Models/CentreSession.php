<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CentreSession extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    protected $table = 'centre_sessions';

    protected $fillable = [
        'name',
        'centre_id',
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
            ->setDescriptionForEvent(fn(string $event) => "Centre Session {$event}");
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setSessionName();
        });

        static::updating(function ($model) {
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
