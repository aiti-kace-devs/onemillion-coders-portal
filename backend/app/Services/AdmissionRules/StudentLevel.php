<?php

namespace App\Services\AdmissionRules;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class StudentLevel implements AdmissionRuleInterface
{
    protected array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function apply(Builder $query, $value, Closure $next): Builder
    {
        if (is_string($value)) {
            $value = json_decode($value, true) ?? [];
        }

        $level = $value['level'] ?? $this->params['level'] ?? null;
        $not = $value['not'] ?? $this->params['not'] ?? false;

        if ($level) {
            $query->whereNotNull('student_level');
            if ($not) {
                $query->where('student_level', '!=', $level);
            } else {
                $query->where('student_level', $level);
            }
        }

        return $next($query);
    }
}
