<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'sub_title',
        'duration',
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
        'status'
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

    /**
     * Get all rules assigned to this programme
     */
    public function rules()
    {
        return $this->morphToMany(Rule::class, 'ruleable', 'rule_assignments')
            ->withPivot(['value', 'priority'])
            ->withTimestamps()
            ->orderBy('rule_assignments.priority', 'asc');
    }

    /**
     * Get effective rules for admission
     */
    public function getEffectiveRules()
    {
        return $this->rules()->where('rule_assignments.is_active', true)->get();
    }

    /**
     * Get all rules including inactive
     */
    public function getAllRules()
    {
        return $this->rules()->get();
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
        });
    }
}
