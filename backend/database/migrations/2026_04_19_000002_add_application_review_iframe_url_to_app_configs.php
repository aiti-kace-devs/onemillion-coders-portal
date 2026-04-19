<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Application-review iframe target: empty = generic in-portal UI. Full https://… URL is used as-is;
     * a path such as /application-review is prefixed with QUIZ_FRONTEND_URL (see StudentOperation::resolveApplicationReviewIframeBase).
     */
    public function up(): void
    {
        DB::table('app_configs')->insertOrIgnore([
            [
                'key' => 'APPLICATION_REVIEW_IFRAME_URL',
                'value' => '',
                'type' => 'string',
                'is_cached' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('app_configs')->where('key', 'APPLICATION_REVIEW_IFRAME_URL')->delete();
    }
};
