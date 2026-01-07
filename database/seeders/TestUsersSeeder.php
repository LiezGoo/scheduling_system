<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample users for testing the User Management module.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_DEPARTMENT_HEAD,
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'first_name' => 'Juan',
                'last_name' => 'dela Cruz',
                'email' => 'juan.delacruz@sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_PROGRAM_HEAD,
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Reyes',
                'email' => 'pedro.reyes@sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_INSTRUCTOR,
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'Garcia',
                'email' => 'ana.garcia@sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_INSTRUCTOR,
                'status' => User::STATUS_INACTIVE,
            ],
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@student.sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'status' => User::STATUS_ACTIVE,
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@student.sorsu.edu.ph',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'status' => User::STATUS_ACTIVE,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Test users created successfully!');
        $this->command->info('All passwords are: password');
    }
}
