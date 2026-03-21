<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $years = [
            [
                'start_year' => 2025,
                'end_year' => 2026,
                'is_active' => true,
                'status' => AcademicYear::STATUS_ACTIVE,
            ],
            [
                'start_year' => 2026,
                'end_year' => 2027,
                'is_active' => false,
                'status' => AcademicYear::STATUS_INACTIVE,
            ],
        ];

        foreach ($years as $year) {
            AcademicYear::updateOrCreate(
                [
                    'start_year' => $year['start_year'],
                    'end_year' => $year['end_year'],
                ],
                [
                    'name' => $year['start_year'] . '-' . $year['end_year'],
                    'is_active' => $year['is_active'],
                    'status' => $year['status'],
                ]
            );
        }

        // Guarantee only one active record after seeding.
        $active = AcademicYear::query()->where('is_active', true)->orderBy('start_year', 'desc')->first();
        if ($active) {
            $active->activate();
        }
    }
}
