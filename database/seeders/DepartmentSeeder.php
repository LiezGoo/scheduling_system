<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departments')->insert([
            ['name' => 'Computer Science', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mathematics', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
