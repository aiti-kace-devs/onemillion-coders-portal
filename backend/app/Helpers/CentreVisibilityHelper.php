<?php

namespace App\Helpers;

use App\Models\Admin;

final class CentreVisibilityHelper
{
    /**
     * Return centre IDs visible to the current admin.
     * `null` means unrestricted visibility (super admin or non-centre managers).
     */
    public static function currentAdminVisibleCentreIds(): ?array
    {
        $admin = backpack_user();

        if (! $admin instanceof Admin) {
            return null;
        }

        if (method_exists($admin, 'isSuper') && $admin->isSuper()) {
            return null;
        }

        if (method_exists($admin, 'visibleCentreIds')) {
            return $admin->visibleCentreIds();
        }

        return $admin->assignedCentres()
            ->pluck('centres.id')
            ->map(fn ($centreId) => (int) $centreId)
            ->all();
    }
}
