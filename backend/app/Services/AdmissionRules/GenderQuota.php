<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class GenderQuota implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Ensure minimum gender representation
     * Priority: 4
     * 
     * Note: This rule sorts to prioritize the target gender, ensuring they appear first
     * The actual limit enforcement happens in the AdmissionService
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $gender = $value['gender'] ?? $this->params['gender'] ?? 'female';
        $minCount = $value['min_count'] ?? $this->params['min_count'] ?? 0;
        
        // Prioritize the target gender by sorting them first
        if ($minCount > 0) {
            $query->orderByRaw("CASE WHEN gender = ? THEN 0 ELSE 1 END", [$gender]);
        }
        
        return $next($query);
    }
}
