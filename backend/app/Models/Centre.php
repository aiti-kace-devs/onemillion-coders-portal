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
            ->setDescriptionForEvent(fn (string $event) => "Centre {$event}");
    }

    protected $fillable = [
        'title',
        'branch_id',
        'constituency_id',
        'status',
        'is_ready',
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
        'images',
        'video',
        'gps_location',
        'seat_count',
        'short_slots_per_day',
        'long_slots_per_day',
    ];

    protected $casts = [
        'constituency_id' => 'integer',
        'status' => 'boolean',
        'is_ready' => 'boolean',
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
        'images' => 'array',
        'video' => 'string',
        'gps_location' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function constituency()
    {
        return $this->belongsTo(Constituency::class, 'constituency_id', 'id');
    }

    public function programme()
    {
        return $this->belongsToMany(Programme::class, 'courses');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_centre', 'centre_id', 'admin_id')
            ->withTimestamps();
    }

    public function centreSessions()
    {
        return $this->hasMany(CentreSession::class, 'centre_id');
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
                $centre->centreSessions()->get()->each->save();
            }
        });

        static::saving(function ($centre) {
            // Auto-derive slot counts from seat_count using AppConfig percentages if not explicitly set
            if ($centre->seat_count && (!$centre->short_slots_per_day || !$centre->long_slots_per_day)) {
                $shortPercent = (int) (\App\Models\AppConfig::getValue('SHORT_SLOTS_PERCENTAGE', 60));
                $longPercent = (int) (\App\Models\AppConfig::getValue('LONG_SLOTS_PERCENTAGE', 40));

                $centre->short_slots_per_day = (int) round($centre->seat_count * $shortPercent / 100);
                $centre->long_slots_per_day = (int) round($centre->seat_count * $longPercent / 100);

                // Adjust rounding so short + long = seat_count
                $diff = $centre->seat_count - ($centre->short_slots_per_day + $centre->long_slots_per_day);
                if ($diff !== 0) {
                    $centre->short_slots_per_day += $diff;
                }
            }
        });
    }
}
