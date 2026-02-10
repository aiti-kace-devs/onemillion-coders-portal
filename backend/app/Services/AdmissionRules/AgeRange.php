<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class AgeRange implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Filter users by age range
     * Priority: 5
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $minAge = $value['min_age'] ?? $this->params['min_age'] ?? null;
        $maxAge = $value['max_age'] ?? $this->params['max_age'] ?? null;
        
        if ($minAge !== null) {
            $query->where(function($q) use ($minAge) {
                $q->where('age', '>=', $minAge)
                  ->orWhereRaw('CAST(age AS UNSIGNED) >= ?', [$minAge]);
            });
        }
        
        if ($maxAge !== null) {
            $query->where(function($q) use ($maxAge) {
                $q->where('age', '<=', $maxAge)
                  ->orWhereRaw('CAST(age AS UNSIGNED) <= ?', [$maxAge]);
            });
        }
        
        return $next($query);
    }
}
