<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Rule extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'rule_class_path',
        'description',
        'default_parameters',
        'is_active',
    ];

    protected $casts = [
        'default_parameters' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all programmes that have this rule assigned
     */
    public function programmes(): MorphToMany
    {
        return $this->morphedByMany(Programme::class, 'ruleable', 'rule_assignments')
            ->withPivot(['value', 'priority'])
            ->withTimestamps()
            ->orderBy('rule_assignments.priority', 'asc');
    }

    /**
     * Get all courses that have this rule assigned
     */
    public function courses(): MorphToMany
    {
        return $this->morphedByMany(Course::class, 'ruleable', 'rule_assignments')
            ->withPivot(['value', 'priority'])
            ->withTimestamps()
            ->orderBy('rule_assignments.priority', 'asc');
    }

    /**
     * Instantiate the rule class with parameters
     *
     * @param mixed $parameters Parameters from pivot or default
     * @return object Instance of the rule class
     */
    public function instantiate($parameters = null)
    {
        $className = $this->rule_class_path;

        if (!class_exists($className)) {
            throw new \Exception("Rule class {$className} not found");
        }

        $defaultParams = $this->default_parameters ?? [];

        // Merge with default parameters
        $params = array_merge(
            $defaultParams,
            is_array($parameters) ? $parameters : json_decode($parameters, true) ?? []
        );

        return new $className($params);
    }

    /**
     * Scope to get only active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
