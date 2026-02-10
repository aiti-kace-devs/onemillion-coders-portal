<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class EducationalLevel implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Sort by educational hierarchy
     * Priority: 6
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $hierarchy = $value['hierarchy'] ?? $this->params['hierarchy'] ?? [
            'PhD', 'Masters', 'Bachelors', 'Diploma', 'High School'
        ];
        
        $minLevel = $value['min_level'] ?? $this->params['min_level'] ?? null;
        
        // Filter by minimum level if specified
        if ($minLevel && in_array($minLevel, $hierarchy)) {
            $minIndex = array_search($minLevel, $hierarchy);
            $allowedLevels = array_slice($hierarchy, 0, $minIndex + 1);
            
            $query->whereIn('educational_level', $allowedLevels);
        }
        
        // Sort by hierarchy using FIELD() function
        $fieldOrder = implode("','", array_map(function($level) {
            return addslashes($level);
        }, $hierarchy));
        
        $query->orderByRaw("FIELD(educational_level, '{$fieldOrder}') ASC");
        
        return $next($query);
    }
}
