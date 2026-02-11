<?php

namespace App\Models;

use App\Models\Rule;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RuleAssignment extends Model
{
    use CrudTrait;

    protected $fillable = [
        'rule_id',
        'ruleable_type',
        'ruleable_id',
        'value',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'value' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the rule for this assignment
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    /**
     * Get the owning ruleable model (Programme or Course)
     */
    public function ruleable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the display name for the ruleable
     */
    public function getRuleableNameAttribute()
    {
        if ($this->ruleable_type === 'App\Models\Programme') {
            return $this->ruleable->title ?? 'N/A';
        } elseif ($this->ruleable_type === 'App\Models\Course') {
            return $this->ruleable->course_name ?? 'N/A';
        }
        return 'N/A';
    }

    /**
     * Get the display type for the ruleable
     */
    public function getRuleableTypeNameAttribute()
    {
        if ($this->ruleable_type === 'App\Models\Programme') {
            return 'Programme';
        } elseif ($this->ruleable_type === 'App\Models\Course') {
            return 'Course';
        }
        return 'Unknown';
    }
}
