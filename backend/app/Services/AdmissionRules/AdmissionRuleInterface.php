<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

interface AdmissionRuleInterface
{
    /**
     * Apply the admission rule to the query
     *
     * @param Builder $query Eloquent query builder for User model
     * @param mixed $value Rule parameters from pivot table
     * @param Closure $next Next rule in the pipeline
     * @return Builder
     */
    public function apply(Builder $query, $value, Closure $next): Builder;
}
