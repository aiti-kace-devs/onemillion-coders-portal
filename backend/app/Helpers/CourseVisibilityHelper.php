<?php

namespace App\Helpers;

use App\Models\Admin;

final class CourseVisibilityHelper
{
    /**
     * Return course IDs visible to the current admin.
     * `null` means unrestricted visibility (super admin or non-admin contexts).
     */
    public static function currentAdminVisibleCourseIds(): ?array
    {
        $admin = backpack_user();

        if (! $admin instanceof Admin) {
            return null;
        }

        if (method_exists($admin, 'visibleCourseIds')) {
            return $admin->visibleCourseIds();
        }

        if (method_exists($admin, 'isSuper') && $admin->isSuper()) {
            return null;
        }

        return $admin->assignedCourses()
            ->pluck('courses.id')
            ->map(fn ($courseId) => (int) $courseId)
            ->all();
    }
}
