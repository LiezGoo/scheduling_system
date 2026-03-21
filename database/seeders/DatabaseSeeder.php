<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
        // Keep production safe by default, but seed baseline academic years
        // for local/testing workflows.
        if (app()->environment(['local', 'testing', 'staging'])) {
            $this->call([
                AcademicYearSeeder::class,
            ]);
        }
    }
}
