<?php

namespace App\Observers;

use App\Models\CourseCategory;
use App\Services\CourseMatchReferenceService;

class CourseCategoryObserver
{
    public function saved(CourseCategory $category): void
    {
        app(CourseMatchReferenceService::class)->syncReferenceSource('course_categories');
    }

    public function deleted(CourseCategory $category): void
    {
        app(CourseMatchReferenceService::class)->syncReferenceSource('course_categories');
    }

    public function restored(CourseCategory $category): void
    {
        app(CourseMatchReferenceService::class)->syncReferenceSource('course_categories');
    }
}
