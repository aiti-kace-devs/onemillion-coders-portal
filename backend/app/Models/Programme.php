<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Programme extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public const TIME_ALLOCATION_SHORT = 2;
    public const TIME_ALLOCATION_LONG = 4;
    public const COURSE_TYPE_SHORT = 'short';
    public const COURSE_TYPE_LONG = 'long';

    public function courseType(): string
    {
        return (int) $this->time_allocation === self::TIME_ALLOCATION_LONG
            ? self::COURSE_TYPE_LONG
            : self::COURSE_TYPE_SHORT;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('programme')
            ->setDescriptionForEvent(fn(string $event) => "Programme {$event}");
    }

    protected $fillable = [
        'title',
        'sub_title',
        'duration',
        'duration_in_days',
        'time_allocation',
        'start_date',
        'end_date',
        'description',
        'overview',
        'prerequisites',
        'image',
        'level',
        'job_responsible',
        'cover_image_id',
        'course_category_id',
        'status',
        'mode_of_delivery',
        'provider'
    ];

    protected $casts = [
        'status' => 'boolean',
        'overview' => 'array'
    ];

    public function centre()
    {
        return $this->belongsToMany(Centre::class, 'courses');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }


    public function courseModules()
    {
        return $this->hasMany(CourseModule::class, 'programme_id');
    }


    public function courseCertification()
    {
        return $this->hasMany(CourseCertification::class, 'programme_id');
    }

    public function category()
    {

        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function coverImage()
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    public function tags()
    {
        return $this->belongsToMany(CourseMatchOption::class, 'programme_course_match_options', 'programme_id', 'course_match_option_id');
    }

    public function programmeTags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function isOnline(): bool
    {
        return strtolower(trim((string) $this->mode_of_delivery)) === 'online';
    }





    protected static function booted()
    {
        static::saving(function ($programme) {
            $overview = request('overview', []);

            $whatYouWillLearn = is_array($overview['what_you_will_learn'] ?? null)
                ? array_filter($overview['what_you_will_learn'])
                : [];

            $whyChoose = is_array($overview['why_choose_this_course'] ?? null)
                ? array_filter($overview['why_choose_this_course'])
                : [];

            $programme->overview = [
                'what_you_will_learn' => $whatYouWillLearn,
                'why_choose_this_course' => $whyChoose
            ];

            // Auto-compute duration_in_days and time_allocation from duration
            if ($programme->duration) {
                $hours = (int) $programme->duration;
                $programme->time_allocation = $hours <= 40
                    ? self::TIME_ALLOCATION_SHORT
                    : self::TIME_ALLOCATION_LONG;

                if ($hours <= 40) {
                    $programme->duration_in_days = (int) ceil($hours / 2);
                } elseif ($hours <= 80) {
                    $programme->duration_in_days = 20;
                } elseif ($hours <= 120) {
                    $programme->duration_in_days = 30;
                } elseif ($hours <= 160) {
                    $programme->duration_in_days = 40;
                } elseif ($hours <= 200) {
                    $programme->duration_in_days = 50;
                } else {
                    $programme->duration_in_days = 60;
                }

                $programme->duration = $hours . ' hours';
            }
        });

        static::saved(function ($programme) {
            if ($programme->wasChanged('title')) {
                $programme->courses()->get()->each->save();
            }
        });
    }
}
