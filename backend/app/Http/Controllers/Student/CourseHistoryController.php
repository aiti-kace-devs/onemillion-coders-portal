<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\OldAdmission;
use App\Models\UserAdmission;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseHistoryController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        $userId = $user->userId;

        $this->syncMissingHistory($userId);

        $history = OldAdmission::where('user_id', $userId)
            ->with([
                'course:id,course_name,centre_id,programme_id,duration,start_date,end_date',
                'course.centre:id,title',
                'course.programme:id,title',
                'sessionRecord:id,session,course_time',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = OldAdmission::where('user_id', $userId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'admitted') as admitted_count,
                SUM(status = 'confirmed') as confirmed_count,
                SUM(status = 'revoked') as revoked_count
            ")
            ->first();

        $history->getCollection()->transform(fn ($h) => [
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
        ]);

        // Related courses: other courses at the student's centre, excluding already-enrolled
        $enrolledCourseIds = OldAdmission::where('user_id', $userId)
            ->whereNotNull('course_id')
            ->pluck('course_id')
            ->toArray();

        $centreIds = OldAdmission::where('user_id', $userId)
            ->whereIn('status', ['admitted', 'confirmed'])
            ->whereNotNull('centre_id')
            ->pluck('centre_id')
            ->unique()
            ->toArray();

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
     */
    private function syncMissingHistory(string $userId): void
    {
        $admissions = UserAdmission::where('user_id', $userId)->get();

        foreach ($admissions as $admission) {
            $course = $admission->course_id ? Course::find($admission->course_id) : null;
            $status = $admission->confirmed ? 'confirmed' : 'admitted';
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

            OldAdmission::create([
                'user_id'        => $admission->user_id,
                'course_id'      => $admission->course_id,
                'centre_id'      => $course?->centre_id,
                'session'        => $admission->session,
                'status'         => $status,
                'support_status' => $admission->user?->support,
                'started_at'     => $startedAt,
            ]);
        }
    }
}
