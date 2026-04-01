<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'database.connections.legacy' => array_merge(
        config('database.connections.mysql'),
        ['database' => 'one-mil-coders']
    ),
]);

$tables = [
    'admins',
    'users',
    'programmes',
    'centres',
    'courses',
    'course_sessions',
    'user_admission',
    'attendances',
    'student_partner_progress',
    'student_partner_progress_history',
    'partner_course_mappings',
];

$legacy = DB::connection('legacy');
$target = DB::connection();

foreach ($tables as $table) {
    if (!Schema::connection('legacy')->hasTable($table) || !Schema::hasTable($table)) {
        fwrite(STDOUT, "[skip] {$table}: missing in source or target\n");
        continue;
    }

    $sourceCols = Schema::connection('legacy')->getColumnListing($table);
    $targetCols = Schema::getColumnListing($table);
    $common = array_values(array_intersect($sourceCols, $targetCols));

    if ($common === []) {
        fwrite(STDOUT, "[skip] {$table}: no compatible columns\n");
        continue;
    }

    $total = 0;
    $pk = in_array('id', $common, true) ? 'id' : null;
    $query = $legacy->table($table)->select($common);
    $columnCount = max(count($common), 1);
    $maxChunk = (int) floor(60000 / $columnCount);
    $chunkSize = max(50, min(2000, $maxChunk));

    $copyChunk = function ($rows) use ($target, $table, &$total) {
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = (array) $row;
        }
        if ($payload !== []) {
            $target->table($table)->insertOrIgnore($payload);
            $total += count($payload);
        }
    };

    if ($pk) {
        $query->orderBy($pk)->chunk($chunkSize, $copyChunk);
    } else {
        $query->chunk($chunkSize, $copyChunk);
    }

    fwrite(STDOUT, "[ok] {$table}: processed {$total} rows using chunk {$chunkSize}\n");
}

fwrite(STDOUT, "Legacy import complete.\n");
