<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify the foreign key constraint
        DB::statement('ALTER TABLE users DROP FOREIGN KEY users_registered_course_foreign');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_registered_course_foreign FOREIGN KEY (registered_course) REFERENCES courses(id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to revert the foreign key constraint
        DB::statement('ALTER TABLE users DROP FOREIGN KEY users_registered_course_foreign');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_registered_course_foreign FOREIGN KEY (registered_course) REFERENCES courses(id) ON DELETE RESTRICT');
    }
};
