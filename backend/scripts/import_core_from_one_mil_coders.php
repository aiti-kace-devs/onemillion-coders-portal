<?php

declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=omcp_db;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = [
    'admins',
    'users',
    'branches',
    'districts',
    'programmes',
    'centres',
    'courses',
    'course_sessions',
    'user_admission',
    'attendances',
];

foreach ($tables as $table) {
    $sourceColumns = $pdo->query(
        "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = 'one-mil-coders' AND table_name = '{$table}' ORDER BY ORDINAL_POSITION"
    )->fetchAll(PDO::FETCH_COLUMN);

    $targetColumns = $pdo->query(
        "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = 'omcp_db' AND table_name = '{$table}' ORDER BY ORDINAL_POSITION"
    )->fetchAll(PDO::FETCH_COLUMN);

    $common = array_values(array_intersect($sourceColumns, $targetColumns));
    if ($common === []) {
        echo "[skip] {$table}: no common columns\n";
        continue;
    }

    $columnList = '`' . implode('`,`', $common) . '`';
    $query = "INSERT IGNORE INTO `{$table}` ({$columnList}) SELECT {$columnList} FROM `one-mil-coders`.`{$table}`";
    $pdo->exec($query);
    echo "[ok] {$table}: imported with " . count($common) . " columns\n";
}

echo "Core import from one-mil-coders completed.\n";
