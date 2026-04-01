<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dumpPath = dirname($root) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'test_omcp_db.sql';

if (!is_file($dumpPath)) {
    fwrite(STDERR, "Dump file not found: {$dumpPath}\n");
    exit(1);
}

$envPath = $root . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envPath)) {
    fwrite(STDERR, ".env file not found.\n");
    exit(1);
}

$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = (int) ($env['DB_PORT'] ?? 3306);
$db = $env['DB_DATABASE'] ?? 'omcp_db';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]
);

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

$fh = fopen($dumpPath, 'r');
if (!$fh) {
    fwrite(STDERR, "Failed to open dump file.\n");
    exit(1);
}

$stmt = '';
$isInsert = false;
$count = 0;

while (($line = fgets($fh)) !== false) {
    $trimmed = ltrim($line);
    if ($stmt === '') {
        if (str_starts_with($trimmed, 'INSERT INTO')) {
            $isInsert = true;
            $stmt = $line;
        }
        continue;
    }

    $stmt .= $line;
    if (str_ends_with(rtrim($line), ';')) {
        if ($isInsert) {
            try {
                $pdo->exec($stmt);
                $count++;
                if ($count % 25 === 0) {
                    fwrite(STDOUT, "Executed {$count} INSERT statements...\n");
                }
            } catch (Throwable $e) {
                fwrite(STDERR, "Failed INSERT statement #{$count}: {$e->getMessage()}\n");
            }
        }
        $stmt = '';
        $isInsert = false;
    }
}

fclose($fh);
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

fwrite(STDOUT, "Done. Executed {$count} INSERT statements from dump.\n");
