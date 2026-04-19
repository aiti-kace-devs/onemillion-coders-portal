<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('application_review_completed_at')->nullable();
        });

        // Existing accounts that have already progressed: treat review as acknowledged so deploy does not block them.
        if (Schema::hasTable('user_assessments')) {
            $mark = fn () => DB::raw('COALESCE(updated_at, created_at)');

            DB::table('users')
                ->whereNull('application_review_completed_at')
                ->whereIn('id', DB::table('user_assessments')->where('completed', 1)->pluck('user_id'))
                ->update(['application_review_completed_at' => $mark()]);

            DB::table('users')
                ->whereNull('application_review_completed_at')
                ->whereNotNull('registered_course')
                ->update(['application_review_completed_at' => $mark()]);

            if (Schema::hasTable('ghana_card_verifications')) {
                DB::table('users')
                    ->whereNull('application_review_completed_at')
                    ->whereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('ghana_card_verifications as g')
                            ->whereColumn('g.user_id', 'users.id')
                            ->where('g.verified', true)
                            ->where('g.code', '00');
                    })
                    ->update(['application_review_completed_at' => $mark()]);
            }

            if (Schema::hasTable('user_admission')) {
                DB::table('users')
                    ->whereNull('application_review_completed_at')
                    ->whereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('user_admission as a')
                            ->whereColumn('a.user_id', 'users.userId');
                    })
                    ->update(['application_review_completed_at' => $mark()]);
            }

            if (Schema::hasTable('admission_waitlist')) {
                DB::table('users')
                    ->whereNull('application_review_completed_at')
                    ->whereExists(function ($q) {
                        $q->selectRaw('1')
                            ->from('admission_waitlist as w')
                            ->whereColumn('w.user_id', 'users.userId')
                            ->whereIn('w.status', ['pending', 'notified']);
                    })
                    ->update(['application_review_completed_at' => $mark()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('application_review_completed_at');
        });
    }
};
