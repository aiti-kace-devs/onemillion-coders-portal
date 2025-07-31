<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $user = [
            'name' => config('app.super_admin_name'),
            'email' => config('app.super_admin_email'),
            'password' => Hash::make(config('app.super_admin_password')),
            // 'userId' => Str::uuid(),
            'super' => 1,
            'is_super' => 1,
            'status' => 1,
        ];

        Admin::createOrFirst([
            'email' => $user['email'],
        ], $user);
    }
}
