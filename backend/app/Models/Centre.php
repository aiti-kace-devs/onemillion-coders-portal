<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Centre extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('centre')
            ->setDescriptionForEvent(fn(string $event) => "Centre {$event}");
    }

    protected $fillable = [
        'title',
        'branch_id',
        'status',
        'gps_address',
        'is_pwd_friendly',
        'wheelchair_accessible',
        'has_access_ramp',
        'has_accessible_toilet',
        'has_elevator',
        'supports_hearing_impaired',
        'supports_visually_impaired',
        'staff_trained_for_pwd',
        'accessibility_rating',
        'pwd_notes',
    ];


    protected $casts = [
        'status' => 'boolean',
        'is_pwd_friendly' => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'has_access_ramp' => 'boolean',
        'has_accessible_toilet' => 'boolean',
        'has_elevator' => 'boolean',
        'supports_hearing_impaired' => 'boolean',
        'supports_visually_impaired' => 'boolean',
        'staff_trained_for_pwd' => 'boolean',
        'gps_address' => 'string',
        'accessibility_rating' => 'integer',
        'pwd_notes' => 'string',
    ];


    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function programme()
    {
        return $this->belongsToMany(Programme::class, 'courses');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function districts()
    {
        return $this->belongsToMany(District::class, 'district_centre', 'centre_id', 'district_id')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::saved(function ($centre) {
            if ($centre->wasChanged('title')) {
                $centre->courses()->get()->each->save();
            }
        });
    }
}
