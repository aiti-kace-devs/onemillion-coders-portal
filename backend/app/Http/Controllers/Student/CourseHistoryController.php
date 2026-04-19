<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AdmissionRejection;
use App\Models\Course;
use App\Models\OldAdmission;
use App\Models\UserAdmission;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseHistoryController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        $userId = $user->userId;

        $this->syncMissingHistory($userId);

        // Active/confirmed history from old_admissions (exclude revoked — those come from admission_rejections)
        $activeHistory = OldAdmission::where('user_id', $userId)
            ->where('status', '!=', 'revoked')
            ->with([
                'course:id,course_name,centre_id,programme_id,duration,start_date,end_date',
                'course.centre:id,title',
                'course.programme:id,title',
                'sessionRecord:id,session,course_time',
            ])
            ->get()
            ->map(fn ($h) => [
                'id'           => $h->id,
                'course_name'  => $h->course?->course_name ?? '—',
                'programme'    => $h->course?->programme?->title,
                'duration'     => $h->course?->duration,
                'centre'       => $h->course?->centre?->title ?? '—',
                'session'      => $h->sessionRecord?->session ?? 'Self-paced',
                'session_time' => $h->sessionRecord?->course_time,
                'support'      => $h->support_status ? 'With support' : 'Self-paced',
                'status'       => $h->status,
                'started_at'   => $h->started_at?->format('Y-m-d'),
                'ended_at'     => $h->ended_at?->format('Y-m-d'),
                'sort_date'    => $h->started_at ?? $h->created_at,
            ]);

        // Revoked history from admission_rejections
        $revokedHistory = AdmissionRejection::where('user_id', $userId)
            ->with([
                'course:id,course_name,centre_id,programme_id,duration',
                'course.centre:id,title',
                'course.programme:id,title',
            ])
            ->get()
            ->map(fn ($r) => [
                'id'           => 'rej-' . $r->id,
                'course_name'  => $r->course?->course_name ?? '—',
                'programme'    => $r->course?->programme?->title,
                'duration'     => $r->course?->duration,
                'centre'       => $r->course?->centre?->title ?? '—',
                'session'      => null,
                'session_time' => null,
                'support'      => null,
                'status'       => 'revoked',
                'started_at'   => null,
                'ended_at'     => $r->rejected_at ? Carbon::parse($r->rejected_at)->format('Y-m-d') : null,
                'sort_date'    => $r->rejected_at ? Carbon::parse($r->rejected_at) : $r->created_at,
            ]);

        // Merge and sort by most recent first
        $allHistory = $activeHistory->concat($revokedHistory)
            ->sortByDesc('sort_date')
            ->values()
            ->map(fn ($item) => collect($item)->except('sort_date')->all());

        // Paginate
        $page = request()->get('page', 1);
        $perPage = 15;
        $history = new LengthAwarePaginator(
            $allHistory->forPage($page, $perPage)->values(),
            $allHistory->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        // Stats
        $revokedCount = $revokedHistory->count();
        $ongoingCount = UserAdmission::where('user_id', $userId)->count();
        $total = $activeHistory->count() + $revokedCount;
        $completedCount = max(0, $total - $ongoingCount - $revokedCount);

        $stats = [
            'total'           => $total,
            'completed_count' => $completedCount,
            'ongoing_count'   => $ongoingCount,
            'revoked_count'   => $revokedCount,
        ];

        // Related courses: other courses at the student's centre, excluding already-enrolled
        $admissionCourseIds = OldAdmission::where('user_id', $userId)
            ->whereNotNull('course_id')
            ->pluck('course_id');
        $rejectionCourseIds = AdmissionRejection::where('user_id', $userId)
            ->whereNotNull('course_id')
            ->pluck('course_id');
        $enrolledCourseIds = $admissionCourseIds->merge($rejectionCourseIds)->unique()->toArray();

        // Get centres from old_admissions + courses linked to rejections
        $centreIds = OldAdmission::where('user_id', $userId)
            ->whereNotNull('centre_id')
            ->pluck('centre_id');
        $rejectionCentreIds = Course::whereIn('id', $rejectionCourseIds)
            ->whereNotNull('centre_id')
            ->pluck('centre_id');
        $centreIds = $centreIds->merge($rejectionCentreIds)->unique()->toArray();

        $relatedCourses = [];
        if (!empty($centreIds)) {
            $relatedCourses = Course::whereIn('centre_id', $centreIds)
                ->whereNotIn('id', $enrolledCourseIds)
                ->where('status', true)
                ->with('centre:id,title', 'programme:id,title,image')
                ->limit(6)
                ->get()
                ->map(fn ($c) => [
                    'id'          => $c->id,
                    'course_name' => $c->course_name,
                    'programme'   => $c->programme?->title,
                    'centre'      => $c->centre?->title ?? '—',
                    'duration'    => $c->duration,
                    'image'       => $c->programme?->image,
                ]);
        }

        // Student can only enroll if they have no active (admitted/confirmed) course
        $hasActiveCourse = OldAdmission::where('user_id', $userId)
            ->whereIn('status', ['admitted', 'confirmed'])
            ->exists();

        return Inertia::render('Student/CourseHistory', [
            'history'        => $history,
            'stats'          => $stats,
            'relatedCourses' => $relatedCourses,
            'canEnroll'      => !$hasActiveCourse,
        ]);
    }

    /**
     * Auto-create history rows for admissions that were missed by the observer
     * (e.g. queue worker running old code, raw DB inserts, etc.)
     *
     * Also closes stale active rows that no longer have a matching UserAdmission,
     * and enforces one active course per user.
     */
    private function syncMissingHistory(string $userId): void
    {
        $admissions = UserAdmission::where('user_id', $userId)->get();
        $activeCourseIds = $admissions->pluck('course_id')->filter()->toArray();

        // Close old_admissions rows that are still active but have no matching UserAdmission
        OldAdmission::where('user_id', $userId)
            ->whereIn('status', ['admitted', 'confirmed'])
            ->when(! empty($activeCourseIds), function ($q) use ($activeCourseIds) {
                $q->whereNotIn('course_id', $activeCourseIds);
            }, function ($q) {
                // No active admissions at all — close everything
                $q;
            })
            ->update([
                'status' => 'revoked',
                'ended_at' => now(),
            ]);

        foreach ($admissions as $admission) {
            $course = $admission->course_id ? Course::find($admission->course_id) : null;
            $status = 'admitted';
            $startedAt = $admission->confirmed ?: $admission->created_at;

            $historyRow = OldAdmission::where('user_id', $admission->user_id)
                ->where('course_id', $admission->course_id)
                ->whereIn('status', ['admitted', 'confirmed'])
                ->orderByDesc('id')
                ->first();

            if ($historyRow) {
                $historyRow->update([
                    'centre_id'      => $course?->centre_id,
                    'session'        => $admission->session,
                    'status'         => $status,
                    'support_status' => $admission->user?->support,
                    'started_at'     => $startedAt,
                    'ended_at'       => null,
                ]);

                continue;
            }

            try {
                OldAdmission::create([
                    'user_id'        => $admission->user_id,
                    'course_id'      => $admission->course_id,
                    'centre_id'      => $course?->centre_id,
                    'session'        => $admission->session,
                    'status'         => $status,
                    'support_status' => $admission->user?->support,
                    'started_at'     => $startedAt,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to sync course history row', [
                    'user_id' => $admission->user_id,
                    'course_id' => $admission->course_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
