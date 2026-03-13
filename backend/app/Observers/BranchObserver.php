<?php

namespace App\Observers;

use App\Models\Branch;
use App\Services\CourseMatchReferenceService;

class BranchObserver
{
    public function saved(Branch $branch): void
    {
        app(CourseMatchReferenceService::class)->syncReferenceSource('branches');
    }

    public function deleted(Branch $branch): void
    {
        app(CourseMatchReferenceService::class)->syncReferenceSource('branches');
    }

    public function restored(Branch $branch): void
    {
        app(CourseMatchReferenceService::class)->syncReferenceSource('branches');
    }
}
