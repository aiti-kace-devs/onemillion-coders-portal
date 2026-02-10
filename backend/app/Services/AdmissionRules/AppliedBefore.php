<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AppliedBefore implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Filter or prioritize students based on application date
     * Priority: 2
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $beforeDate = $value['before_date'] ?? $this->params['before_date'] ?? null;
        $priority = $value['priority'] ?? $this->params['priority'] ?? 'include_only';
        
        if (!$beforeDate) {
            return $next($query);
        }

        $date = Carbon::parse($beforeDate);
        
        switch ($priority) {
            case 'include_only':
                $query->where('created_at', '<=', $date);
                break;
            
            case 'prioritize':
                $query->orderByRaw("CASE WHEN created_at <= ? THEN 0 ELSE 1 END", [$date]);
                break;
            
            case 'exclude':
                $query->where('created_at', '>', $date);
                break;
        }
        
        return $next($query);
    }
}
