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
     * 
     * DISABLED: All test seeders removed for production.
     * Database contains only schema, no sample data.
     */
    public function run(): void
    {
        // No seeding for production environment
        // All data must be manually entered through the application
    }
}
