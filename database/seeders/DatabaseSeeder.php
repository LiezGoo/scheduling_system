<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\TestDataSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure departments/programs already exist before seeding users
        $this->call([
            DepartmentSeeder::class,
            UserSeeder::class,
            TestDataSeeder::class,  // ← NEW: Comprehensive test data
        ]);
    }
}
