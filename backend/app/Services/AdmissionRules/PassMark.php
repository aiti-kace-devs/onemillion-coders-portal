<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class PassMark implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Filter users by minimum exam score
     * Priority: 0 (HIGHEST - executes first)
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $passMark = $value['pass_mark'] ?? $this->params['pass_mark'] ?? 50;
        
        $query->whereHas('examResults', function($q) use ($passMark) {
            $q->where('yes_ans', '>=', $passMark);
        });
        
        return $next($query);
    }
}
