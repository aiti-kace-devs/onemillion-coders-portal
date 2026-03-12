<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropBatchForeignKeyOnCourses(): void
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'courses'
               AND COLUMN_NAME = 'batch_id'
               AND REFERENCED_TABLE_NAME = 'admission_batches'"
        );

        foreach ($constraints as $row) {
            $name = $row->name ?? null;
            if (!$name) {
                continue;
            }

            DB::statement("ALTER TABLE `courses` DROP FOREIGN KEY `{$name}`");
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'batch_id')) {
            return;
        }

        DB::statement('ALTER TABLE `courses` MODIFY `batch_id` BIGINT UNSIGNED NULL');

        $this->dropBatchForeignKeyOnCourses();

        DB::statement(
            "UPDATE `courses` c
             LEFT JOIN `admission_batches` b ON b.`id` = c.`batch_id`
             SET c.`batch_id` = NULL
             WHERE c.`batch_id` IS NOT NULL
               AND b.`id` IS NULL"
        );

        DB::statement(
            "ALTER TABLE `courses`
             ADD CONSTRAINT `fk_courses_batch`
             FOREIGN KEY (`batch_id`)
             REFERENCES `admission_batches`(`id`)
             ON DELETE SET NULL
             ON UPDATE CASCADE"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('courses') || !Schema::hasColumn('courses', 'batch_id')) {
            return;
        }

        $this->dropBatchForeignKeyOnCourses();

        DB::statement(
            "ALTER TABLE `courses`
             ADD CONSTRAINT `fk_courses_batch`
             FOREIGN KEY (`batch_id`)
             REFERENCES `admission_batches`(`id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE"
        );
    }
};

