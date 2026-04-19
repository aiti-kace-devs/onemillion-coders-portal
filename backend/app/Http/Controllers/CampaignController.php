<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use App\Models\UserAdmission;
use App\Models\Branch;
use App\Models\District;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\ProgrammeBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    /**
     * Send a campaign notification to selected users.
     */
    public static function sendCampaign(Campaign $campaign)
    {
        $users = self::getTargetUsers($campaign);

        foreach ($users as $user) {
            NotificationController::notify(
                $user->id,
                'campaign',
                $campaign->title,
                $campaign->message,
                $campaign->priority,
                'campaign',
                $campaign->id
            );
        }

        $campaign->update(['sent_at' => now()]);
    }

    /**
     * Get users based on hierarchical campaign targets.
     * Uses the deepest selected level in the hierarchy.
     * Hierarchy: branch -> district -> centre -> course -> session
     */
    private static function getTargetUsers(Campaign $campaign)
    {
        $admissionsQuery = UserAdmission::query();

        // Check which levels have selections (from deepest to shallowest)
        
        // Level 5: Sessions (deepest)
        if (!empty($campaign->target_course_sessions)) {
            $admissionsQuery->whereIn('session', $campaign->target_course_sessions);
            return User::whereIn('userId', $admissionsQuery->pluck('user_id'))->distinct()->get();
        }

        // Level 4: Courses
        if (!empty($campaign->target_centres)) {
            $courseIds = Course::whereIn('centre_id', $campaign->target_centres)
                ->pluck('id')
                ->toArray();
            
            $admissionsQuery->whereIn('course_id', $courseIds);
            return User::whereIn('userId', $admissionsQuery->pluck('user_id'))->distinct()->get();
        }

        // Level 3: Centres
        if (!empty($campaign->target_centres)) {
            $courseIds = Course::whereIn('centre_id', $campaign->target_centres)
                ->pluck('id')
                ->toArray();
            
            $admissionsQuery->whereIn('course_id', $courseIds);
            return User::whereIn('userId', $admissionsQuery->pluck('user_id'))->distinct()->get();
        }

        // Level 2: Districts
        if (!empty($campaign->target_districts)) {
            $centreIds = Centre::whereHas('districts', function ($q) use ($campaign) {
                $q->whereIn('district_id', $campaign->target_districts);
            })->pluck('id')->toArray();
            
            $courseIds = Course::whereIn('centre_id', $centreIds)->pluck('id')->toArray();
            $admissionsQuery->whereIn('course_id', $courseIds);
            return User::whereIn('userId', $admissionsQuery->pluck('user_id'))->distinct()->get();
        }

        // Level 1: Branches
        if (!empty($campaign->target_branches)) {
            $centreIds = Centre::whereHas('branch', function ($q) use ($campaign) {
                $q->whereIn('branch_id', $campaign->target_branches);
            })->pluck('id')->toArray();
            
            $courseIds = Course::whereIn('centre_id', $centreIds)->pluck('id')->toArray();
            $admissionsQuery->whereIn('course_id', $courseIds);
            return User::whereIn('userId', $admissionsQuery->pluck('user_id'))->distinct()->get();
        }

        // Default: All admitted users
        return User::whereIn('userId', $admissionsQuery->pluck('user_id'))->distinct()->get();
    }
}
