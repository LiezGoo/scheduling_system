<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
{
    DB::table('users')->updateOrInsert(
        ['email' => 'yourliez15@gmail.com'],
        [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    DB::table('users')->updateOrInsert(
        ['email' => 'departmenthead@gmail.com'],
        [
            'first_name' => 'Department Head',
            'last_name' => 'User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'department_head',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
}
}
