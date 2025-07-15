<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->foreignId('course_category_id')->nullable()->constrained('course_categories')->nullOnDelete()->after('id');
            $table->unsignedBigInteger('cover_image_id')->nullable()->after('course_category_id');
            $table->text('description')->nullable()->after('cover_image_id');
            $table->text('sub_title')->nullable()->after('description');
            $table->text('overview')->nullable()->after('sub_title');
            $table->text('prerequisites')->nullable()->after('overview');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropForeign(['course_category_id']);
            // $table->dropForeign(['cover_image_id']); // Uncomment if foreign key was added

            $table->dropColumn([
                'course_category_id',
                'cover_image_id',
                'description',
                'prerequisites',
            ]);
        });
    }
};
