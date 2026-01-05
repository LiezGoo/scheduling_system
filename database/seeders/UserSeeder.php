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
            'name' => 'Admin User',
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
            'name' => 'Department Head User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'department_head',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );
}
}
