<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->text('gps_address')->nullable();
            $table->boolean('is_pwd_friendly')->default(false);

            $table->boolean('wheelchair_accessible')->default(false);
            $table->boolean('has_access_ramp')->default(false);
            $table->boolean('has_accessible_toilet')->default(false);
            $table->boolean('has_elevator')->default(false);

            $table->boolean('supports_hearing_impaired')->default(false);
            $table->boolean('supports_visually_impaired')->default(false);

            $table->boolean('staff_trained_for_pwd')->default(false);

            $table->tinyInteger('accessibility_rating')->nullable(); // 1–5
            $table->text('pwd_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn([
                'is_pwd_friendly',
                'wheelchair_accessible',
                'has_access_ramp',
                'has_accessible_toilet',
                'has_elevator',
                'supports_hearing_impaired',
                'supports_visually_impaired',
                'staff_trained_for_pwd',
                'accessibility_rating',
                'pwd_notes',
            ]);
        });
    }
};
