<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centre extends Model
{
    use CrudTrait;
    use HasFactory;


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

    /**
     * Get all rules assigned to this centre
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
        static::saved(function ($centre) {
            $centre->courses->each->save();
        });
    }
}
