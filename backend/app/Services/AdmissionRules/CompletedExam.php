<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class CompletedExam implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * Filter users who have completed their exam
     * Priority: 1
     */
    public function apply(Builder $query, $value, Closure $next): Builder
    {
        $requireCompletion = $value['require_completion'] ?? $this->params['require_completion'] ?? true;
        $requireSubmission = $value['require_submission'] ?? $this->params['require_submission'] ?? true;
        
        if ($requireCompletion) {
            $query->whereHas('examResults');
        }
        
        if ($requireSubmission) {
            $query->whereHas('userExams', function($q) {
                $q->whereNotNull('submitted');
            });
        }
        
        return $next($query);
    }
}
