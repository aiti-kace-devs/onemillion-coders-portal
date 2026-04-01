<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$student = \App\Models\User::where('email', 'noblemort123@gmail.com')->first();
$admin = \App\Models\Admin::where('email', 'samtugah@gmail.com')->first();

echo "student_found=" . ($student ? 'yes' : 'no') . PHP_EOL;
if ($student) {
    echo "student_id={$student->id}" . PHP_EOL;
    echo "student_status=" . var_export($student->status, true) . PHP_EOL;
    echo "student_password_matches_password=" . (Hash::check('password', (string) $student->password) ? 'yes' : 'no') . PHP_EOL;
}

echo "admin_found=" . ($admin ? 'yes' : 'no') . PHP_EOL;
if ($admin) {
    echo "admin_id={$admin->id}" . PHP_EOL;
    echo "admin_status=" . var_export($admin->status, true) . PHP_EOL;
    echo "admin_password_matches_password=" . (Hash::check('password', (string) $admin->password) ? 'yes' : 'no') . PHP_EOL;
}
