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
        $passMark = $value['pass_mark'] ?? $this->params['pass_mark'] ?? config('MINIMUM_EXAM_PASS_PERCENTAGE', 50);

        $query->whereHas('examResults', function ($q) use ($passMark) {
            $q->whereRaw('ROUND((yes_ans / (no_ans + yes_ans)) * 100, 2) >= CAST(? AS DECIMAL)', [$passMark]);
        })
            ->orderBy(function ($q) {
                $q->selectRaw('ROUND((yes_ans / (no_ans + yes_ans)) * 100, 2)')
                    ->from('oex_results')
                    ->whereColumn('users.id', 'oex_results.user_id')
                    ->limit(1);
            }, 'desc');

        return $next($query);
    }
}
