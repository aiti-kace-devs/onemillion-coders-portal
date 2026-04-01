<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$studentEmail = 'noblemort123@gmail.com';
$adminEmail = 'samtugah@gmail.com';
$plainPassword = 'password';

$student = \App\Models\User::where('email', $studentEmail)->first();
if ($student) {
    $student->password = Hash::make($plainPassword);
    $student->save();
    echo "[ok] student password reset: {$studentEmail}\n";
} else {
    echo "[warn] student not found: {$studentEmail}\n";
}

$admin = \App\Models\Admin::where('email', $adminEmail)->first();
if (!$admin) {
    $admin = \App\Models\Admin::create([
        'name' => 'Sam Tugah',
        'email' => $adminEmail,
        'password' => Hash::make($plainPassword),
        'status' => true,
    ]);
    echo "[ok] admin created: {$adminEmail}\n";
} else {
    $admin->password = Hash::make($plainPassword);
    $admin->status = true;
    $admin->save();
    echo "[ok] admin password reset: {$adminEmail}\n";
}

$studentValid = $student ? Hash::check($plainPassword, (string) $student->password) : false;
$adminValid = Hash::check($plainPassword, (string) $admin->password);

echo "[verify] student_password_matches=" . ($studentValid ? 'yes' : 'no') . "\n";
echo "[verify] admin_password_matches=" . ($adminValid ? 'yes' : 'no') . "\n";
