<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class SortByDate implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Sort users by registration date (first come, first served)
     * Priority: 3
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $direction = $value['direction'] ?? $this->params['direction'] ?? 'asc';
        
        $query->orderBy('created_at', $direction);
        
        return $next($query);
    }
}
