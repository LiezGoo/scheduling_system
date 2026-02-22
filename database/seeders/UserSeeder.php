<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // STEP 1: safely clear users table (no drops) - database-agnostic
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('users')->truncate();
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            // For other databases, just truncate (may fail with FKs)
            DB::table('users')->truncate();
        }

        $faker = \Faker\Factory::create();

        // Preload relations for integrity checks
        $departments = Department::with('programs')->get()->keyBy('id');
        $programs = Program::with('department')->get();

        // Department Heads: one per department (department_id required, program_id null)
        foreach ($departments as $department) {
            User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => "depthead+{$department->id}@example.test",
                'role' => 'department_head',
                'department_id' => $department->id,
                'program_id' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
                'remember_token' => Str::random(10),
            ]);
        }

        // Program Heads: one per program (department_id must match)
        foreach ($programs as $program) {
            User::create([
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => "proghead+{$program->id}@example.test",
                'role' => 'program_head',
                'department_id' => $program->department_id,
                'program_id' => $program->id,
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
                'remember_token' => Str::random(10),
            ]);
        }

        // Instructors: 2 per department, optional program_id, with faculty_scheme
        $facultySchemes = ['7:00-16:00', '8:00-17:00', '10:00-19:00'];
        foreach ($departments as $department) {
            $deptPrograms = $department->programs ?? collect();

            for ($i = 0; $i < 2; $i++) {
                $program = $deptPrograms->isNotEmpty() ? $deptPrograms->random() : null;

                User::create([
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                    'email' => $faker->unique()->safeEmail(),
                    'role' => 'instructor',
                    'department_id' => $department->id,
                    'program_id' => $program?->id,
                    'faculty_scheme' => $faker->randomElement($facultySchemes),
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password123!'),
                    'remember_token' => Str::random(10),
                ]);
            }
        }

        // Students: 10 per program (department inferred from program)
        foreach ($programs as $program) {
            for ($i = 0; $i < 10; $i++) {
                User::create([
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName(),
                    'email' => $faker->unique()->safeEmail(),
                    'role' => 'student',
                    'department_id' => $program->department_id,
                    'program_id' => $program->id,
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password123!'),
                    'remember_token' => Str::random(10),
                ]);
            }
        }
    }
}
