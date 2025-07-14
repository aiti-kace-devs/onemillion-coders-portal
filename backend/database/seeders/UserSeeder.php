<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

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
            'password' => config('app.super_admin_password'),
            'userId' => Str::uuid(),
            'super' => 1,
            'is_super' => 1,
            'status' => 1,
        ];

        User::createOrFirst([
            'email' => $user['email'],
        ], $user);
    }
}
