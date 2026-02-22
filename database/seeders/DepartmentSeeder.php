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
            [
                'department_code' => 'CICT',
                'department_name' => 'College of Information and Communications Technolgy',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'department_code' => 'MAT002',
                'department_name' => 'Mathematics',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
