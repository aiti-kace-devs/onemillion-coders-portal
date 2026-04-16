<?php

namespace App\Services;

use App\Models\Centre;
use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CentreDeletionService
{
    public function delete(Centre $centre): void
    {
        DB::transaction(function () use ($centre) {
            $courseIds = Course::query()
                ->where('centre_id', $centre->id)
                ->pluck('id');

            $courseSessionIds = DB::table('course_sessions')
                ->where('centre_id', $centre->id)
                ->pluck('id');

            if ($courseIds->isNotEmpty()) {
                DB::table('users')
                    ->whereIn('registered_course', $courseIds)
                    ->update(['registered_course' => null]);

                DB::table('attendances')
                    ->whereIn('course_id', $courseIds)
                    ->delete();
            }

            $this->deleteByCourseOrSession('user_admission', $courseIds, $courseSessionIds);

            if (Schema::hasTable('old_admissions')) {
                $this->deleteByCourseOrSession('old_admissions', $courseIds, $courseSessionIds);
            }

            if ($courseSessionIds->isNotEmpty()) {
                DB::table('course_sessions')
                    ->whereIn('id', $courseSessionIds)
                    ->delete();
            }

            if (Schema::hasTable('centre_sessions')) {
                DB::table('centre_sessions')
                    ->where('centre_id', $centre->id)
                    ->delete();
            }

            if ($courseIds->isNotEmpty()) {
                Course::query()
                    ->whereIn('id', $courseIds)
                    ->get()
                    ->each
                    ->delete();
            }

            if (Schema::hasTable('admin_centre')) {
                DB::table('admin_centre')
                    ->where('centre_id', $centre->id)
                    ->delete();
            }

            if (Schema::hasTable('district_centre')) {
                DB::table('district_centre')
                    ->where('centre_id', $centre->id)
                    ->delete();
            }

            $centre->delete();
        });
    }

    protected function deleteByCourseOrSession(string $table, Collection $courseIds, Collection $courseSessionIds): void
    {
        if ($courseIds->isEmpty() && $courseSessionIds->isEmpty()) {
            return;
        }

        DB::table($table)
            ->where(function ($query) use ($courseIds, $courseSessionIds) {
                if ($courseIds->isNotEmpty()) {
                    $query->whereIn('course_id', $courseIds);
                }

                if ($courseSessionIds->isNotEmpty()) {
                    if ($courseIds->isNotEmpty()) {
                        $query->orWhereIn('session', $courseSessionIds);
                    } else {
                        $query->whereIn('session', $courseSessionIds);
                    }
                }
            })
            ->delete();
    }
}
